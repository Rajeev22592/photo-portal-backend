<?php

namespace App\Http\Controllers\Api\Studio;

use App\Http\Controllers\Api\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        if (! $tenant) {
            return $this->error('Tenant not found.', 403);
        }

        $sub = $tenant->activeSubscription();
        if (! $sub) {
            return $this->success([
                'id' => null,
                'plan' => 'free',
                'plan_name' => 'Free',
                'status' => 'inactive',
                'max_galleries' => null,
                'max_media_per_gallery' => null,
                'max_media_total' => null,
                'max_face_searches_per_month' => null,
                'face_recognition_enabled' => false,
            ]);
        }

        return $this->success([
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
            'starts_at' => $sub->starts_at?->toIso8601String(),
            'ends_at' => $sub->ends_at?->toIso8601String(),
            'is_active' => $sub->is_active,
            'created_at' => $sub->created_at?->toIso8601String(),
            'updated_at' => $sub->updated_at?->toIso8601String(),
        ]);
    }
}
