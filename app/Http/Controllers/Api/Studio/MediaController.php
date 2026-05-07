<?php

namespace App\Http\Controllers\Api\Studio;

use App\Http\Controllers\Api\Controller;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        if (! $tenant) {
            return $this->error('Tenant not found.', 403);
        }

        $query = Media::whereIn('gallery_id', $tenant->galleries()->pluck('id'))
            ->with('gallery');

        if ($request->filled('gallery_id')) {
            $gallery = $tenant->galleries()->find($request->gallery_id);
            if (! $gallery) {
                return $this->error('Gallery not found.', 404);
            }
            $query->where('gallery_id', $request->gallery_id);
        }

        $media = $query->get()->map(fn (Media $m) => $this->mediaResource($m));
        return $this->success($media);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $tenant = $request->user()->tenant;
        if (! $tenant) {
            return $this->error('Tenant not found.', 403);
        }

        $media = Media::whereIn('gallery_id', $tenant->galleries()->pluck('id'))->with('faces')->find($id);
        if (! $media) {
            return $this->error('Media not found.', 404);
        }

        $resource = $this->mediaResource($media);
        $resource['faces'] = $media->faces->map(fn ($f) => [
            'id' => $f->id,
            'x' => $f->x,
            'y' => $f->y,
            'width' => $f->width,
            'height' => $f->height,
            'confidence' => $f->confidence,
            'bounding_box' => $f->bounding_box,
        ]);
        return $this->success($resource);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $tenant = $request->user()->tenant;
        if (! $tenant) {
            return $this->error('Tenant not found.', 403);
        }

        $media = Media::whereIn('gallery_id', $tenant->galleries()->pluck('id'))->find($id);
        if (! $media) {
            return $this->error('Media not found.', 404);
        }

        $validated = $request->validate([
            'gallery_id' => ['sometimes', 'exists:galleries,id'],
            'thumbnail_url' => ['nullable', 'string'],
        ]);

        if (isset($validated['gallery_id']) && $tenant->galleries()->where('id', $validated['gallery_id'])->doesntExist()) {
            return $this->error('Gallery not found.', 422);
        }

        $media->update($validated);
        return $this->success($this->mediaResource($media));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $tenant = $request->user()->tenant;
        if (! $tenant) {
            return $this->error('Tenant not found.', 403);
        }

        $media = Media::whereIn('gallery_id', $tenant->galleries()->pluck('id'))->find($id);
        if (! $media) {
            return $this->error('Media not found.', 404);
        }

        $media->delete();
        return $this->success(null);
    }

    private function mediaResource(Media $media): array
    {
        return [
            'id' => $media->id,
            'url' => $media->file_url,
            'file_url' => $media->file_url,
            'thumbnail_url' => $media->thumbnail_url,
            'type' => $media->type,
            'gallery_id' => $media->gallery_id,
            'has_face' => $media->has_face,
            'face_processed' => $media->face_processed,
            'faces_detected' => $media->faces_detected,
            'faces' => $media->relationLoaded('faces') ? $media->faces->map(fn ($f) => [
                'x' => $f->x, 'y' => $f->y, 'width' => $f->width ?? $f->w, 'height' => $f->height ?? $f->h, 'confidence' => $f->confidence,
            ]) : null,
            'created_at' => $media->created_at?->toIso8601String(),
        ];
    }
}
