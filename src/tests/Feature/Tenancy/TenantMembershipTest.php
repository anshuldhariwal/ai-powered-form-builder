<?php

use App\Enums\TenantRole;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows a user to belong to multiple tenants with independent roles', function () {
    $user = User::factory()->create();
    $ownedTenant = Tenant::factory()->create();
    $viewedTenant = Tenant::factory()->create();

    $user->tenants()->attach($ownedTenant, ['role' => TenantRole::Owner->value]);
    $user->tenants()->attach($viewedTenant, ['role' => TenantRole::Viewer->value]);

    $memberships = $user->tenants()
        ->orderBy('tenants.id')
        ->get()
        ->mapWithKeys(fn (Tenant $tenant): array => [
            $tenant->id => $tenant->pivot?->role,
        ]);

    expect($memberships)
        ->toHaveCount(2)
        ->and($memberships[$ownedTenant->id])->toBe(TenantRole::Owner)
        ->and($memberships[$viewedTenant->id])->toBe(TenantRole::Viewer)
        ->and($ownedTenant->users()->first()?->pivot)->toBeInstanceOf(TenantMembership::class);
});

it('prevents duplicate memberships for the same tenant and user', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();

    $user->tenants()->attach($tenant, ['role' => TenantRole::Owner->value]);

    expect(fn () => $user->tenants()->attach($tenant, ['role' => TenantRole::Editor->value]))
        ->toThrow(QueryException::class);
});
