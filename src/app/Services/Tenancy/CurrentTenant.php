<?php

namespace App\Services\Tenancy;

use App\Models\Tenant;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CurrentTenant
{
    public function forUser(User $user): Tenant
    {
        $tenant = $user->tenants()->orderBy('tenants.id')->first();

        return $tenant ?? throw new AccessDeniedHttpException('No tenant membership is available.');
    }
}
