<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EtlController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('etl')->group(function () {
    Route::post('/upload', [EtlController::class, 'upload']);
    Route::get('/batches', [EtlController::class, 'getBatches']); // BARU
    Route::get('/stats', [EtlController::class, 'getStats']); // BARU
    Route::get('/batch/{id}', [EtlController::class, 'getBatch']);
    Route::get('/batch/{id}/results', [EtlController::class, 'getResults']);
});