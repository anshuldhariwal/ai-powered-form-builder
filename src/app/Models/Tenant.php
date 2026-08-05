<?php

namespace App\Models;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'slug'])]
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

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
}
