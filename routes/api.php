<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OcrController;

Route::post('/ocr/analyze', [OcrController::class, 'analyze']);