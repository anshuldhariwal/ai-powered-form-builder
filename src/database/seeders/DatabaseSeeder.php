<?php

namespace Database\Seeders;

use App\Enums\FormStatus;
use App\Enums\TenantRole;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Forms\FormService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'demo@formforge.test'],
            ['name' => 'Demo User', 'password' => Hash::make(env('DEMO_PASSWORD', 'password'))],
        );
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'formforge-demo'],
            ['name' => 'FormForge Demo'],
        );
        $tenant->users()->syncWithoutDetaching([$user->id => ['role' => TenantRole::Owner->value]]);

        $service = app(FormService::class);
        foreach (['internship-application.json', 'customer-feedback.json'] as $fixture) {
            $contents = file_get_contents(base_path('../contracts/examples/'.$fixture));
            $schema = json_decode($contents ?: '', true, 512, JSON_THROW_ON_ERROR);
            $slug = Str::slug($schema['form']['title']);
            $form = $tenant->forms()->where('slug', $slug)->first()
                ?? $service->create($tenant, $user, $schema);

            if ($form->status !== FormStatus::Published) {
                $service->publish($form);
            }
        }
    }
}
