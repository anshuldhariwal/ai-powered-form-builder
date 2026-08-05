<?php

namespace App\Models;

use App\Enums\FormStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable(['tenant_id', 'created_by', 'title', 'slug', 'status', 'current_version_id', 'published_version_id', 'published_at'])]
class Form extends Model
{
    use SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (Form $form): void {
            $form->public_id ??= (string) Str::ulid();
        });
        static::updating(function (Form $form): void {
            if ($form->isDirty('slug')) {
                throw new \LogicException('Form slugs are immutable.');
            }
        });
    }

    protected function casts(): array
    {
        return ['status' => FormStatus::class, 'published_at' => 'datetime'];
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<FormVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(FormVersion::class);
    }

    /** @return BelongsTo<FormVersion, $this> */
    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(FormVersion::class, 'current_version_id');
    }

    /** @return BelongsTo<FormVersion, $this> */
    public function publishedVersion(): BelongsTo
    {
        return $this->belongsTo(FormVersion::class, 'published_version_id');
    }

    /** @return HasMany<FormSubmission, $this> */
    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }
}
