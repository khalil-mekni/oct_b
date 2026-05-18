<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class PredictionEmballageService
{
    public function predictBatch(array $payloads): array
    {
        $response = Http::timeout(60)->post(
            'http://127.0.0.1:8001/predict-batch',
            $payloads
        );

        if (!$response->successful()) {
            throw new Exception('Erreur API ML Batch : ' . $response->body());
        }

        return $response->json();
    }
}