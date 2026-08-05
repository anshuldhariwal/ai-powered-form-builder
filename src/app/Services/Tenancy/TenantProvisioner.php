<?php

namespace App\Services\Tenancy;

use App\Enums\TenantRole;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use RuntimeException;

class TenantProvisioner
{
    private const MAX_ATTEMPTS = 5;

    public function createPersonalTenant(User $user): Tenant
    {
        $name = $user->name."'s Workspace";
        $baseSlug = Str::slug($name) ?: 'workspace';

        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            $slug = $attempt === 0
                ? Str::limit($baseSlug, 255, '')
                : $this->withCollisionSuffix($baseSlug);

            try {
                $tenant = Tenant::create([
                    'name' => $name,
                    'slug' => $slug,
                ]);
            } catch (QueryException $exception) {
                if ($attempt === self::MAX_ATTEMPTS - 1) {
                    throw $exception;
                }

                continue;
            }

            $tenant->users()->attach($user, ['role' => TenantRole::Owner->value]);

            return $tenant;
        }

        throw new RuntimeException('Unable to allocate a unique tenant slug.');
    }

    private function withCollisionSuffix(string $baseSlug): string
    {
        $suffix = Str::lower(Str::substr((string) Str::ulid(), -8));

        return Str::limit($baseSlug, 246, '').'-'.$suffix;
    }
}
