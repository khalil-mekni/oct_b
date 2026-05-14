<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OcrController;
use App\Http\Controllers\Api\PredictionEmballageController;

Route::post('/ocr/analyze', [OcrController::class, 'analyze']);
Route::prefix('ai')->group(function () {
 
    // Health check du service IA
    Route::get('health', [PredictionEmballageController::class, 'health']);
 
    // Prédiction unitaire : 1 emballage × 1 entrepôt
    // POST /api/ai/predict-emballage
    Route::post('predict-emballage', [PredictionEmballageController::class, 'predict']);
 
    // Prédiction batch : N emballages × N entrepôts pour un mois/année
    // POST /api/ai/predict-emballage/batch
    Route::post('predict-emballage/batch', [PredictionEmballageController::class, 'predictBatch']);
});