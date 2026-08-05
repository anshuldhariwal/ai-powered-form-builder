<?php

namespace App\Models;

use App\Enums\TenantRole;
use Illuminate\Database\Eloquent\Relations\Pivot;

class TenantMembership extends Pivot
{
    public $incrementing = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => TenantRole::class,
        ];
    }
}
