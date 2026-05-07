<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FaceRecognitionService
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly int $timeout = 120
    ) {}

    public function health(): array
    {
        try {
            $response = Http::timeout(5)->get(rtrim($this->baseUrl, '/') . '/health');
            if ($response->successful()) {
                $body = $response->json();
                return [
                    'status' => $body['status'] ?? 'ok',
                    'service' => $body['service'] ?? 'face-recognition',
                ];
            }
            return ['status' => 'error', 'service' => 'face-recognition'];
        } catch (\Throwable $e) {
            Log::warning('Face recognition health check failed: ' . $e->getMessage());
            return ['status' => 'unavailable', 'service' => 'face-recognition'];
        }
    }

    public function detect(UploadedFile $file): array
    {
        $response = Http::timeout($this->timeout)
            ->attach('file', $file->get(), $file->getClientOriginalName())
            ->post(rtrim($this->baseUrl, '/') . '/detect');

        if (! $response->successful()) {
            $msg = $response->json('message') ?? $response->body();
            throw new \RuntimeException('Face detection service: ' . $msg);
        }

        return $response->json('data', $response->json());
    }

    public function compare(UploadedFile $file1, UploadedFile $file2): array
    {
        $response = Http::timeout($this->timeout)
            ->attach('file1', $file1->get(), $file1->getClientOriginalName())
            ->attach('file2', $file2->get(), $file2->getClientOriginalName())
            ->post(rtrim($this->baseUrl, '/') . '/compare');

        if (! $response->successful()) {
            $msg = $response->json('message') ?? $response->body();
            throw new \RuntimeException('Face recognition service: ' . $msg);
        }

        return $response->json('data', $response->json());
    }

    public function search(UploadedFile $file, array $embeddingIdsOrPaths): array
    {
        $request = Http::timeout($this->timeout)
            ->attach('file', $file->get(), $file->getClientOriginalName());

        foreach ($embeddingIdsOrPaths as $i => $path) {
            if (is_string($path) && is_file($path)) {
                $request->attach("ref_{$i}", file_get_contents($path), basename($path));
            }
        }

        $response = $request->post(rtrim($this->baseUrl, '/') . '/search');

        if (! $response->successful()) {
            $msg = $response->json('message') ?? $response->body();
            throw new \RuntimeException('Face search service: ' . $msg);
        }

        return $response->json('data', $response->json());
    }
}
