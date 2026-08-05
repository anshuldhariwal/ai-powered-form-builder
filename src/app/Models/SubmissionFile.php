<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['form_submission_id', 'field_key', 'disk', 'path', 'original_name', 'mime_type', 'size_bytes'])]
class SubmissionFile extends Model
{
    protected static function booted(): void
    {
        static::creating(function (SubmissionFile $file): void {
            $file->public_id ??= (string) Str::ulid();
        });
    }

    /** @return BelongsTo<FormSubmission, $this> */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(FormSubmission::class, 'form_submission_id');
    }
}
