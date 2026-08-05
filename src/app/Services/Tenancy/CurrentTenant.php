<?php

namespace App\Services\Tenancy;

use App\Models\Tenant;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CurrentTenant
{
    public function forUser(User $user): Tenant
    {
        $query = $user->tenants()->orderBy('tenants.id');
        $selectedTenantId = session('current_tenant_id');
        $tenant = $selectedTenantId ? (clone $query)->whereKey($selectedTenantId)->first() : null;

        $tenant ??= $query->first();

        if ($tenant && $tenant->id !== $selectedTenantId) {
            session(['current_tenant_id' => $tenant->id]);
        }

        return $tenant ?? throw new AccessDeniedHttpException('No tenant membership is available.');
    }

    public function selectForUser(User $user, string $slug): Tenant
    {
        $tenant = $user->tenants()->where('tenants.slug', $slug)->first()
            ?? throw new AccessDeniedHttpException('You do not belong to that workspace.');

        session(['current_tenant_id' => $tenant->id]);

        return $tenant;
    }
}
