<?php

namespace App\Http\Controllers\Api\AdminSuper;

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
        $request->validate([
            'gallery_id' => ['required', 'exists:galleries,id'],
            'files' => ['required', 'array'],
            'files.*' => ['file', 'image', 'max:51200'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $gallery = Gallery::find($request->gallery_id);
        if (! $gallery) {
            return $this->error('Gallery not found.', 404);
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
            $uploaded[] = [
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

        return $this->success(count($uploaded) === 1 ? $uploaded[0] : $uploaded, null, 201);
    }
}
