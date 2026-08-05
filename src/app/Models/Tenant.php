<?php

namespace App\Models;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

#[Fillable(['name', 'slug'])]
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::updating(function (Tenant $tenant): void {
            if ($tenant->isDirty('slug')) {
                throw new LogicException('Tenant slugs are immutable.');
            }
        });
    }

    /**
     * @return BelongsToMany<User, $this, TenantMembership>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(TenantMembership::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /** @return HasMany<Form, $this> */
    public function forms(): HasMany
    {
        return $this->hasMany(Form::class);
    }

    /** @return HasMany<TenantInvitation, $this> */
    public function invitations(): HasMany
    {
        return $this->hasMany(TenantInvitation::class);
    }
}
