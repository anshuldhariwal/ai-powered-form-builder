<?php

use App\Enums\TenantRole;

it('allows only owners to administer a tenant', function (TenantRole $role, bool $allowed) {
    expect($role->canAdministerTenant())->toBe($allowed);
})->with([
    'owner' => [TenantRole::Owner, true],
    'editor' => [TenantRole::Editor, false],
    'viewer' => [TenantRole::Viewer, false],
]);

it('allows owners and editors to manage tenant resources', function (TenantRole $role, bool $allowed) {
    expect($role->canManageResources())->toBe($allowed);
})->with([
    'owner' => [TenantRole::Owner, true],
    'editor' => [TenantRole::Editor, true],
    'viewer' => [TenantRole::Viewer, false],
]);

it('allows every tenant role to view tenant resources', function (TenantRole $role) {
    expect($role->canViewResources())->toBeTrue();
})->with(TenantRole::cases());
