<?php

use App\Http\Controllers\CanvaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PriceController;
use App\Http\Controllers\SyncController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])
    ->name('dashboard');

/*
|--------------------------------------------------------------------------
| Data Harga
|--------------------------------------------------------------------------
*/

Route::get('/harga/jawa', [PriceController::class, 'jawa'])
    ->name('prices.jawa');

Route::get('/harga/kalimantan', [PriceController::class, 'kalimantan'])
    ->name('prices.kalimantan');

Route::get('/harga/sumatera', [PriceController::class, 'sumatera'])
    ->name('prices.sumatera');

Route::get('/harga/logam-mulia', [PriceController::class, 'logamMulia'])
    ->name('prices.logam-mulia');

/*
|--------------------------------------------------------------------------
| Canva
|--------------------------------------------------------------------------
*/

Route::get('/canva/connect', [CanvaController::class, 'connect'])
    ->name('canva.connect');

Route::get('/canva/callback', [CanvaController::class, 'callback'])
    ->name('canva.callback');

Route::get('/canva/status', [CanvaController::class, 'status'])
    ->name('canva.status');

Route::get('/canva/dataset', [CanvaController::class, 'dataset'])
    ->name('canva.dataset');

Route::get('/canva/dataset/{designId}', [CanvaController::class, 'datasetByDesign'])
    ->name('canva.dataset.design');

/*
|--------------------------------------------------------------------------
| Synchronization
|--------------------------------------------------------------------------
*/

Route::post('/sync', [SyncController::class, 'sync'])
    ->name('sync');

Route::get('/sync/status/{jobId}', [SyncController::class, 'status'])
    ->name('sync.status');