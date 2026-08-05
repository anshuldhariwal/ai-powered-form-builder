<?php

namespace App\Services\Tenancy;

use App\Enums\TenantRole;
use App\Models\Tenant;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class TenantAccess
{
    public function role(User $user, Tenant $tenant): TenantRole
    {
        $membership = $user->tenants()->whereKey($tenant->id)->first();
        $role = $membership?->pivot?->getAttribute('role');

        return $role instanceof TenantRole
            ? $role
            : throw new AccessDeniedHttpException('You do not belong to this workspace.');
    }

    public function requireManager(User $user, Tenant $tenant): TenantRole
    {
        $role = $this->role($user, $tenant);
        if (! $role->canManageResources()) {
            throw new AccessDeniedHttpException('This action requires an owner or editor role.');
        }

        return $role;
    }

    public function requireOwner(User $user, Tenant $tenant): TenantRole
    {
        $role = $this->role($user, $tenant);
        if (! $role->canAdministerTenant()) {
            throw new AccessDeniedHttpException('This action requires the owner role.');
        }

        return $role;
    }
}
