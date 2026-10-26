<?php

namespace App\Providers;

use App\Contracts\SoapGenerationService;
use App\Contracts\TranscriptionService;
use App\Services\MockSoapGenerationService;
use App\Services\MockTranscriptionService;
use App\Services\OpenRouterSoapGenerationService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(TranscriptionService::class, MockTranscriptionService::class);
        $this->app->bind(SoapGenerationService::class, fn () => config('medscribe.soap_driver', env('MEDSCRIBE_SOAP_DRIVER', 'mock')) === 'openrouter'
            ? app(OpenRouterSoapGenerationService::class)
            : app(MockSoapGenerationService::class));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
