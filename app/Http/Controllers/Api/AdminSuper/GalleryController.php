<?php

namespace App\Http\Controllers\Api\AdminSuper;

use App\Http\Controllers\Api\Controller;
use App\Models\Gallery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $galleries = Gallery::with(['tenant', 'client'])->withCount('media')
            ->when($request->has('page'), fn ($q) => $q->paginate(15), fn ($q) => $q->get())
            ->map(fn (Gallery $g) => $this->resource($g));
        return $this->success($galleries);
    }

    public function show(int $id): JsonResponse
    {
        $gallery = Gallery::with(['tenant', 'client', 'media'])->withCount('media')->find($id);
        if (! $gallery) {
            return $this->error('Gallery not found.', 404);
        }
        return $this->success($this->resource($gallery));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'tenant_id' => ['required', 'exists:tenants,id'],
            'event_date' => ['nullable', 'date'],
            'cover_image' => ['nullable', 'string'],
            'name' => ['nullable', 'string', 'max:255'],
            'is_public' => ['nullable', 'boolean'],
        ]);

        $gallery = Gallery::create($validated);
        return $this->success($this->resource($gallery->loadCount('media')), null, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $gallery = Gallery::find($id);
        if (! $gallery) {
            return $this->error('Gallery not found.', 404);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'tenant_id' => ['sometimes', 'exists:tenants,id'],
            'event_date' => ['nullable', 'date'],
            'cover_image' => ['nullable', 'string'],
            'name' => ['nullable', 'string', 'max:255'],
            'is_public' => ['nullable', 'boolean'],
        ]);

        $gallery->update($validated);
        return $this->success($this->resource($gallery->loadCount('media')));
    }

    public function destroy(int $id): JsonResponse
    {
        $gallery = Gallery::find($id);
        if (! $gallery) {
            return $this->error('Gallery not found.', 404);
        }
        $gallery->delete();
        return $this->success(null);
    }

    private function resource(Gallery $g): array
    {
        return [
            'id' => $g->id,
            'title' => $g->title,
            'name' => $g->name,
            'client_id' => $g->client_id,
            'client' => $g->relationLoaded('client') ? ['id' => $g->client->id, 'name' => $g->client->name] : null,
            'tenant_id' => $g->tenant_id,
            'is_public' => $g->is_public,
            'total_media_count' => $g->media_count ?? $g->media()->count(),
            'cover_image' => $g->cover_image,
            'event_date' => $g->event_date?->format('Y-m-d'),
            'media' => $g->relationLoaded('media') ? $g->media->map(fn ($m) => ['id' => $m->id, 'url' => $m->file_url]) : null,
        ];
    }
}
