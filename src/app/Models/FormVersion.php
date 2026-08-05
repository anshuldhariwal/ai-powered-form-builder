<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/** @property array<string, mixed> $schema_json */
#[Fillable(['form_id', 'version_number', 'schema_json', 'schema_checksum', 'change_summary', 'created_by'])]
class FormVersion extends Model
{
    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Form versions are immutable.'));
        static::deleting(fn () => throw new LogicException('Form versions are immutable.'));
    }

    protected function casts(): array
    {
        return ['schema_json' => 'array'];
    }

    /** @return BelongsTo<Form, $this> */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
