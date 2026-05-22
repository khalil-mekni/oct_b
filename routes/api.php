<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OcrController;
use App\Http\Controllers\Api\PredictionEmballageController;

Route::post('/ocr/analyze', [OcrController::class, 'analyze']);
Route::prefix('ai')->group(function () {

    Route::get('health', [PredictionEmballageController::class, 'health']);

    Route::middleware('token.interceptor')->group(function () {
        Route::post('predict-emballage', [PredictionEmballageController::class, 'predictEmballage']);
        Route::post('predict-emballage-period', [PredictionEmballageController::class, 'predictEmballagePeriod']);
        Route::post('predict-emballage-all', [PredictionEmballageController::class, 'predictAll']);
    });
});