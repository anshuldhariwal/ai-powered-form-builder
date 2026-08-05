<?php

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('does not allow a tenant slug to change', function () {
    $tenant = Tenant::factory()->create(['slug' => 'stable-workspace']);

    $tenant->slug = 'changed-workspace';

    expect(fn () => $tenant->save())->toThrow(
        LogicException::class,
        'Tenant slugs are immutable.',
    );
});
