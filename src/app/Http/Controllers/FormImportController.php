<?php

namespace App\Http\Controllers;

use App\Jobs\ParseFormImport;
use App\Models\FormImport;
use App\Services\Forms\FormService;
use App\Services\Tenancy\CurrentTenant;
use App\Services\Tenancy\TenantAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FormImportController extends Controller
{
    public function __construct(private readonly TenantAccess $access) {}

    public function store(Request $request, CurrentTenant $currentTenant): JsonResponse
    {
        $data = $request->validate(['file' => ['required', 'file', 'max:10240', 'mimes:docx,xlsx']]);
        $tenant = $currentTenant->forUser($request->user());
        $this->access->requireManager($request->user(), $tenant);
        $file = $data['file'];
        $path = $file->store("imports/{$tenant->id}", 'local');
        $import = FormImport::create(['tenant_id' => $tenant->id, 'user_id' => $request->user()->id, 'status' => 'queued', 'disk' => 'local', 'path' => $path, 'original_name' => $file->getClientOriginalName()]);
        ParseFormImport::dispatch($import->id);

        return response()->json($import->fresh(), 202);
    }

    public function show(Request $request, string $importId, CurrentTenant $currentTenant): JsonResponse
    {
        $tenant = $currentTenant->forUser($request->user());

        return response()->json(FormImport::where('tenant_id', $tenant->id)->where('public_id', $importId)->firstOrFail());
    }

    public function commit(Request $request, string $importId, CurrentTenant $currentTenant, FormService $forms): JsonResponse
    {
        $tenant = $currentTenant->forUser($request->user());
        $this->access->requireManager($request->user(), $tenant);
        $import = FormImport::where('tenant_id', $tenant->id)->where('public_id', $importId)->where('status', 'ready')->firstOrFail();
        $data = $request->validate(['schema' => ['nullable', 'array']]);
        /** @var array<string, mixed> $schema */
        $schema = $data['schema'] ?? $import->candidate_schema;
        $form = $forms->create($tenant, $request->user(), $schema);
        $import->update(['status' => 'committed']);

        return response()->json($form, 201);
    }
}
