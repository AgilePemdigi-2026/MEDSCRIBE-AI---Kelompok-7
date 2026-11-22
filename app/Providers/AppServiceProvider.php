<?php

namespace App\Providers;

use App\Contracts\SoapGenerationService;
use App\Models\Pengguna;
use App\Services\MockSoapGenerationService;
use App\Services\OpenRouterSoapGenerationService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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

        // Semua daftar berhalaman memakai navigasi halaman MedScribe, bukan tampilan bawaan Laravel.
        Paginator::defaultView('pagination.medscribe');

        // Jumlah pendaftaran tertunda untuk badge navigasi admin.
        View::composer('layouts.admin', function ($view) {
            $hospitalId = Auth::user()?->hospital_id;
            $pendingBadgeCount = $hospitalId
                ? Pengguna::where('hospital_id', $hospitalId)->where('approval_status', 'pending')->count()
                : 0;
            $view->with('pendingBadgeCount', $pendingBadgeCount);
            // Permintaan mutasi tenaga medis yang menunggu keputusan RS ini.
            $view->with('mutasiBadgeCount', $hospitalId ? \App\Models\MutasiTenagaMedis::masukUntuk((int) $hospitalId)->count() : 0);
        });
    }
}
