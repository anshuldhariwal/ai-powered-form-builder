<?php

namespace App\Models;

use App\Enums\TenantRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property TenantRole $role
 * @property Carbon $expires_at
 * @property Carbon|null $accepted_at
 */
#[Fillable(['tenant_id', 'invited_by', 'email', 'role', 'token_hash', 'expires_at', 'accepted_at'])]
class TenantInvitation extends Model
{
    protected static function booted(): void
    {
        static::creating(function (TenantInvitation $invitation): void {
            $invitation->public_id ??= (string) Str::ulid();
        });
    }

    protected function casts(): array
    {
        return ['role' => TenantRole::class, 'expires_at' => 'datetime', 'accepted_at' => 'datetime'];
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return BelongsTo<User, $this> */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}
