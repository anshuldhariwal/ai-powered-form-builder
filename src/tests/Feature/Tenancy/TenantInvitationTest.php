<?php

use App\Enums\TenantRole;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function csrfClientFor(User $user, string $token = 'workspace-members-token')
{
    return test()->actingAs($user)->withSession(['_token' => $token])->withHeader('X-CSRF-TOKEN', $token);
}

function invitationTestSchema(string $title): array
{
    $contents = file_get_contents(base_path('../contracts/examples/internship-application.json'));
    $schema = json_decode($contents ?: '', true, 512, JSON_THROW_ON_ERROR);
    $schema['form']['title'] = $title;

    return $schema;
}

it('creates and accepts a secure single-use workspace invitation', function () {
    $owner = User::factory()->create();
    $invitee = User::factory()->create(['email' => 'editor@example.com']);
    $tenant = Tenant::factory()->create(['name' => 'Shared workspace']);
    $owner->tenants()->attach($tenant, ['role' => TenantRole::Owner->value]);

    $response = csrfClientFor($owner)->postJson('/api/tenant/invitations', [
        'email' => $invitee->email,
        'role' => TenantRole::Editor->value,
    ])->assertCreated()->assertJsonPath('invitation.email', $invitee->email);

    $url = $response->json('accept_url');
    parse_str(parse_url($url, PHP_URL_QUERY), $query);
    $publicId = basename(parse_url($url, PHP_URL_PATH));

    csrfClientFor($invitee, 'accept-invite-token')->postJson("/api/invitations/{$publicId}/accept", ['token' => $query['token']])
        ->assertOk()->assertJsonPath('tenant.id', $tenant->id);

    expect($invitee->tenants()->whereKey($tenant->id)->first()?->pivot?->role)->toBe(TenantRole::Editor);
    csrfClientFor($invitee, 'accept-invite-token')->postJson("/api/invitations/{$publicId}/accept", ['token' => $query['token']])
        ->assertUnprocessable()->assertJsonValidationErrors('invitation');
});

it('allows only owners to administer membership and retains a last owner', function () {
    $owner = User::factory()->create();
    $editor = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $owner->tenants()->attach($tenant, ['role' => TenantRole::Owner->value]);
    $editor->tenants()->attach($tenant, ['role' => TenantRole::Editor->value]);

    csrfClientFor($editor)->getJson('/api/tenant/members')->assertForbidden();
    csrfClientFor($editor)->postJson('/api/tenant/invitations', ['email' => 'new@example.com', 'role' => 'viewer'])->assertForbidden();
    csrfClientFor($owner)->patchJson("/api/tenant/members/{$owner->id}", ['role' => 'editor'])
        ->assertUnprocessable()->assertJsonValidationErrors('role');
    csrfClientFor($owner)->deleteJson("/api/tenant/members/{$editor->id}")->assertOk();

    expect($tenant->users()->whereKey($editor->id)->exists())->toBeFalse();
});

it('enforces viewer read-only access while editors can manage forms', function () {
    $owner = User::factory()->create();
    $editor = User::factory()->create();
    $viewer = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $owner->tenants()->attach($tenant, ['role' => TenantRole::Owner->value]);
    $editor->tenants()->attach($tenant, ['role' => TenantRole::Editor->value]);
    $viewer->tenants()->attach($tenant, ['role' => TenantRole::Viewer->value]);
    $schema = invitationTestSchema('Shared form');

    csrfClientFor($viewer)->getJson('/api/forms')->assertOk()->assertJsonPath('current_role', 'viewer');
    csrfClientFor($viewer)->postJson('/api/forms', ['schema' => $schema])->assertForbidden();
    csrfClientFor($editor)->postJson('/api/forms', ['schema' => $schema])->assertCreated();
});
