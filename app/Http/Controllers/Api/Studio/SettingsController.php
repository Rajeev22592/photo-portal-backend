<?php

namespace App\Http\Controllers\Api\Studio;

use App\Http\Controllers\Api\Controller;
use App\Models\StudioSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        if (! $tenant) {
            return $this->error('Tenant not found.', 403);
        }

        $settings = $tenant->settings ?? StudioSetting::create(['tenant_id' => $tenant->id]);
        return $this->success($this->settingsResource($settings));
    }

    public function update(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        if (! $tenant) {
            return $this->error('Tenant not found.', 403);
        }

        $settings = $tenant->settings ?? StudioSetting::create(['tenant_id' => $tenant->id]);

        if ($request->hasFile('logo_file')) {
            if ($settings->logo) {
                Storage::disk('public')->delete($settings->logo);
            }
            $path = $request->file('logo_file')->store('studio-logos', 'public');
            $settings->update(['logo' => $path]);
            return $this->success($this->settingsResource($settings->fresh()));
        }

        $validated = $request->validate([
            'studio_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'bio' => ['nullable', 'string'],
            'logo' => ['nullable', 'string'],
            'preferences.watermark_photos' => ['nullable', 'boolean'],
            'preferences.auto_face_detection' => ['nullable', 'boolean'],
            'preferences.client_downloads' => ['nullable', 'boolean'],
            'preferences.email_notifications' => ['nullable', 'boolean'],
        ]);

        if (isset($validated['preferences']) && is_array($validated['preferences'])) {
            $settings->preferences = array_merge($settings->preferences ?? [], $validated['preferences']);
            unset($validated['preferences']);
        }

        $settings->update($validated);
        return $this->success($this->settingsResource($settings->fresh()));
    }

    public function logo(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        if (! $tenant) {
            return $this->error('Tenant not found.', 403);
        }

        $request->validate(['logo' => ['required', 'file', 'image', 'max:2048']]);

        $settings = $tenant->settings ?? StudioSetting::create(['tenant_id' => $tenant->id]);
        if ($settings->logo) {
            Storage::disk('public')->delete($settings->logo);
        }
        $path = $request->file('logo')->store('studio-logos', 'public');
        $settings->update(['logo' => $path]);

        return $this->success(['logo' => Storage::url($path)]);
    }

    private function settingsResource(StudioSetting $s): array
    {
        return [
            'studio_name' => $s->studio_name,
            'email' => $s->email,
            'phone' => $s->phone,
            'website' => $s->website,
            'address' => $s->address,
            'bio' => $s->bio,
            'logo' => $s->logo ? Storage::url($s->logo) : null,
            'preferences' => $s->preferences ?? [],
        ];
    }
}
