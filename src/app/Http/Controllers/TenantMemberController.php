<?php

namespace App\Http\Controllers;

use App\Enums\TenantRole;
use App\Models\TenantInvitation;
use App\Models\User;
use App\Services\Tenancy\CurrentTenant;
use App\Services\Tenancy\TenantAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TenantMemberController extends Controller
{
    public function index(Request $request, CurrentTenant $currentTenant, TenantAccess $access): JsonResponse
    {
        $tenant = $currentTenant->forUser($request->user());
        $access->requireOwner($request->user(), $tenant);
        $members = $tenant->users()->orderBy('users.name')->get(['users.id', 'users.name', 'users.email']);
        $invitations = $tenant->invitations()->whereNull('accepted_at')->where('expires_at', '>', now())
            ->latest()->get(['public_id', 'email', 'role', 'expires_at', 'created_at']);

        return response()->json(['members' => $members, 'invitations' => $invitations]);
    }

    public function invite(Request $request, CurrentTenant $currentTenant, TenantAccess $access): JsonResponse
    {
        $tenant = $currentTenant->forUser($request->user());
        $access->requireOwner($request->user(), $tenant);
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', Rule::enum(TenantRole::class), Rule::notIn([TenantRole::Owner->value])],
        ]);
        if ($tenant->users()->where('users.email', $data['email'])->exists()) {
            throw ValidationException::withMessages(['email' => 'That user is already a workspace member.']);
        }

        $token = Str::random(64);
        $tenant->invitations()->where('email', $data['email'])->whereNull('accepted_at')->delete();
        /** @var TenantInvitation $invitation */
        $invitation = $tenant->invitations()->create([
            'invited_by' => $request->user()->id,
            'email' => Str::lower($data['email']),
            'role' => $data['role'],
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDays(7),
        ]);

        return response()->json([
            'invitation' => $invitation,
            'accept_url' => url("/invitations/{$invitation->public_id}?token={$token}"),
        ], 201);
    }

    public function update(Request $request, int $userId, CurrentTenant $currentTenant, TenantAccess $access): JsonResponse
    {
        $tenant = $currentTenant->forUser($request->user());
        $access->requireOwner($request->user(), $tenant);
        $data = $request->validate(['role' => ['required', Rule::enum(TenantRole::class)]]);
        $member = $tenant->users()->whereKey($userId)->firstOrFail();
        $this->protectLastOwner($tenant->id, $member, TenantRole::from($data['role']));
        $tenant->users()->updateExistingPivot($member->id, ['role' => $data['role']]);

        return response()->json(['member' => $tenant->users()->whereKey($member->id)->firstOrFail()]);
    }

    public function destroy(Request $request, int $userId, CurrentTenant $currentTenant, TenantAccess $access): JsonResponse
    {
        $tenant = $currentTenant->forUser($request->user());
        $access->requireOwner($request->user(), $tenant);
        $member = $tenant->users()->whereKey($userId)->firstOrFail();
        $this->protectLastOwner($tenant->id, $member, null);
        $tenant->users()->detach($member->id);

        return response()->json(['removed' => true]);
    }

    public function accept(Request $request, string $publicId): JsonResponse
    {
        $data = $request->validate(['token' => ['required', 'string', 'size:64']]);
        $invitation = DB::transaction(function () use ($publicId, $data, $request): TenantInvitation {
            $invitation = TenantInvitation::query()->where('public_id', $publicId)->lockForUpdate()->firstOrFail();
            if ($invitation->accepted_at || $invitation->expires_at->isPast() || ! hash_equals($invitation->token_hash, hash('sha256', $data['token']))) {
                throw ValidationException::withMessages(['invitation' => 'This invitation is invalid, expired, or already used.']);
            }
            if (Str::lower($request->user()->email) !== Str::lower($invitation->email)) {
                throw ValidationException::withMessages(['email' => 'Sign in with the email address that was invited.']);
            }

            $invitation->tenant->users()->syncWithoutDetaching([$request->user()->id => ['role' => $invitation->role->value]]);
            $invitation->update(['accepted_at' => now()]);

            return $invitation;
        });

        session(['current_tenant_id' => $invitation->tenant_id]);

        return response()->json(['tenant' => $invitation->tenant]);
    }

    private function protectLastOwner(int $tenantId, User $member, ?TenantRole $newRole): void
    {
        $currentRole = DB::table('tenant_user')->where('tenant_id', $tenantId)->where('user_id', $member->id)->value('role');
        if ($currentRole !== TenantRole::Owner->value || $newRole === TenantRole::Owner) {
            return;
        }
        $owners = DB::table('tenant_user')->where('tenant_id', $tenantId)->where('role', TenantRole::Owner->value)->count();
        if ($owners <= 1) {
            throw ValidationException::withMessages(['role' => 'A workspace must retain at least one owner.']);
        }
    }
}
