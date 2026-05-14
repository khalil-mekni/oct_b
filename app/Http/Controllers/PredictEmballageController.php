<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PredictionEmballageController extends Controller
{
    public function predict(Request $request)
    {
        $payload = $request->validate([
            'date_prediction' => 'required|string',

            'emballage_id' => 'required|integer',
            'entrepot_id' => 'required|integer',

            'type_emballage' => 'required|string',
            'region' => 'required|string',

            'prix_unitaire' => 'required|numeric',
            'capacite_totale' => 'required|numeric',

            'stock_initial' => 'required|numeric',
            'stock_final' => 'required|numeric',

            'reception_ent' => 'nullable|numeric',
            'transfert_in_cdd' => 'nullable|numeric',
            'transfert_out_cdd' => 'nullable|numeric',

            'contrat_actif' => 'nullable|integer',
            'commandes_en_cours' => 'nullable|integer',

            'consommation_j_1' => 'nullable|numeric',
            'consommation_j_7' => 'nullable|numeric',
            'consommation_j_30' => 'nullable|numeric',

            'rolling_mean_7j' => 'nullable|numeric',
            'rolling_mean_30j' => 'nullable|numeric',
            'rolling_std_30j' => 'nullable|numeric',
        ]);

        $response = Http::post('http://127.0.0.1:8001/predict', $payload);

        if (!$response->successful()) {
            return response()->json([
                'message' => 'Erreur API ML',
                'error' => $response->body(),
            ], 500);
        }

        return response()->json($response->json());
    }
}