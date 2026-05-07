<?php

namespace App\Http\Controllers\Api;

use App\Services\FaceRecognitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaceRecognitionController extends Controller
{
    public function detect(Request $request, FaceRecognitionService $faceService): JsonResponse
    {
        $request->validate(['file' => ['required', 'file', 'image', 'max:10240']]);

        try {
            $result = $faceService->detect($request->file('file'));
            $faces = $result['faces'] ?? [];
            return $this->success(['faces' => $faces]);
        } catch (\Throwable $e) {
            return $this->error(
                str_contains($e->getMessage(), 'Face detection') ? $e->getMessage() : 'Face detection failed.',
                500
            );
        }
    }

    public function compare(Request $request, FaceRecognitionService $faceService): JsonResponse
    {
        $request->validate([
            'file1' => ['required', 'file', 'image', 'max:10240'],
            'file2' => ['required', 'file', 'image', 'max:10240'],
        ]);

        try {
            $result = $faceService->compare($request->file('file1'), $request->file('file2'));
            return $this->success([
                'match' => $result['match'] ?? false,
                'similarity' => $result['similarity'] ?? 0,
            ]);
        } catch (\Throwable $e) {
            return $this->error(
                str_contains($e->getMessage(), 'Face') ? $e->getMessage() : 'Face compare failed.',
                500
            );
        }
    }

    public function health(FaceRecognitionService $faceService): JsonResponse
    {
        $result = $faceService->health();
        return $this->success($result);
    }
}
