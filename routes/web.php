<?php

use App\Livewire\RouteOptimizer;
use Illuminate\Support\Facades\Route;

Route::get('/', RouteOptimizer::class)->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
