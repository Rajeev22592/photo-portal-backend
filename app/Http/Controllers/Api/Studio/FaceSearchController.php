<?php

namespace App\Http\Controllers\Api\Studio;

use App\Http\Controllers\Api\Controller;
use App\Models\Media;
use App\Services\FaceRecognitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FaceSearchController extends Controller
{
    public function __invoke(Request $request, FaceRecognitionService $faceService): JsonResponse
    {
        $tenant = $request->user()->tenant;
        if (! $tenant) {
            return $this->error('Tenant not found.', 403);
        }

        $sub = $tenant->activeSubscription();
        if ($sub && $sub->face_recognition_enabled === false) {
            return $this->error('Face recognition is not enabled for your plan.', 403);
        }
        if ($sub && $sub->max_face_searches_per_month !== null) {
            // Simplified: no usage table; could add tenant_usage table with month/count
            // For now we allow and document that you should add usage tracking
        }

        $request->validate(['file' => ['required', 'file', 'image', 'max:10240']]); // 10MB

        try {
            $file = $request->file('file');
            $health = $faceService->health();
            if (($health['status'] ?? '') === 'unavailable') {
                return $this->error('Face detection service is temporarily unavailable. Please try again later.', 503);
            }

            // Get all media with face embeddings for this tenant
            $mediaIds = Media::whereIn('gallery_id', $tenant->galleries()->pluck('id'))
                ->where('face_processed', true)
                ->pluck('id');

            if ($mediaIds->isEmpty()) {
                return $this->success(['matches' => []]);
            }

            // Build paths and ordered media list for the Python service
            $mediaList = Media::whereIn('id', $mediaIds)->get();
            $pathList = [];
            $mediaInOrder = [];
            foreach ($mediaList as $m) {
                $fullPath = Storage::disk('public')->path($m->path);
                if (is_file($fullPath)) {
                    $pathList[] = $fullPath;
                    $mediaInOrder[] = $m;
                }
            }

            if (empty($pathList)) {
                return $this->success(['matches' => []]);
            }

            $result = $faceService->search($file, $pathList);
            $matches = [];
            foreach ($result['matches'] ?? [] as $match) {
                $index = $match['index'] ?? $match['media_id'] ?? null;
                $media = is_int($index) && isset($mediaInOrder[$index])
                    ? $mediaInOrder[$index]
                    : $mediaList->firstWhere('id', $index);
                if ($media) {
                    $matches[] = [
                        'media_id' => $media->id,
                        'gallery_id' => $media->gallery_id,
                        'score' => $match['score'] ?? 0,
                        'similarity' => $match['similarity'] ?? null,
                        'file_url' => $media->file_url,
                        'thumbnail_url' => $media->thumbnail_url,
                        'media_url' => $media->file_url,
                        'bounding_box' => $match['bounding_box'] ?? null,
                    ];
                }
            }

            return $this->success(['matches' => $matches]);
        } catch (\Throwable $e) {
            return $this->error(
                str_contains($e->getMessage(), 'Face detection service') ? $e->getMessage() : 'Face search failed. Please try again.',
                500
            );
        }
    }
}
