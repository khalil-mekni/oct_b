<?php

namespace App\Services;

use App\Models\Entrepot;
use App\Models\EntrepotLot;
use App\Models\Lot;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class EntrepotLotService
{
    public function addToEntrepot(
        int $entrepotId,
        int $lotId,
        int $emballageId,
        float $quantite
    ): EntrepotLot {
        if ($quantite <= 0) {
            throw new RuntimeException("La quantité à ajouter doit être supérieure à 0.");
        }

        return DB::transaction(function () use ($entrepotId, $lotId, $emballageId, $quantite) {
            $this->lockAndCheckEntrepot($entrepotId);
            $lot = $this->lockAndCheckLot($lotId, $emballageId);

            $entrepotLot = EntrepotLot::query()
                ->where('entrepot_id', $entrepotId)
                ->where('lot_id', $lotId)
                ->lockForUpdate()
                ->first();

            if ($entrepotLot) {
                $entrepotLot->update([
                    'quantite' => (float) $entrepotLot->quantite + $quantite,
                    'emballage_id' => $emballageId,
                ]);
            } else {
                $entrepotLot = EntrepotLot::create([
                    'entrepot_id' => $entrepotId,
                    'lot_id' => $lotId,
                    'emballage_id' => $emballageId,
                    'quantite' => $quantite,
                ]);
            }

            $this->syncEntrepotStock($entrepotId);

            return $entrepotLot->refresh();
        });
    }

    public function removeFromEntrepot(
        int $entrepotId,
        int $lotId,
        int $emballageId,
        float $quantite
    ): EntrepotLot {
        if ($quantite <= 0) {
            throw new RuntimeException("La quantité à retirer doit être supérieure à 0.");
        }

        return DB::transaction(function () use ($entrepotId, $lotId, $emballageId, $quantite) {
            $this->lockAndCheckEntrepot($entrepotId);
            $lot = $this->lockAndCheckLot($lotId, $emballageId);

            $entrepotLot = EntrepotLot::query()
                ->where('entrepot_id', $entrepotId)
                ->where('lot_id', $lotId)
                ->where('emballage_id', $emballageId)
                ->lockForUpdate()
                ->first();

            if (!$entrepotLot) {
                throw new RuntimeException(
                    "Le lot {$lot->code_lot} n'existe pas dans l'entrepôt sélectionné."
                );
            }

            $nouvelleQuantite = (float) $entrepotLot->quantite - $quantite;

            if ($nouvelleQuantite < 0) {
                throw new RuntimeException(
                    "Quantité insuffisante pour le lot {$lot->code_lot} dans l'entrepôt. Disponible: {$entrepotLot->quantite}, demandé: {$quantite}."
                );
            }

            if ($nouvelleQuantite == 0.0) {
                $entrepotLot->delete();

                $this->syncEntrepotStock($entrepotId);

                return new EntrepotLot([
                    'entrepot_id' => $entrepotId,
                    'lot_id' => $lotId,
                    'emballage_id' => $emballageId,
                    'quantite' => 0,
                ]);
            }

            $entrepotLot->update([
                'quantite' => $nouvelleQuantite,
            ]);

            $this->syncEntrepotStock($entrepotId);

            return $entrepotLot->refresh();
        });
    }

    public function getDisponible(
        int $entrepotId,
        int $lotId,
        int $emballageId
    ): float {
        $quantite = EntrepotLot::query()
            ->where('entrepot_id', $entrepotId)
            ->where('lot_id', $lotId)
            ->where('emballage_id', $emballageId)
            ->value('quantite');

        return $quantite !== null ? (float) $quantite : 0.0;
    }

    public function existsInEntrepot(
        int $entrepotId,
        int $lotId,
        int $emballageId
    ): bool {
        return EntrepotLot::query()
            ->where('entrepot_id', $entrepotId)
            ->where('lot_id', $lotId)
            ->where('emballage_id', $emballageId)
            ->exists();
    }

    public function listLotsByEntrepot(
        int $entrepotId,
        ?int $emballageId = null
    ): Collection {
        return EntrepotLot::query()
            ->with(['lot', 'emballage', 'entrepot'])
            ->where('entrepot_id', $entrepotId)
            ->when($emballageId !== null, fn ($q) => $q->where('emballage_id', $emballageId))
            ->where('quantite', '>', 0)
            ->orderByDesc('id')
            ->get();
    }

    public function listEntrepotsByLot(int $lotId): Collection
    {
        return EntrepotLot::query()
            ->with(['lot', 'emballage', 'entrepot'])
            ->where('lot_id', $lotId)
            ->where('quantite', '>', 0)
            ->orderByDesc('id')
            ->get();
    }

    public function syncEntrepotStock(int $entrepotId): Entrepot
    {
        return DB::transaction(function () use ($entrepotId) {
            $entrepot = Entrepot::query()
                ->lockForUpdate()
                ->findOrFail($entrepotId);

            $stockExistant = (float) EntrepotLot::query()
                ->where('entrepot_id', $entrepotId)
                ->sum('quantite');

            $capaciteTotale = (float) $entrepot->capacite_totale;
            $capaciteDisponible = $capaciteTotale - $stockExistant;

            if ($capaciteDisponible < 0) {
                throw new RuntimeException(
                    "L'entrepôt dépasse sa capacité totale. Capacité totale: {$capaciteTotale}, stock existant: {$stockExistant}."
                );
            }

            $entrepot->update([
                'stock_existant' => $stockExistant,
                'capacite_disponible' => $capaciteDisponible,
            ]);

            return $entrepot->refresh();
        });
    }

    public function validateAvailableQuantity(
        int $entrepotId,
        int $lotId,
        int $emballageId,
        float $quantiteDemandee
    ): void {
        if ($quantiteDemandee <= 0) {
            throw new RuntimeException("La quantité demandée doit être supérieure à 0.");
        }

        $disponible = $this->getDisponible(
            entrepotId: $entrepotId,
            lotId: $lotId,
            emballageId: $emballageId
        );

        $lot = Lot::findOrFail($lotId);

        if ($disponible <= 0) {
            throw new RuntimeException(
                "Le lot {$lot->code_lot} n'est pas disponible dans l'entrepôt sélectionné."
            );
        }

        if ($quantiteDemandee > $disponible) {
            throw new RuntimeException(
                "Quantité insuffisante pour le lot {$lot->code_lot}. Maximum autorisé: {$disponible}."
            );
        }
    }

    private function lockAndCheckEntrepot(int $entrepotId): Entrepot
    {
        return Entrepot::query()
            ->lockForUpdate()
            ->findOrFail($entrepotId);
    }

    private function lockAndCheckLot(int $lotId, int $emballageId): Lot
    {
        $lot = Lot::query()
            ->lockForUpdate()
            ->findOrFail($lotId);

        if ((int) $lot->emballage_id !== $emballageId) {
            throw new RuntimeException(
                "Le lot {$lot->code_lot} n'appartient pas à l'emballage sélectionné."
            );
        }

        return $lot;
    }
}