<?php

namespace App\Http\Controllers;

use App\Enums\FormStatus;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\FormVersion;
use App\Models\SubmissionFile;
use App\Services\Forms\SubmissionValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PublicFormController extends Controller
{
    public function show(string $tenantSlug, string $formSlug): JsonResponse
    {
        $form = $this->publishedForm($tenantSlug, $formSlug);
        /** @var FormVersion $version */
        $version = $form->publishedVersion;

        return response()->json([
            'title' => $form->title,
            'schema' => $version->schema_json,
        ]);
    }

    public function store(Request $request, string $tenantSlug, string $formSlug, SubmissionValidator $validator): JsonResponse
    {
        $form = $this->publishedForm($tenantSlug, $formSlug);
        /** @var FormVersion $version */
        $version = $form->publishedVersion;
        $schema = $version->schema_json;
        $answers = $request->input('answers', []);
        if (is_string($answers)) {
            $answers = json_decode($answers, true);
        }
        if (! is_array($answers)) {
            throw ValidationException::withMessages(['answers' => 'Answers must be an object.']);
        }

        $fileFields = [];
        foreach ($schema['steps'] as $step) {
            foreach ($step['sections'] as $section) {
                foreach ($section['fields'] as $field) {
                    if ($field['type'] === 'file') {
                        $fileFields[$field['key']] = $field;
                    }
                }
            }
        }
        foreach ($request->file('files', []) as $key => $file) {
            $field = $fileFields[$key] ?? null;
            if ($field === null || ! $file->isValid()) {
                throw ValidationException::withMessages([$key => 'Invalid file upload.']);
            }
            $rules = $field['validation'];
            if ($rules['max_file_size_kb'] !== null && $file->getSize() > $rules['max_file_size_kb'] * 1024) {
                throw ValidationException::withMessages([$key => "{$field['label']} is too large."]);
            }
            $allowed = array_map('strtolower', $rules['allowed_file_types']);
            if ($allowed !== [] && ! in_array(strtolower($file->getClientOriginalExtension()), $allowed, true) && ! in_array(strtolower((string) $file->getMimeType()), $allowed, true)) {
                throw ValidationException::withMessages([$key => "{$field['label']} has an unsupported file type."]);
            }
            $answers[$key] = $file->getClientOriginalName();
        }

        $clean = $validator->validate($schema, $answers);
        $submission = FormSubmission::create([
            'form_id' => $form->id,
            'form_version_id' => $form->published_version_id,
            'data_json' => $clean,
            'search_text' => implode(' ', array_map(fn ($value) => is_array($value) ? implode(' ', $value) : (string) $value, $clean)),
            'submitted_at' => now(),
        ]);

        foreach ($request->file('files', []) as $key => $file) {
            $path = $file->store("submissions/{$submission->public_id}", 'local');
            SubmissionFile::create([
                'form_submission_id' => $submission->id,
                'field_key' => $key,
                'disk' => 'local',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                'size_bytes' => $file->getSize(),
            ]);
        }

        return response()->json(['message' => $schema['form']['success_message'], 'submission_id' => $submission->public_id], 201);
    }

    private function publishedForm(string $tenantSlug, string $formSlug): Form
    {
        return Form::query()->whereHas('tenant', fn ($query) => $query->where('slug', $tenantSlug))
            ->where('slug', $formSlug)
            ->where('status', FormStatus::Published)
            ->whereNotNull('published_version_id')
            ->with('publishedVersion')
            ->firstOrFail();
    }
}
