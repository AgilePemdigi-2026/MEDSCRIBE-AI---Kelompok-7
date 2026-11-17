<?php

namespace App\Providers;

use App\Contracts\SoapGenerationService;
use App\Services\MockSoapGenerationService;
use App\Services\OpenRouterSoapGenerationService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SoapGenerationService::class, fn () => config('medscribe.soap_driver', env('MEDSCRIBE_SOAP_DRIVER', 'mock')) === 'openrouter'
            ? app(OpenRouterSoapGenerationService::class)
            : app(MockSoapGenerationService::class));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Coolify terminates TLS at the proxy. Keep generated URLs HTTPS even
        // when the application container receives the request over HTTP.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}
