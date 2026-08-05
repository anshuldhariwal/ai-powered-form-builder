<?php

use App\Enums\TenantRole;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Services\Forms\FormService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function tenantTestSchema(string $title): array
{
    $contents = file_get_contents(base_path('../contracts/examples/internship-application.json'));
    $schema = json_decode($contents ?: '', true, 512, JSON_THROW_ON_ERROR);
    $schema['form']['title'] = $title;

    return $schema;
}

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

it('switches the active workspace and scopes form lists to it', function () {
    $user = User::factory()->create();
    $first = Tenant::factory()->create(['name' => 'First workspace']);
    $second = Tenant::factory()->create(['name' => 'Second workspace']);
    $outsider = Tenant::factory()->create();
    $user->tenants()->attach($first, ['role' => TenantRole::Owner->value]);
    $user->tenants()->attach($second, ['role' => TenantRole::Editor->value]);
    app(FormService::class)->create($first, $user, tenantTestSchema('First form'));
    app(FormService::class)->create($second, $user, tenantTestSchema('Second form'));
    $client = $this->actingAs($user)->withSession(['_token' => 'tenant-switch-token'])
        ->withHeader('X-CSRF-TOKEN', 'tenant-switch-token');

    $client->postJson("/api/tenants/{$second->slug}/switch")
        ->assertOk()->assertJsonPath('tenant.slug', $second->slug);

    $client->getJson('/api/forms')->assertOk()
        ->assertJsonPath('tenant.slug', $second->slug)
        ->assertJsonCount(2, 'tenants')
        ->assertJsonCount(1, 'forms')
        ->assertJsonPath('forms.0.title', 'Second form');

    $client->postJson("/api/tenants/{$outsider->slug}/switch")->assertForbidden();
});
