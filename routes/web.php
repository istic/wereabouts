<?php

use App\Http\Controllers\VenueController;
use Illuminate\Support\Facades\Route;

Route::get('/', [VenueController::class, 'index'])->name('home');
Route::get('/venue/{slug}', [VenueController::class, 'show'])->name('venue.show');
