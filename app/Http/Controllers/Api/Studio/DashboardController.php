<?php

namespace App\Http\Controllers\Api\Studio;

use App\Http\Controllers\Api\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        if (! $tenant) {
            return $this->error('Tenant not found.', 403);
        }

        $totalClients = $tenant->clients()->count();
        $totalGalleries = $tenant->galleries()->count();
        $totalMedia = \App\Models\Media::whereIn('gallery_id', $tenant->galleries()->pluck('id'))->count();
        $totalFaces = \App\Models\Face::whereIn('media_id', \App\Models\Media::whereIn('gallery_id', $tenant->galleries()->pluck('id'))->pluck('id'))->count();

        $startOfMonth = now()->startOfMonth();
        $uploadsThisMonth = \App\Models\Media::whereIn('gallery_id', $tenant->galleries()->pluck('id'))
            ->where('created_at', '>=', $startOfMonth)
            ->count();

        $recentUploads = \App\Models\Media::whereIn('gallery_id', $tenant->galleries()->pluck('id'))
            ->with('gallery')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'url' => $m->file_url,
                'thumbnail_url' => $m->thumbnail_url,
                'gallery_id' => $m->gallery_id,
                'created_at' => $m->created_at?->toIso8601String(),
            ]);

        $mostActiveGallery = $tenant->galleries()
            ->withCount('media')
            ->orderByDesc('media_count')
            ->first();

        return $this->success([
            'total_clients' => $totalClients,
            'total_galleries' => $totalGalleries,
            'total_media' => $totalMedia,
            'total_faces_detected' => $totalFaces,
            'total_faces' => $totalFaces,
            'uploads_this_month' => $uploadsThisMonth,
            'faces_detected_this_month' => null,
            'most_active_gallery' => $mostActiveGallery ? [
                'id' => $mostActiveGallery->id,
                'title' => $mostActiveGallery->title,
                'media_count' => $mostActiveGallery->media_count,
            ] : null,
            'recent_uploads' => $recentUploads,
        ]);
    }
}
