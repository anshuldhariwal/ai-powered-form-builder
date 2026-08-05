<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Services\Forms\FormService;
use App\Services\Tenancy\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FormController extends Controller
{
    public function index(Request $request, CurrentTenant $currentTenant): JsonResponse
    {
        $tenant = $currentTenant->forUser($request->user());
        $forms = $tenant->forms()->with('currentVersion:id,form_id,version_number')->latest('updated_at')->get();

        return response()->json(['tenant' => $tenant, 'forms' => $forms]);
    }

    public function store(Request $request, CurrentTenant $currentTenant, FormService $service): JsonResponse
    {
        $data = $request->validate(['schema' => ['required', 'array']]);
        $tenant = $currentTenant->forUser($request->user());
        $form = $service->create($tenant, $request->user(), $data['schema']);

        return response()->json($form, 201);
    }

    public function show(Request $request, string $publicId, CurrentTenant $currentTenant): JsonResponse
    {
        $form = $this->ownedForm($request, $publicId, $currentTenant);

        return response()->json($form->load(['tenant', 'currentVersion', 'publishedVersion']));
    }

    public function update(Request $request, string $publicId, CurrentTenant $currentTenant, FormService $service): JsonResponse
    {
        $form = $this->ownedForm($request, $publicId, $currentTenant);
        $data = $request->validate(['schema' => ['required', 'array']]);
        $version = $service->save($form, $request->user(), $data['schema']);

        return response()->json(['form' => $form->fresh(), 'version' => $version]);
    }

    public function publish(Request $request, string $publicId, CurrentTenant $currentTenant, FormService $service): JsonResponse
    {
        return response()->json($service->publish($this->ownedForm($request, $publicId, $currentTenant)));
    }

    public function submissions(Request $request, string $publicId, CurrentTenant $currentTenant): JsonResponse
    {
        $form = $this->ownedForm($request, $publicId, $currentTenant);
        $submissions = $form->submissions()->latest('submitted_at')->paginate(25);

        return response()->json($submissions);
    }

    private function ownedForm(Request $request, string $publicId, CurrentTenant $currentTenant): Form
    {
        $tenant = $currentTenant->forUser($request->user());

        /** @var Form $form */
        $form = $tenant->forms()->where('public_id', $publicId)->firstOrFail();

        return $form;
    }
}
