<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['form_id', 'form_version_id', 'data_json', 'search_text', 'submitted_at'])]
class FormSubmission extends Model
{
    protected static function booted(): void
    {
        static::creating(function (FormSubmission $submission): void {
            $submission->public_id ??= (string) Str::ulid();
        });
    }

    protected function casts(): array
    {
        return ['data_json' => 'array', 'submitted_at' => 'datetime'];
    }

    /** @return BelongsTo<Form, $this> */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    /** @return BelongsTo<FormVersion, $this> */
    public function version(): BelongsTo
    {
        return $this->belongsTo(FormVersion::class, 'form_version_id');
    }
}
