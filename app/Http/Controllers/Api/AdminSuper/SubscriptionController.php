<?php

namespace App\Http\Controllers\Api\AdminSuper;

use App\Http\Controllers\Api\Controller;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Subscription::with('tenant');
        $result = $request->has('page')
            ? $query->paginate(15)->through(fn ($s) => $this->resource($s))
            : $query->get()->map(fn ($s) => $this->resource($s));
        return $this->success($result);
    }

    public function show(int $id): JsonResponse
    {
        $sub = Subscription::with('tenant')->find($id);
        if (! $sub) {
            return $this->error('Subscription not found.', 404);
        }
        return $this->success($this->resource($sub));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'plan' => ['required', 'string', 'max:100'],
            'plan_name' => ['nullable', 'string', 'max:255'],
            'amount' => ['nullable', 'numeric'],
            'interval' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'string', 'max:50'],
            'expires_at' => ['nullable', 'date'],
            'max_galleries' => ['nullable', 'integer'],
            'max_media_per_gallery' => ['nullable', 'integer'],
            'max_media_total' => ['nullable', 'integer'],
            'max_face_searches_per_month' => ['nullable', 'integer'],
            'face_recognition_enabled' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $sub = Subscription::create($validated);
        return $this->success($this->resource($sub->load('tenant')), null, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $sub = Subscription::find($id);
        if (! $sub) {
            return $this->error('Subscription not found.', 404);
        }

        $validated = $request->validate([
            'tenant_id' => ['sometimes', 'exists:tenants,id'],
            'plan' => ['sometimes', 'string', 'max:100'],
            'plan_name' => ['nullable', 'string', 'max:255'],
            'amount' => ['nullable', 'numeric'],
            'interval' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'string', 'max:50'],
            'expires_at' => ['nullable', 'date'],
            'max_galleries' => ['nullable', 'integer'],
            'max_media_per_gallery' => ['nullable', 'integer'],
            'max_media_total' => ['nullable', 'integer'],
            'max_face_searches_per_month' => ['nullable', 'integer'],
            'face_recognition_enabled' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $sub->update($validated);
        return $this->success($this->resource($sub->load('tenant')));
    }

    public function destroy(int $id): JsonResponse
    {
        $sub = Subscription::find($id);
        if (! $sub) {
            return $this->error('Subscription not found.', 404);
        }
        $sub->delete();
        return $this->success(null);
    }

    private function resource(Subscription $s): array
    {
        return [
            'id' => $s->id,
            'tenant_id' => $s->tenant_id,
            'tenant' => $s->relationLoaded('tenant') ? ['id' => $s->tenant->id, 'studio_name' => $s->tenant->studio_name] : null,
            'plan' => $s->plan,
            'plan_name' => $s->plan_name,
            'amount' => $s->amount,
            'interval' => $s->interval,
            'status' => $s->status,
            'expires_at' => $s->expires_at?->toIso8601String(),
            'max_galleries' => $s->max_galleries,
            'max_media_per_gallery' => $s->max_media_per_gallery,
            'max_media_total' => $s->max_media_total,
            'max_face_searches_per_month' => $s->max_face_searches_per_month,
            'face_recognition_enabled' => $s->face_recognition_enabled,
            'starts_at' => $s->starts_at?->toIso8601String(),
            'ends_at' => $s->ends_at?->toIso8601String(),
            'is_active' => $s->is_active,
            'created_at' => $s->created_at?->toIso8601String(),
            'updated_at' => $s->updated_at?->toIso8601String(),
        ];
    }
}
