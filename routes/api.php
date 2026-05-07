<?php

use App\Http\Controllers\Api\AdminSuper\ClientController as AdminClientController;
use App\Http\Controllers\Api\AdminSuper\GalleryController as AdminGalleryController;
use App\Http\Controllers\Api\AdminSuper\SubscriptionController as AdminSubscriptionController;
use App\Http\Controllers\Api\AdminSuper\TenantController as AdminTenantController;
use App\Http\Controllers\Api\AdminSuper\UploadController as AdminUploadController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Client\FaceSearchController as ClientFaceSearchController;
use App\Http\Controllers\Api\Client\GalleryController as ClientGalleryController;
use App\Http\Controllers\Api\Client\MediaDownloadController;
use App\Http\Controllers\Api\Client\ProfileController as ClientProfileController;
use App\Http\Controllers\Api\FaceRecognitionController;
use App\Http\Controllers\Api\Studio\ClientController as StudioClientController;
use App\Http\Controllers\Api\Studio\DashboardController;
use App\Http\Controllers\Api\Studio\FaceSearchController as StudioFaceSearchController;
use App\Http\Controllers\Api\Studio\GalleryController as StudioGalleryController;
use App\Http\Controllers\Api\Studio\MediaController as StudioMediaController;
use App\Http\Controllers\Api\Studio\SettingsController;
use App\Http\Controllers\Api\Studio\SubscriptionController;
use App\Http\Controllers\Api\Studio\UploadController;
use Illuminate\Support\Facades\Route;

// Public / guest
Route::post('/auth/register-studio', [AuthController::class, 'registerStudio']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

// Face recognition (can be public or auth - spec has GET health)
Route::get('/face-recognition/health', [FaceRecognitionController::class, 'health']);

// Auth required
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Face recognition (detect, compare) - any authenticated user
    Route::post('/face-recognition/detect', [FaceRecognitionController::class, 'detect']);
    Route::post('/face-recognition/compare', [FaceRecognitionController::class, 'compare']);

    // Studio (tenant) routes
    Route::prefix('studio')->middleware('role:tenant,super_admin')->group(function () {
        Route::get('/dashboard', DashboardController::class);
        Route::get('/clients', [StudioClientController::class, 'index']);
        Route::get('/clients/{id}', [StudioClientController::class, 'show']);
        Route::post('/clients', [StudioClientController::class, 'store']);
        Route::put('/clients/{id}', [StudioClientController::class, 'update']);
        Route::delete('/clients/{id}', [StudioClientController::class, 'destroy']);
        Route::get('/galleries', [StudioGalleryController::class, 'index']);
        Route::get('/galleries/{id}', [StudioGalleryController::class, 'show']);
        Route::post('/galleries', [StudioGalleryController::class, 'store']);
        Route::put('/galleries/{id}', [StudioGalleryController::class, 'update']);
        Route::delete('/galleries/{id}', [StudioGalleryController::class, 'destroy']);
        Route::post('/upload', UploadController::class);
        Route::post('/face-search', StudioFaceSearchController::class);
        Route::get('/media', [StudioMediaController::class, 'index']);
        Route::get('/media/{id}', [StudioMediaController::class, 'show']);
        Route::put('/media/{id}', [StudioMediaController::class, 'update']);
        Route::delete('/media/{id}', [StudioMediaController::class, 'destroy']);
        Route::get('/subscription', SubscriptionController::class);
        Route::get('/settings', [SettingsController::class, 'show']);
        Route::put('/settings', [SettingsController::class, 'update']);
        Route::post('/settings/logo', [SettingsController::class, 'logo']);
    });

    // Client portal routes
    Route::prefix('client')->middleware('role:client')->group(function () {
        Route::get('/profile', [ClientProfileController::class, 'show']);
        Route::put('/profile', [ClientProfileController::class, 'update']);
        Route::get('/galleries', [ClientGalleryController::class, 'index']);
        Route::get('/galleries/{id}', [ClientGalleryController::class, 'show']);
        Route::get('/media/{id}/download', MediaDownloadController::class);
        Route::post('/face-search', ClientFaceSearchController::class);
    });

    // Super admin routes
    Route::prefix('admin-super')->middleware('role:super_admin')->group(function () {
        Route::get('/tenants', [AdminTenantController::class, 'index']);
        Route::get('/tenants/{id}', [AdminTenantController::class, 'show']);
        Route::post('/tenants', [AdminTenantController::class, 'store']);
        Route::put('/tenants/{id}', [AdminTenantController::class, 'update']);
        Route::delete('/tenants/{id}', [AdminTenantController::class, 'destroy']);
        Route::get('/tenants/{id}/subscription', [AdminTenantController::class, 'subscription']);

        Route::get('/subscriptions', [AdminSubscriptionController::class, 'index']);
        Route::get('/subscriptions/{id}', [AdminSubscriptionController::class, 'show']);
        Route::post('/subscriptions', [AdminSubscriptionController::class, 'store']);
        Route::put('/subscriptions/{id}', [AdminSubscriptionController::class, 'update']);
        Route::delete('/subscriptions/{id}', [AdminSubscriptionController::class, 'destroy']);

        Route::get('/clients', [AdminClientController::class, 'index']);
        Route::get('/clients/{id}', [AdminClientController::class, 'show']);
        Route::post('/clients', [AdminClientController::class, 'store']);
        Route::put('/clients/{id}', [AdminClientController::class, 'update']);
        Route::delete('/clients/{id}', [AdminClientController::class, 'destroy']);

        Route::get('/galleries', [AdminGalleryController::class, 'index']);
        Route::get('/galleries/{id}', [AdminGalleryController::class, 'show']);
        Route::post('/galleries', [AdminGalleryController::class, 'store']);
        Route::put('/galleries/{id}', [AdminGalleryController::class, 'update']);
        Route::delete('/galleries/{id}', [AdminGalleryController::class, 'destroy']);

        Route::post('/upload', AdminUploadController::class);
    });
});
