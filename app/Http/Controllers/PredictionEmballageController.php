<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * PredictionEmballageController
 *
 * Fait le pont entre GraphQL Laravel et l'API FastAPI (Python ML).
 * Endpoint FastAPI : http://127.0.0.1:8001
 */
class PredictionEmballageController extends Controller
{
    private string $fastApiUrl;

    public function __construct()
    {
        $this->fastApiUrl = config('services.fastapi.url', 'http://127.0.0.1:8001');
    }

    // ─── Prédiction unitaire (1 emballage + 1 entrepôt) ─────────────────────
    public function predict(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'annee'                          => 'required|integer|min:2023',
            'mois'                           => 'required|integer|min:1|max:12',
            'emballage_id'                   => 'required|integer|min:1|max:4',
            'entrepot_id'                    => 'required|integer|min:1|max:17',
            'consommation_mois'              => 'required|numeric|min:0',
            'consommation_mois_precedent'    => 'nullable|numeric|min:0',
            'moyenne_3_mois'                 => 'nullable|numeric|min:0',
            'stock_fin_mois'                 => 'nullable|numeric|min:0',
            'quantite_a_commander_estimee'   => 'nullable|numeric|min:0',
        ]);

        try {
            $response = Http::timeout(30)
                ->post("{$this->fastApiUrl}/predict", $validated);

            if ($response->failed()) {
                Log::error('FastAPI predict error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return response()->json([
                    'error' => 'Erreur service IA',
                    'detail' => $response->json('detail', 'Erreur inconnue'),
                ], $response->status());
            }

            return response()->json($response->json());

        } catch (\Exception $e) {
            Log::error('FastAPI unreachable', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Service IA indisponible'], 503);
        }
    }

    // ─── Prédiction batch (tous entrepôts / tous emballages) ─────────────────
    public function predictBatch(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'annee'        => 'required|integer|min:2023',
            'mois'         => 'required|integer|min:1|max:12',
            'emballage_id' => 'nullable|integer|min:1|max:4',
            'entrepot_id'  => 'nullable|integer|min:1|max:17',
        ]);

        try {
            $response = Http::timeout(60)
                ->post("{$this->fastApiUrl}/predict-batch", $validated);

            if ($response->failed()) {
                return response()->json([
                    'error' => 'Erreur service IA batch',
                    'detail' => $response->json('detail', 'Erreur inconnue'),
                ], $response->status());
            }

            return response()->json($response->json());

        } catch (\Exception $e) {
            Log::error('FastAPI batch unreachable', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Service IA indisponible'], 503);
        }
    }

    // ─── Health check FastAPI ─────────────────────────────────────────────────
    public function health(): \Illuminate\Http\JsonResponse
    {
        try {
            $response = Http::timeout(5)->get("{$this->fastApiUrl}/");
            return response()->json([
                'fastapi_status' => $response->ok() ? 'ok' : 'error',
                'fastapi_data'   => $response->json(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['fastapi_status' => 'unreachable'], 503);
        }
    }
}