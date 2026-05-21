<?php

use App\Livewire\Entregadores\Index as EntregadoresIndex;
use App\Livewire\Entregas\Index as EntregasIndex;
use App\Livewire\Roteirizar;
use App\Livewire\RouteOptimizer;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::get('entregadores', EntregadoresIndex::class)->name('entregadores.index');
    Route::get('entregadores/{entregador}/roteirizar', Roteirizar::class)
        ->name('entregadores.roteirizar');
    Route::get('entregas', EntregasIndex::class)->name('entregas.index');
    Route::get('otimizador-manual', RouteOptimizer::class)->name('otimizador.manual');
});

require __DIR__.'/settings.php';
