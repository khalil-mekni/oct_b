<?php

namespace App\Services;

use App\Models\Entrepot;
use RuntimeException;

class EntrepotService
{
    public function addStock(int $entrepotId, float $quantite): Entrepot
    {
        if ($quantite <= 0) {
            throw new RuntimeException("La quantité doit être supérieure à 0.");
        }

        $entrepot = Entrepot::query()
            ->lockForUpdate()
            ->findOrFail($entrepotId);

        $stockExistant = (float) $entrepot->stock_existant + $quantite;
        $capaciteTotale = (float) $entrepot->capacite_totale;
        $capaciteDisponible = $capaciteTotale - $stockExistant;

        if ($capaciteDisponible < 0) {
            throw new RuntimeException("Capacité disponible insuffisante dans l'entrepôt.");
        }

        $entrepot->update([
            'stock_existant' => $stockExistant,
            'capacite_disponible' => $capaciteDisponible,
        ]);

        return $entrepot->refresh();
    }

    public function removeStock(int $entrepotId, float $quantite): Entrepot
    {
        if ($quantite <= 0) {
            throw new RuntimeException("La quantité doit être supérieure à 0.");
        }

        $entrepot = Entrepot::query()
            ->lockForUpdate()
            ->findOrFail($entrepotId);

        $stockExistant = (float) $entrepot->stock_existant - $quantite;

        if ($stockExistant < 0) {
            throw new RuntimeException("Stock existant insuffisant dans l'entrepôt.");
        }

        $capaciteTotale = (float) $entrepot->capacite_totale;
        $capaciteDisponible = $capaciteTotale - $stockExistant;

        if ($capaciteDisponible > $capaciteTotale) {
            throw new RuntimeException("La capacité disponible ne peut pas dépasser la capacité totale.");
        }

        $entrepot->update([
            'stock_existant' => $stockExistant,
            'capacite_disponible' => $capaciteDisponible,
        ]);

        return $entrepot->refresh();
    }

    public function syncCapacity(int $entrepotId): Entrepot
    {
        $entrepot = Entrepot::query()
            ->lockForUpdate()
            ->findOrFail($entrepotId);

        $stockExistant = (float) $entrepot->stock_existant;
        $capaciteTotale = (float) $entrepot->capacite_totale;
        $capaciteDisponible = $capaciteTotale - $stockExistant;

        if ($capaciteDisponible < 0) {
            throw new RuntimeException("L'entrepôt dépasse sa capacité totale.");
        }

        $entrepot->update([
            'capacite_disponible' => $capaciteDisponible,
        ]);

        return $entrepot->refresh();
    }
}