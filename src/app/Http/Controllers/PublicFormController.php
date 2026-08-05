<?php

namespace App\Http\Controllers;

use App\Enums\FormStatus;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\FormVersion;
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
        if (! is_array($answers)) {
            throw ValidationException::withMessages(['answers' => 'Answers must be an object.']);
        }

        $clean = $validator->validate($schema, $answers);
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
