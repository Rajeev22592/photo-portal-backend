<?php

namespace App\Http\Controllers\Api\Studio;

use App\Http\Controllers\Api\Controller;
use App\Models\Gallery;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        if (! $tenant) {
            return $this->error('Tenant not found.', 403);
        }

        $request->validate([
            'gallery_id' => ['required', 'exists:galleries,id'],
            'files' => ['required', 'array'],
            'files.*' => ['file', 'image', 'max:51200'], // 50MB
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $gallery = $tenant->galleries()->find($request->gallery_id);
        if (! $gallery) {
            return $this->error('Gallery not found.', 404);
        }

        $sub = $tenant->activeSubscription();
        if ($sub && $sub->max_media_per_gallery !== null) {
            $current = $gallery->media()->count();
            $adding = count($request->file('files', []));
            if ($current + $adding > $sub->max_media_per_gallery) {
                return $this->planLimitReached('Media per gallery limit reached.');
            }
        }
        if ($sub && $sub->max_media_total !== null) {
            $total = Media::whereIn('gallery_id', $tenant->galleries()->pluck('id'))->count();
            $adding = count($request->file('files', []));
            if ($total + $adding > $sub->max_media_total) {
                return $this->planLimitReached('Total media limit reached.');
            }
        }

        $uploaded = [];
        foreach ($request->file('files', []) as $file) {
            $path = $file->store('galleries/' . $gallery->id, 'public');
            $media = Media::create([
                'gallery_id' => $gallery->id,
                'path' => $path,
                'url' => Storage::url($path),
                'type' => 'photo',
                'title' => $request->input('title'),
                'description' => $request->input('description'),
            ]);
            $uploaded[] = $this->mediaResource($media);
        }

        return $this->success(count($uploaded) === 1 ? $uploaded[0] : $uploaded, null, 201);
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
            'created_at' => $media->created_at?->toIso8601String(),
        ];
    }
}
