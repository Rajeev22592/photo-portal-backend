<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Api\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $client = $request->user()->client;
        if (! $client) {
            return $this->error('Client not found.', 403);
        }

        $galleries = $client->galleries()->withCount('media')->get()->map(fn ($g) => [
            'id' => $g->id,
            'title' => $g->title,
            'name' => $g->name,
            'client_id' => $g->client_id,
            'is_public' => $g->is_public,
            'total_media_count' => $g->media_count,
            'cover_image_url' => $g->cover_image,
            'event_date' => $g->event_date?->format('Y-m-d'),
        ]);
        return $this->success($galleries);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $client = $request->user()->client;
        if (! $client) {
            return $this->error('Client not found.', 403);
        }

        $gallery = $client->galleries()->with('media')->withCount('media')->find($id);
        if (! $gallery) {
            return $this->error('Gallery not found.', 404);
        }

        return $this->success([
            'id' => $gallery->id,
            'title' => $gallery->title,
            'name' => $gallery->name,
            'client_id' => $gallery->client_id,
            'is_public' => $gallery->is_public,
            'total_media_count' => $gallery->media_count,
            'cover_image_url' => $gallery->cover_image,
            'event_date' => $gallery->event_date?->format('Y-m-d'),
            'media' => $gallery->media->map(fn ($m) => [
                'id' => $m->id,
                'url' => $m->file_url,
                'thumbnail_url' => $m->thumbnail_url,
                'type' => $m->type,
            ]),
        ]);
    }
}
