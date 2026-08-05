<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Services\Forms\FormService;
use App\Services\Tenancy\CurrentTenant;
use App\Services\Tenancy\TenantAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FormController extends Controller
{
    public function __construct(private readonly TenantAccess $access) {}

    public function index(Request $request, CurrentTenant $currentTenant): JsonResponse
    {
        $tenant = $currentTenant->forUser($request->user());
        $forms = $tenant->forms()->with('currentVersion:id,form_id,version_number')->latest('updated_at')->get();
        $tenants = $request->user()->tenants()->orderBy('tenants.name')->get(['tenants.id', 'tenants.name', 'tenants.slug']);

        return response()->json(['tenant' => $tenant, 'current_role' => $this->access->role($request->user(), $tenant), 'tenants' => $tenants, 'forms' => $forms]);
    }

    public function store(Request $request, CurrentTenant $currentTenant, FormService $service): JsonResponse
    {
        $data = $request->validate(['schema' => ['required', 'array']]);
        $tenant = $currentTenant->forUser($request->user());
        $this->access->requireManager($request->user(), $tenant);
        $form = $service->create($tenant, $request->user(), $data['schema']);

        return response()->json($form, 201);
    }

    public function show(Request $request, string $publicId, CurrentTenant $currentTenant): JsonResponse
    {
        $form = $this->ownedForm($request, $publicId, $currentTenant);
        $form->load(['tenant', 'currentVersion', 'publishedVersion']);

        return response()->json([...$form->toArray(), 'current_role' => $this->access->role($request->user(), $form->tenant)]);
    }

    public function update(Request $request, string $publicId, CurrentTenant $currentTenant, FormService $service): JsonResponse
    {
        $form = $this->ownedForm($request, $publicId, $currentTenant);
        $this->access->requireManager($request->user(), $form->tenant);
        $data = $request->validate(['schema' => ['required', 'array']]);
        $version = $service->save($form, $request->user(), $data['schema']);

        return response()->json(['form' => $form->fresh(), 'version' => $version]);
    }

    public function publish(Request $request, string $publicId, CurrentTenant $currentTenant, FormService $service): JsonResponse
    {
        $form = $this->ownedForm($request, $publicId, $currentTenant);
        $this->access->requireManager($request->user(), $form->tenant);

        return response()->json($service->publish($form));
    }

    public function unpublish(Request $request, string $publicId, CurrentTenant $currentTenant, FormService $service): JsonResponse
    {
        $form = $this->ownedForm($request, $publicId, $currentTenant);
        $this->access->requireManager($request->user(), $form->tenant);

        return response()->json($service->unpublish($form));
    }

    public function archive(Request $request, string $publicId, CurrentTenant $currentTenant, FormService $service): JsonResponse
    {
        $form = $this->ownedForm($request, $publicId, $currentTenant);
        $this->access->requireManager($request->user(), $form->tenant);

        return response()->json($service->archive($form));
    }

    public function restore(Request $request, string $publicId, CurrentTenant $currentTenant, FormService $service): JsonResponse
    {
        $form = $this->ownedForm($request, $publicId, $currentTenant);
        $this->access->requireManager($request->user(), $form->tenant);

        return response()->json($service->restore($form));
    }

    public function submissions(Request $request, string $publicId, CurrentTenant $currentTenant): JsonResponse
    {
        $form = $this->ownedForm($request, $publicId, $currentTenant);
        $submissions = $form->submissions()
            ->when($request->string('search')->isNotEmpty(), fn ($query) => $query->where('search_text', 'like', '%'.$request->string('search')->value().'%'))
            ->latest('submitted_at')
            ->paginate(min(100, max(1, $request->integer('per_page', 25))));

        return response()->json($submissions);
    }

    public function submission(Request $request, string $publicId, string $submissionId, CurrentTenant $currentTenant): JsonResponse
    {
        $form = $this->ownedForm($request, $publicId, $currentTenant);

        return response()->json($form->submissions()->with(['version:id,version_number', 'files:id,public_id,form_submission_id,field_key,original_name,mime_type,size_bytes'])->where('public_id', $submissionId)->firstOrFail());
    }

    public function download(Request $request, string $publicId, string $submissionId, string $fileId, CurrentTenant $currentTenant): StreamedResponse
    {
        $form = $this->ownedForm($request, $publicId, $currentTenant);
        $submission = $form->submissions()->where('public_id', $submissionId)->firstOrFail();
        $file = $submission->files()->where('public_id', $fileId)->firstOrFail();

        return Storage::disk($file->disk)->download($file->path, $file->original_name, ['Content-Type' => $file->mime_type]);
    }

    public function export(Request $request, string $publicId, CurrentTenant $currentTenant): StreamedResponse
    {
        $form = $this->ownedForm($request, $publicId, $currentTenant);
        $keys = [];
        foreach ($form->versions()->get() as $version) {
            foreach ($version->schema_json['steps'] as $step) {
                foreach ($step['sections'] as $section) {
                    foreach ($section['fields'] as $field) {
                        if ($field['type'] !== 'heading') {
                            $keys[$field['key']] = true;
                        }
                    }
                }
            }
        }
        $keys = array_keys($keys);

        return response()->streamDownload(function () use ($form, $keys): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['submission_id', 'submitted_at', ...$keys]);
            $form->submissions()->oldest('id')->chunkById(500, function ($submissions) use ($handle, $keys): void {
                foreach ($submissions as $submission) {
                    fputcsv($handle, [$submission->public_id, (string) $submission->submitted_at, ...array_map(fn ($key) => is_array($submission->data_json[$key] ?? null) ? json_encode($submission->data_json[$key]) : $submission->data_json[$key] ?? '', $keys)]);
                }
            });
            fclose($handle);
        }, $form->slug.'-responses.csv', ['Content-Type' => 'text/csv']);
    }

    public function versions(Request $request, string $publicId, CurrentTenant $currentTenant): JsonResponse
    {
        $form = $this->ownedForm($request, $publicId, $currentTenant);

        return response()->json($form->versions()->with('creator:id,name')->latest('version_number')->get(['id', 'form_id', 'version_number', 'schema_checksum', 'created_by', 'created_at']));
    }

    public function rollback(Request $request, string $publicId, int $versionNumber, CurrentTenant $currentTenant, FormService $service): JsonResponse
    {
        $form = $this->ownedForm($request, $publicId, $currentTenant);
        $this->access->requireManager($request->user(), $form->tenant);
        $source = $form->versions()->where('version_number', $versionNumber)->firstOrFail();
        $version = $service->save($form, $request->user(), $source->schema_json);

        return response()->json(['form' => $form->fresh(), 'version' => $version]);
    }

    private function ownedForm(Request $request, string $publicId, CurrentTenant $currentTenant): Form
    {
        $tenant = $currentTenant->forUser($request->user());

        /** @var Form $form */
        $form = $tenant->forms()->where('public_id', $publicId)->firstOrFail();

        return $form;
    }
}
