<?php

namespace App\GraphQL\Queries\Dashboard;

use App\Models\Entrepot;

final class WarehouseCapacityStats
{
    public function __invoke($_, array $args)
    {
        return Entrepot::query()
            ->select([
                'id',
                'nom',
                'adresse',
                'capacite_totale',
                'stock_existant',
                'capacite_disponible',
                'statut',
            ])
            ->get()
            ->map(function (Entrepot $entrepot): array {
                $capaciteTotale = (float) $entrepot->capacite_totale;
                $stockExistant = (float) $entrepot->stock_existant;

                $fillRate = $capaciteTotale > 0
                    ? round(($stockExistant / $capaciteTotale) * 100, 2)
                    : 0.0;

                $level = match (true) {
                    $fillRate >= 90 => 'critical',
                    $fillRate >= 80 => 'warning',
                    default => 'normal',
                };

                return [
                    'id' => $entrepot->id,
                    'nom' => $entrepot->nom,
                    'adresse' => $entrepot->adresse,
                    'capacite_totale' => (float) $entrepot->capacite_totale,
                    'stock_existant' => (float) $entrepot->stock_existant,
                    'capacite_disponible' => (float) $entrepot->capacite_disponible,
                    'statut' => $entrepot->statut,
                    'fillRate' => $fillRate,
                    'level' => $level,
                ];
            })
            ->values()
            ->all();
    }
}