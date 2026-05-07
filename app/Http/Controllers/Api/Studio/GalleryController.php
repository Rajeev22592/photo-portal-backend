<?php

namespace App\Http\Controllers\Api\Studio;

use App\Http\Controllers\Api\Controller;
use App\Models\Gallery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        if (! $tenant) {
            return $this->error('Tenant not found.', 403);
        }

        $this->checkGalleryLimit($request->user());

        $galleries = $tenant->galleries()
            ->with('client')
            ->withCount('media')
            ->when($request->has('page'), fn ($q) => $q->paginate(15), fn ($q) => $q->get());

        $data = $galleries instanceof \Illuminate\Pagination\LengthAwarePaginator
            ? $galleries->through(fn (Gallery $g) => $this->galleryResource($g))
            : $galleries->map(fn (Gallery $g) => $this->galleryResource($g));

        return $this->success($data);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $tenant = $request->user()->tenant;
        if (! $tenant) {
            return $this->error('Tenant not found.', 403);
        }

        $gallery = $tenant->galleries()->with(['client', 'media'])->withCount('media')->find($id);
        if (! $gallery) {
            return $this->error('Gallery not found.', 404);
        }

        return $this->success($this->galleryResource($gallery));
    }

    public function store(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        if (! $tenant) {
            return $this->error('Tenant not found.', 403);
        }

        $this->checkGalleryLimit($request->user());

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'event_date' => ['nullable', 'date'],
            'cover_image' => ['nullable', 'string'],
            'name' => ['nullable', 'string', 'max:255'],
            'is_public' => ['nullable', 'boolean'],
        ]);

        $validated['tenant_id'] = $tenant->id;
        if (isset($validated['client_id']) && $validated['client_id']) {
            if ($tenant->clients()->where('id', $validated['client_id'])->doesntExist()) {
                return $this->error('Client does not belong to your studio.', 422);
            }
        }

        $gallery = Gallery::create($validated);
        return $this->success($this->galleryResource($gallery->loadCount('media')), null, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $tenant = $request->user()->tenant;
        if (! $tenant) {
            return $this->error('Tenant not found.', 403);
        }

        $gallery = $tenant->galleries()->find($id);
        if (! $gallery) {
            return $this->error('Gallery not found.', 404);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'event_date' => ['nullable', 'date'],
            'cover_image' => ['nullable', 'string'],
            'name' => ['nullable', 'string', 'max:255'],
            'is_public' => ['nullable', 'boolean'],
        ]);

        if (isset($validated['client_id']) && $validated['client_id'] && $tenant->clients()->where('id', $validated['client_id'])->doesntExist()) {
            return $this->error('Client does not belong to your studio.', 422);
        }

        $gallery->update($validated);
        return $this->success($this->galleryResource($gallery->loadCount('media')));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $tenant = $request->user()->tenant;
        if (! $tenant) {
            return $this->error('Tenant not found.', 403);
        }

        $gallery = $tenant->galleries()->find($id);
        if (! $gallery) {
            return $this->error('Gallery not found.', 404);
        }

        $gallery->delete();
        return $this->success(null);
    }

    private function checkGalleryLimit($user): void
    {
        $sub = $user->tenant?->activeSubscription();
        if ($sub && $sub->max_galleries !== null) {
            $count = $user->tenant->galleries()->count();
            if ($count >= $sub->max_galleries) {
                abort(403, 'Plan gallery limit reached.');
            }
        }
    }

    private function galleryResource(Gallery $gallery): array
    {
        $mediaCount = $gallery->media_count ?? $gallery->media()->count();
        return [
            'id' => $gallery->id,
            'title' => $gallery->title,
            'name' => $gallery->name,
            'client_id' => $gallery->client_id,
            'client' => $gallery->relationLoaded('client') ? [
                'id' => $gallery->client->id,
                'name' => $gallery->client->name,
                'email' => $gallery->client->email,
            ] : null,
            'is_public' => $gallery->is_public,
            'total_media_count' => $mediaCount,
            'total_faces_count' => 0,
            'cover_image_url' => $gallery->cover_image,
            'cover_image' => $gallery->cover_image,
            'event_date' => $gallery->event_date?->format('Y-m-d'),
            'media' => $gallery->relationLoaded('media') ? $gallery->media->map(fn ($m) => [
                'id' => $m->id,
                'url' => $m->file_url,
                'thumbnail_url' => $m->thumbnail_url,
                'type' => $m->type,
            ]) : null,
            'media_count' => $mediaCount,
        ];
    }
}
