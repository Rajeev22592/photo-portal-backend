<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Api\Controller;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaDownloadController extends Controller
{
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $client = $request->user()->client;
        if (! $client) {
            return $this->error('Client not found.', 403);
        }

        $media = Media::whereIn('gallery_id', $client->galleries()->pluck('id'))->find($id);
        if (! $media) {
            return $this->error('Media not found.', 404);
        }

        $url = Storage::disk('public')->temporaryUrl($media->path, now()->addMinutes(30));
        if (! $url) {
            $url = Storage::url($media->path);
        }
        return $this->success(['url' => $url]);
    }
}
