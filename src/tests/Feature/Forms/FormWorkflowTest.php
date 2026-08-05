<?php

use App\Models\Form;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Forms\FormService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function validFormSchema(string $title = 'Application form'): array
{
    $contents = file_get_contents(base_path('../contracts/examples/internship-application.json'));

    $schema = json_decode($contents ?: '', true, 512, JSON_THROW_ON_ERROR);
    $schema['form']['title'] = $title;

    return $schema;
}

function userWithTenant(): array
{
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $user->tenants()->attach($tenant, ['role' => 'owner']);

    return [$user, $tenant];
}

it('creates versions without duplicating identical schemas', function () {
    [$user, $tenant] = userWithTenant();
    $service = app(FormService::class);
    $schema = validFormSchema();

    $form = $service->create($tenant, $user, $schema);
    $same = $service->save($form, $user, $schema);
    $schema['form']['title'] = 'Updated application';
    $next = $service->save($form, $user, $schema);

    expect($same->version_number)->toBe(1)
        ->and($next->version_number)->toBe(2)
        ->and($form->versions()->count())->toBe(2);
});

it('isolates authenticated forms by tenant', function () {
    [$owner, $tenant] = userWithTenant();
    [$outsider] = userWithTenant();
    $form = app(FormService::class)->create($tenant, $owner, validFormSchema());

    $this->actingAs($outsider)->getJson('/api/forms/'.$form->public_id)->assertNotFound();
    $this->actingAs($owner)->getJson('/api/forms/'.$form->public_id)->assertOk();
});

it('serves only published forms and stores submissions against the published version', function () {
    [$user, $tenant] = userWithTenant();
    $service = app(FormService::class);
    $form = $service->create($tenant, $user, validFormSchema());
    $url = "/api/public/forms/{$tenant->slug}/{$form->slug}";

    $this->getJson($url)->assertNotFound();
    $service->publish($form);
    $this->getJson($url)->assertOk()->assertJsonPath('schema.form.title', 'Application form');

    $client = $this->withSession(['_token' => 'public-form-test-token'])
        ->withHeader('X-CSRF-TOKEN', 'public-form-test-token');
    $client->postJson($url, ['answers' => []])->assertUnprocessable()->assertJsonValidationErrors('full_name');
    $client->postJson($url, ['answers' => ['full_name' => 'Ada Lovelace']])->assertCreated();

    /** @var Form $fresh */
    $fresh = $form->fresh();
    expect($fresh->submissions)->toHaveCount(1)
        ->and($fresh->submissions->first()->form_version_id)->toBe($fresh->published_version_id);
});

it('unpublishes archives and restores forms without losing versions', function () {
    [$user, $tenant] = userWithTenant();
    $service = app(FormService::class);
    $form = $service->create($tenant, $user, validFormSchema());
    $url = "/api/public/forms/{$tenant->slug}/{$form->slug}";
    $client = $this->actingAs($user)->withSession(['_token' => 'form-lifecycle-token'])
        ->withHeader('X-CSRF-TOKEN', 'form-lifecycle-token');

    $service->publish($form);
    $client->postJson("/api/forms/{$form->public_id}/unpublish")
        ->assertOk()->assertJsonPath('status', 'draft')->assertJsonPath('published_at', null);
    $this->getJson($url)->assertNotFound();

    $service->publish($form->fresh());
    $client->postJson("/api/forms/{$form->public_id}/archive")
        ->assertOk()->assertJsonPath('status', 'archived')->assertJsonPath('published_version_id', null);
    $this->getJson($url)->assertNotFound();

    $client->putJson("/api/forms/{$form->public_id}", ['schema' => validFormSchema('Blocked edit')])
        ->assertUnprocessable()->assertJsonValidationErrors('form');
    $client->postJson("/api/forms/{$form->public_id}/publish")
        ->assertUnprocessable()->assertJsonValidationErrors('form');

    $client->postJson("/api/forms/{$form->public_id}/restore")
        ->assertOk()->assertJsonPath('status', 'draft');

    expect($form->versions()->count())->toBe(1);
});
