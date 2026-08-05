<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable(['tenant_id', 'user_id', 'status', 'disk', 'path', 'original_name', 'candidate_schema', 'warnings', 'error_message'])]
class FormImport extends Model
{
    protected static function booted(): void
    {
        static::creating(function (FormImport $import): void {
            $import->public_id ??= (string) Str::ulid();
        });
    }

    protected function casts(): array
    {
        return ['candidate_schema' => 'array', 'warnings' => 'array'];
    }
}
