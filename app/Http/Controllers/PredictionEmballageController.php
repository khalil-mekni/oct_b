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
            'annee'        => 'required|integer',
            'mois'         => 'required|integer|min:1|max:12',
            'emballage_id' => 'required|integer',
            'entrepot_id'  => 'required|integer',
        ]);

        try {
            $startDate = \Carbon\Carbon::create($validated['annee'], $validated['mois'], 1);
            $daysInMonth = $startDate->daysInMonth;
            
            $allPayloads = [];
            for ($d = 0; $d < $daysInMonth; $d++) {
                $currentDate = $startDate->copy()->addDays($d);
                $allPayloads[] = [
                    'date_prediction' => $currentDate->format('Y-m-d'),
                    'emballage_id'    => (int)$validated['emballage_id'],
                    'entrepot_id'     => (int)$validated['entrepot_id'],
                    'prix_unitaire'   => 0, // Fallback
                ];
            }

            $response = Http::timeout(60)
                ->post("{$this->fastApiUrl}/predict-batch", $allPayloads);

            if ($response->failed()) {
                return response()->json(['error' => 'Erreur service IA'], $response->status());
            }

            $predictions = $response->json();
            $totalQuantity = collect($predictions)->sum('quantite_predite');

            return response()->json([
                'quantite_predite' => round($totalQuantity, 2),
                'unite'            => 'unités',
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ─── Prédiction batch (tous entrepôts / tous emballages) ─────────────────
    public function predictBatch(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'annee'        => 'required|integer',
            'mois'         => 'required|integer|min:1|max:12',
            'emballage_id' => 'nullable|integer',
            'entrepot_id'  => 'nullable|integer',
        ]);

        try {
            $emballages = $validated['emballage_id'] 
                ? [\App\Models\Emballage::findOrFail($validated['emballage_id'])]
                : \App\Models\Emballage::all();
                
            $entrepots = $validated['entrepot_id']
                ? [\App\Models\Entrepot::findOrFail($validated['entrepot_id'])]
                : \App\Models\Entrepot::all();

            $startDate = \Carbon\Carbon::create($validated['annee'], $validated['mois'], 1);
            $daysInMonth = $startDate->daysInMonth;

            $allPayloads = [];
            foreach ($emballages as $emb) {
                foreach ($entrepots as $ent) {
                    for ($d = 0; $d < $daysInMonth; $d++) {
                        $allPayloads[] = [
                            'date_prediction' => $startDate->copy()->addDays($d)->format('Y-m-d'),
                            'emballage_id'    => $emb->id,
                            'entrepot_id'     => $ent->id,
                        ];
                    }
                }
            }

            $response = Http::timeout(120)
                ->post("{$this->fastApiUrl}/predict-batch", $allPayloads);

            if ($response->failed()) {
                return response()->json(['error' => 'Erreur service IA'], $response->status());
            }

            // Pour simplifier, on renvoie les résultats bruts groupés par emballage/entrepot
            // ou on peut structurer la réponse comme attendu par le front
            return response()->json([
                'total' => count($allPayloads),
                'predictions' => $response->json(),
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
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