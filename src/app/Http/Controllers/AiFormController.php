<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateAiForm;
use App\Models\AiRequest;
use App\Services\Forms\FormService;
use App\Services\Tenancy\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiFormController extends Controller
{
    public function generate(Request $request, CurrentTenant $currentTenant): JsonResponse
    {
        $data = $request->validate(['prompt' => ['required', 'string', 'min:5', 'max:4000']]);
        $tenant = $currentTenant->forUser($request->user());
        $aiRequest = AiRequest::create(['tenant_id' => $tenant->id, 'user_id' => $request->user()->id, 'operation' => 'generate', 'prompt' => $data['prompt'], 'status' => 'queued']);
        GenerateAiForm::dispatch($aiRequest->id);

        return response()->json($aiRequest->fresh(), 202);
    }

    public function edit(Request $request, string $publicId, CurrentTenant $currentTenant): JsonResponse
    {
        $data = $request->validate(['prompt' => ['required', 'string', 'min:3', 'max:4000']]);
        $tenant = $currentTenant->forUser($request->user());
        $form = $tenant->forms()->where('public_id', $publicId)->with('currentVersion')->firstOrFail();
        $aiRequest = AiRequest::create(['tenant_id' => $tenant->id, 'user_id' => $request->user()->id, 'form_id' => $form->id, 'operation' => 'edit', 'prompt' => $data['prompt'], 'input_schema' => $form->currentVersion->schema_json, 'status' => 'queued']);
        GenerateAiForm::dispatch($aiRequest->id);

        return response()->json($aiRequest->fresh(), 202);
    }

    public function show(Request $request, string $requestId, CurrentTenant $currentTenant): JsonResponse
    {
        $tenant = $currentTenant->forUser($request->user());

        return response()->json(AiRequest::where('tenant_id', $tenant->id)->where('public_id', $requestId)->firstOrFail());
    }

    public function accept(Request $request, string $requestId, CurrentTenant $currentTenant, FormService $forms): JsonResponse
    {
        $tenant = $currentTenant->forUser($request->user());
        $aiRequest = AiRequest::where('tenant_id', $tenant->id)->where('public_id', $requestId)->where('status', 'succeeded')->firstOrFail();
        /** @var array<string, mixed> $outputSchema */
        $outputSchema = $aiRequest->output_schema;
        $form = $aiRequest->form_id === null
            ? $forms->create($tenant, $request->user(), $outputSchema)
            : $tenant->forms()->findOrFail($aiRequest->form_id);
        if ($aiRequest->form_id !== null) {
            $forms->save($form, $request->user(), $outputSchema);
        }

        return response()->json($form->fresh('currentVersion'));
    }
}
