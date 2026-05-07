<?php

namespace App\Http\Controllers\Api\AdminSuper;

use App\Http\Controllers\Api\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class TenantController extends Controller
{
    public function index(): JsonResponse
    {
        $tenants = Tenant::withCount(['clients', 'galleries'])
            ->get()
            ->map(fn (Tenant $t) => $this->tenantResource($t));
        return $this->success($tenants);
    }

    public function show(int $id): JsonResponse
    {
        $tenant = Tenant::withCount(['clients', 'galleries'])->with('subscriptions')->find($id);
        if (! $tenant) {
            return $this->error('Tenant not found.', 404);
        }
        return $this->success($this->tenantResource($tenant));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'studio_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:tenants,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
            'create_user' => ['nullable', 'boolean'],
            'user_name' => ['nullable', 'string', 'max:255'],
            'user_email' => ['nullable', 'email', 'max:255'],
            'user_password' => ['nullable', 'string', 'confirmed', Password::defaults()],
        ]);

        $tenant = Tenant::create([
            'studio_name' => $validated['studio_name'],
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        if (! empty($validated['create_user']) && ! empty($validated['user_email'])) {
            User::create([
                'name' => $validated['user_name'] ?? $validated['name'],
                'email' => $validated['user_email'],
                'password' => $validated['user_password'] ?? \Illuminate\Support\Str::random(12),
                'role' => 'tenant',
                'tenant_id' => $tenant->id,
            ]);
        }

        return $this->success($this->tenantResource($tenant->loadCount(['clients', 'galleries'])), null, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $tenant = Tenant::find($id);
        if (! $tenant) {
            return $this->error('Tenant not found.', 404);
        }

        $validated = $request->validate([
            'studio_name' => ['sometimes', 'string', 'max:255'],
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $tenant->update($validated);
        return $this->success($this->tenantResource($tenant->loadCount(['clients', 'galleries'])));
    }

    public function destroy(int $id): JsonResponse
    {
        $tenant = Tenant::find($id);
        if (! $tenant) {
            return $this->error('Tenant not found.', 404);
        }
        $tenant->delete();
        return $this->success(null);
    }

    public function subscription(int $id): JsonResponse
    {
        $tenant = Tenant::find($id);
        if (! $tenant) {
            return $this->error('Tenant not found.', 404);
        }
        $sub = $tenant->activeSubscription();
        if (! $sub) {
            return $this->success(null);
        }
        return $this->success($this->subscriptionResource($sub));
    }

    private function tenantResource(Tenant $t): array
    {
        $sub = $t->activeSubscription();
        return [
            'id' => $t->id,
            'studio_name' => $t->studio_name,
            'name' => $t->name,
            'email' => $t->email,
            'phone' => $t->phone,
            'is_active' => $t->is_active,
            'clients_count' => $t->clients_count ?? $t->clients()->count(),
            'galleries_count' => $t->galleries_count ?? $t->galleries()->count(),
            'subscription' => $sub ? $this->subscriptionResource($sub) : null,
            'subscription_status' => $sub?->status,
            'subscription_plan' => $sub?->plan,
            'created_at' => $t->created_at?->toIso8601String(),
        ];
    }

    private function subscriptionResource($sub): array
    {
        return [
            'id' => $sub->id,
            'tenant_id' => $sub->tenant_id,
            'plan' => $sub->plan,
            'plan_name' => $sub->plan_name,
            'amount' => $sub->amount,
            'interval' => $sub->interval,
            'status' => $sub->status,
            'expires_at' => $sub->expires_at?->toIso8601String(),
            'max_galleries' => $sub->max_galleries,
            'max_media_per_gallery' => $sub->max_media_per_gallery,
            'max_media_total' => $sub->max_media_total,
            'max_face_searches_per_month' => $sub->max_face_searches_per_month,
            'face_recognition_enabled' => $sub->face_recognition_enabled,
            'is_active' => $sub->is_active,
        ];
    }
}
