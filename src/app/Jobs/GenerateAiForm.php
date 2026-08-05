<?php

namespace App\Jobs;

use App\Models\AiRequest;
use App\Services\Ai\AiFormGenerator;
use App\Services\Forms\FormSchemaValidator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;
use Throwable;

class GenerateAiForm implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 60;

    public function __construct(public int $requestId) {}

    public function handle(AiFormGenerator $generator, FormSchemaValidator $validator): void
    {
        $request = AiRequest::findOrFail($this->requestId);
        $started = hrtime(true);
        $request->update(['status' => 'processing', 'attempts' => $request->attempts + 1]);
        try {
            /** @var array<string, mixed>|null $inputSchema */
            $inputSchema = $request->input_schema;
            $schema = $generator->generate($request->prompt, $inputSchema);
            $validator->validate($schema);
            $request->update(['status' => 'succeeded', 'output_schema' => $schema, 'latency_ms' => (int) ((hrtime(true) - $started) / 1_000_000), 'input_tokens' => str_word_count($request->prompt), 'output_tokens' => str_word_count(json_encode($schema) ?: '')]);
        } catch (Throwable $exception) {
            $request->update(['status' => 'failed', 'error_message' => Str::limit($exception->getMessage(), 1000), 'latency_ms' => (int) ((hrtime(true) - $started) / 1_000_000)]);
            throw $exception;
        }
    }
}
