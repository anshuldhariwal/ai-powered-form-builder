<?php

namespace App\Services\Forms;

use App\Enums\FormStatus;
use App\Models\Form;
use App\Models\FormVersion;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FormService
{
    public function __construct(
        private readonly FormSchemaValidator $validator,
        private readonly FormSchemaCanonicalizer $canonicalizer,
    ) {}

    /** @param array<string, mixed> $schema */
    public function create(Tenant $tenant, User $user, array $schema): Form
    {
        $this->validator->validate($schema);

        return DB::transaction(function () use ($tenant, $user, $schema): Form {
            $title = $schema['form']['title'];
            $form = Form::create([
                'tenant_id' => $tenant->id,
                'created_by' => $user->id,
                'title' => $title,
                'slug' => $this->uniqueSlug($tenant, $title),
                'status' => FormStatus::Draft,
            ]);
            $version = $this->insertVersion($form, $user, $schema, 1);
            $form->updateQuietly(['current_version_id' => $version->id]);

            return $form->fresh(['currentVersion']) ?? $form;
        });
    }

    /** @param array<string, mixed> $schema */
    public function save(Form $form, User $user, array $schema): FormVersion
    {
        $this->validator->validate($schema);
        $checksum = $this->canonicalizer->checksum($schema);

        return DB::transaction(function () use ($form, $user, $schema, $checksum): FormVersion {
            $locked = Form::query()->lockForUpdate()->findOrFail($form->id);
            /** @var FormVersion|null $current */
            $current = $locked->currentVersion;
            if ($current?->schema_checksum === $checksum) {
                return $current;
            }

            $next = ($locked->versions()->max('version_number') ?? 0) + 1;
            $version = $this->insertVersion($locked, $user, $schema, $next);
            $locked->updateQuietly(['current_version_id' => $version->id, 'title' => $schema['form']['title']]);

            return $version;
        });
    }

    public function publish(Form $form): Form
    {
        $form->update([
            'published_version_id' => $form->current_version_id,
            'status' => FormStatus::Published,
            'published_at' => now(),
        ]);

        return $form->fresh(['publishedVersion']) ?? $form;
    }

    /** @param array<string, mixed> $schema */
    private function insertVersion(Form $form, User $user, array $schema, int $number): FormVersion
    {
        return FormVersion::create([
            'form_id' => $form->id,
            'version_number' => $number,
            'schema_json' => $schema,
            'schema_checksum' => $this->canonicalizer->checksum($schema),
            'created_by' => $user->id,
        ]);
    }

    private function uniqueSlug(Tenant $tenant, string $title): string
    {
        $base = Str::slug($title) ?: 'form';
        $slug = Str::limit($base, 255, '');
        if (! $tenant->forms()->where('slug', $slug)->exists()) {
            return $slug;
        }

        return Str::limit($base, 246, '').'-'.Str::lower(Str::substr((string) Str::ulid(), -8));
    }
}
