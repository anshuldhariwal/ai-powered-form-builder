<?php

namespace App\Jobs;

use App\Models\FormImport;
use App\Services\Forms\FormSchemaValidator;
use App\Services\Imports\DocumentImportParser;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ParseFormImport implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(public int $importId) {}

    public function handle(DocumentImportParser $parser, FormSchemaValidator $validator): void
    {
        $import = FormImport::findOrFail($this->importId);
        $import->update(['status' => 'processing']);
        try {
            $result = $parser->parse(Storage::disk($import->disk)->path($import->path), $import->original_name);
            $validator->validate($result['schema']);
            $import->update(['status' => 'ready', 'candidate_schema' => $result['schema'], 'warnings' => $result['warnings']]);
        } catch (Throwable $exception) {
            $import->update(['status' => 'failed', 'error_message' => Str::limit($exception->getMessage(), 1000)]);
            throw $exception;
        }
    }
}
