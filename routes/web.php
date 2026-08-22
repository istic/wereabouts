<?php

use App\Http\Controllers\MapController;
use App\Http\Controllers\VenueController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Vite;

Route::get('/', [VenueController::class, 'index'])->name('home');
Route::get('/venue/{slug}', [VenueController::class, 'show'])->name('venue.show');
Route::get('/map', [MapController::class, 'index'])->name('map.index');

Route::get('site.webmanifest', function () {
    $color = config('branding.colors.'.app()->environment(), config('branding.default_color'));

    return response()->json([
        'name' => config('app.name', 'Wereabouts'),
        'short_name' => config('app.name', 'Wereabouts'),
        'start_url' => '/',
        'display' => 'standalone',
        'theme_color' => $color,
        'background_color' => $color,
        'icons' => [
            [
                'src' => Vite::asset('resources/icons/web-app-manifest-192x192.png'),
                'sizes' => '192x192',
                'type' => 'image/png',
            ],
            [
                'src' => Vite::asset('resources/icons/web-app-manifest-512x512.png'),
                'sizes' => '512x512',
                'type' => 'image/png',
            ],
        ],
    ])->header('Content-Type', 'application/manifest+json');
})->name('manifest.webmanifest');
