<?php

namespace App\Providers;

use App\Services\FaceRecognitionService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(FaceRecognitionService::class, function () {
            $config = config('services.face_recognition', []);
            return new FaceRecognitionService(
                $config['url'] ?? 'http://127.0.0.1:5000',
                $config['timeout'] ?? 120
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
