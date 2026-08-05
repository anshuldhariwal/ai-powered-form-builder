<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable(['tenant_id', 'user_id', 'form_id', 'operation', 'status', 'prompt', 'input_schema', 'output_schema', 'error_message', 'provider', 'model', 'input_tokens', 'output_tokens', 'latency_ms', 'attempts'])]
class AiRequest extends Model
{
    protected static function booted(): void
    {
        static::creating(function (AiRequest $request): void {
            $request->public_id ??= (string) Str::ulid();
        });
    }

    protected function casts(): array
    {
        return ['input_schema' => 'array', 'output_schema' => 'array'];
    }
}
