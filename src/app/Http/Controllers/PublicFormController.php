<?php

namespace App\Http\Controllers;

use App\Enums\FormStatus;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\FormVersion;
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

    public function store(Request $request, string $tenantSlug, string $formSlug): JsonResponse
    {
        $form = $this->publishedForm($tenantSlug, $formSlug);
        /** @var FormVersion $version */
        $version = $form->publishedVersion;
        $schema = $version->schema_json;
        $answers = $request->input('answers', []);
        if (! is_array($answers)) {
            throw ValidationException::withMessages(['answers' => 'Answers must be an object.']);
        }

        $errors = [];
        $allowed = [];
        foreach ($schema['steps'] as $step) {
            foreach ($step['sections'] as $section) {
                foreach ($section['fields'] as $field) {
                    if ($field['type'] === 'heading') {
                        continue;
                    }
                    $key = $field['key'];
                    $allowed[$key] = true;
                    $value = $answers[$key] ?? null;
                    if ($field['required'] && ($value === null || $value === '' || $value === [])) {
                        $errors[$key] = "{$field['label']} is required.";
                    }
                    if ($value !== null && $value !== '') {
                        if ($field['type'] === 'email' && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                            $errors[$key] = "{$field['label']} must be a valid email.";
                        }
                        if ($field['type'] === 'number' && ! is_numeric($value)) {
                            $errors[$key] = "{$field['label']} must be a number.";
                        }
                        if (in_array($field['type'], ['select', 'radio'], true) && ! in_array($value, array_column($field['options'], 'value'), true)) {
                            $errors[$key] = "{$field['label']} has an invalid selection.";
                        }
                    }
                }
            }
        }
        foreach (array_keys($answers) as $key) {
            if (! isset($allowed[$key])) {
                $errors[$key] = 'Unknown field.';
            }
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $clean = array_intersect_key($answers, $allowed);
        $submission = FormSubmission::create([
            'form_id' => $form->id,
            'form_version_id' => $form->published_version_id,
            'data_json' => $clean,
            'search_text' => implode(' ', array_map(fn ($value) => is_array($value) ? implode(' ', $value) : (string) $value, $clean)),
            'submitted_at' => now(),
        ]);

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
