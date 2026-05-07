<?php

namespace App\Http\Controllers\Api\Client;

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
        $client = $request->user()->client;
        if (! $client) {
            return $this->error('Client not found.', 403);
        }

        $request->validate(['file' => ['required', 'file', 'image', 'max:10240']]);

        try {
            $file = $request->file('file');
            $health = $faceService->health();
            if (($health['status'] ?? '') === 'unavailable') {
                return $this->error('Face detection service is temporarily unavailable.', 503);
            }

            $mediaIds = Media::whereIn('gallery_id', $client->galleries()->pluck('id'))
                ->where('face_processed', true)
                ->pluck('id');
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
                $media = is_int($index) && isset($mediaInOrder[$index]) ? $mediaInOrder[$index] : $mediaList->firstWhere('id', $index);
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
                str_contains($e->getMessage(), 'Face') ? $e->getMessage() : 'Face search failed.',
                500
            );
        }
    }
}
