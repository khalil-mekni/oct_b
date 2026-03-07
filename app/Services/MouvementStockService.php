<?php

namespace App\Services;

use App\Models\MouvementStock;
use App\Models\Stock;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class MouvementStockService
{
    public function createDraft(array $data): MouvementStock
    {
        $this->validateDraft($data);

        return MouvementStock::create([
            'code_mouvement' => $data['code_mouvement'] ?? null,
            'type_mouvement' => $data['type_mouvement'],
            'emballage_id' => $data['emballage_id'],
            'lot_id' => $data['lot_id'] ?? null,
            'entrepot_source_id' => $data['entrepot_source_id'] ?? null,
            'entrepot_destination_id' => $data['entrepot_destination_id'] ?? null,
            'quantite' => (float) $data['quantite'],
            'date_mouvement' => $data['date_mouvement'] ?? now(),
            'user_id' => Auth::id() ?? ($data['user_id'] ?? null),
            'statut' => 'BROUILLON',
        ])->refresh();
    }

    public function validateMovement(MouvementStock $m): MouvementStock
    {
        if ($m->statut === 'VALIDE') {
            return $m;
        }

        return DB::transaction(function () use ($m) {
            $this->validateBeforeApply($m);
            $this->applyToStocks($m);

            $m->update([
                'statut' => 'VALIDE',
                'date_mouvement' => $m->date_mouvement ?? now(),
                'user_id' => $m->user_id ?? Auth::id(),
            ]);

            return $m->refresh();
        });
    }

    public function deleteDraft(MouvementStock $m): bool
    {
        if ($m->statut === 'VALIDE') {
            throw new InvalidArgumentException("Impossible de supprimer un mouvement validé.");
        }

        return (bool) $m->delete();
    }

    private function applyToStocks(MouvementStock $m): void
    {
        $qty = (float) $m->quantite;

        if ($qty <= 0) {
            throw new InvalidArgumentException("quantite doit être > 0");
        }

        $emballageId = $m->emballage_id;
        $lotId = $m->lot_id;

        $inc = fn ($entrepotId) => $this->incrementStock($entrepotId, $emballageId, $lotId, $qty);
        $dec = fn ($entrepotId) => $this->decrementStock($entrepotId, $emballageId, $lotId, $qty);

        switch ($m->type_mouvement) {
            case 'ENT':
                if (!$m->entrepot_destination_id) {
                    throw new InvalidArgumentException("ENT: destination requise.");
                }
                $inc($m->entrepot_destination_id);
                break;

            case 'PRD':
                if (!$m->entrepot_source_id) {
                    throw new InvalidArgumentException("PRD: source requise.");
                }
                $dec($m->entrepot_source_id);
                break;

            case 'CDD':
                if (!$m->entrepot_source_id || !$m->entrepot_destination_id) {
                    throw new InvalidArgumentException("CDD: source et destination requis.");
                }
                $dec($m->entrepot_source_id);
                $inc($m->entrepot_destination_id);
                break;

            case 'PTE':
                if (!$m->entrepot_source_id) {
                    throw new InvalidArgumentException("PTE: source requise.");
                }
                $dec($m->entrepot_source_id);
                break;

            case 'SPL':
                $target = $m->entrepot_destination_id ?? $m->entrepot_source_id;
                if (!$target) {
                    throw new InvalidArgumentException("SPL: entrepot requis.");
                }
                $inc($target);
                break;

            default:
                throw new InvalidArgumentException("type_mouvement invalide.");
        }
    }

    private function incrementStock($entrepotId, $emballageId, $lotId, float $qty): void
    {
        $stock = Stock::firstOrCreate(
            [
                'entrepot_id' => $entrepotId,
                'emballage_id' => $emballageId,
                'lot_id' => $lotId,
            ],
            [
                'date_stock' => now(),
                'quantite_init' => 0,
                'quantite_entree' => 0,
                'quantite_sortie' => 0,
                'quantite_finale' => 0,
                'user_id' => Auth::id(),
            ]
        );

        $newEntree = (float) $stock->quantite_entree + $qty;
        $newFinale = (float) $stock->quantite_init + $newEntree - (float) $stock->quantite_sortie;

        $stock->update([
            'quantite_entree' => $newEntree,
            'quantite_finale' => $newFinale,
            'date_stock' => now(),
            'user_id' => Auth::id(),
        ]);
    }

    private function decrementStock($entrepotId, $emballageId, $lotId, float $qty): void
    {
        $stock = Stock::where('entrepot_id', $entrepotId)
            ->where('emballage_id', $emballageId)
            ->where('lot_id', $lotId)
            ->first();

        $available = (float) ($stock?->quantite_finale ?? 0);

        if ($available < $qty) {
            throw new InvalidArgumentException("Stock insuffisant (dispo=$available, demandé=$qty).");
        }

        $newSortie = (float) $stock->quantite_sortie + $qty;
        $newFinale = (float) $stock->quantite_init + (float) $stock->quantite_entree - $newSortie;

        $stock->update([
            'quantite_sortie' => $newSortie,
            'quantite_finale' => $newFinale,
            'date_stock' => now(),
            'user_id' => Auth::id(),
        ]);
    }

    private function validateDraft(array $data): void
    {
        $allowed = ['ENT', 'PRD', 'CDD', 'PTE', 'SPL'];

        if (empty($data['type_mouvement']) || !in_array($data['type_mouvement'], $allowed, true)) {
            throw new InvalidArgumentException("type_mouvement invalide.");
        }

        if (empty($data['emballage_id'])) {
            throw new InvalidArgumentException("emballage_id requis.");
        }

        if (!isset($data['quantite']) || (float) $data['quantite'] <= 0) {
            throw new InvalidArgumentException("quantite doit être > 0");
        }
    }

    private function validateBeforeApply(MouvementStock $m): void
    {
        if ($m->type_mouvement === 'ENT') {
            if (!$m->entrepot_destination_id) {
                throw new InvalidArgumentException("ENT: destination requise.");
            }
            return;
        }

        if (in_array($m->type_mouvement, ['PRD', 'PTE'], true)) {
            if (!$m->entrepot_source_id) {
                throw new InvalidArgumentException("source requise.");
            }
            if (!$m->lot_id) {
                throw new InvalidArgumentException("lot_id requis.");
            }
            return;
        }

        if ($m->type_mouvement === 'CDD') {
            if (!$m->entrepot_source_id || !$m->entrepot_destination_id) {
                throw new InvalidArgumentException("CDD: source et destination requis.");
            }
            if (!$m->lot_id) {
                throw new InvalidArgumentException("lot_id requis.");
            }
            return;
        }

        if ($m->type_mouvement === 'SPL') {
            $target = $m->entrepot_destination_id ?? $m->entrepot_source_id;
            if (!$target) {
                throw new InvalidArgumentException("SPL: entrepot requis.");
            }
            if (!$m->lot_id) {
                throw new InvalidArgumentException("lot_id requis.");
            }
        }
    }
}