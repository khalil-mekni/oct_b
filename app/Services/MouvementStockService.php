<?php

namespace App\Services;

use App\Models\Entrepot;
use App\Models\Lot;
use App\Models\MouvementStock;
use App\Models\Stock;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class MouvementStockService
{
    private const MANUAL_TYPES = ['PRD', 'CDD', 'PTE', 'SPL'];
    private const STOCK_SENSES = ['entree', 'sortie'];

    public function createDraft(array $data): MouvementStock
    {
        $this->validateDraft($data);

        return MouvementStock::create([
            'code_mouvement' => $data['code_mouvement'] ?? $this->generateCodeMouvement($data['type_mouvement']),
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
            $m->refresh();

            $this->validateBeforeApply($m);
            $sourceLot = $this->lockAndValidateLotForMovement($m);

            match ($m->type_mouvement) {
                'PRD' => $this->applyProduction($m, $sourceLot),
                'CDD' => $this->applyTransfer($m, $sourceLot),
                'PTE' => $this->applyPerte($m, $sourceLot),
                'SPL' => $this->applySurplus($m),
                default => throw new InvalidArgumentException("Type de mouvement non supporté : {$m->type_mouvement}"),
            };

            $m->update([
                'statut' => 'VALIDE',
                'user_id' => $m->user_id ?? Auth::id(),
                'date_mouvement' => $m->date_mouvement ?? now(),
            ]);

            return $m->refresh();
        });
    }

    public function deleteDraft(MouvementStock $m): bool
    {
        if ($m->statut === 'VALIDE') {
            throw new InvalidArgumentException('Impossible de supprimer un mouvement validé.');
        }

        return (bool) $m->delete();
    }

    private function validateDraft(array $data): void
    {
        if (empty($data['type_mouvement']) || !in_array($data['type_mouvement'], self::MANUAL_TYPES, true)) {
            throw new InvalidArgumentException('type_mouvement invalide.');
        }

        if (empty($data['emballage_id'])) {
            throw new InvalidArgumentException('emballage_id requis.');
        }

        if (!isset($data['quantite']) || (float) $data['quantite'] <= 0) {
            throw new InvalidArgumentException('quantite doit être > 0.');
        }

        if (($data['type_mouvement'] ?? null) === 'CDD') {
            if (
                !empty($data['entrepot_source_id']) &&
                !empty($data['entrepot_destination_id']) &&
                (int) $data['entrepot_source_id'] === (int) $data['entrepot_destination_id']
            ) {
                throw new InvalidArgumentException('CDD: la source et la destination doivent être différentes.');
            }
        }
    }

    private function validateBeforeApply(MouvementStock $m): void
    {
        if (!in_array($m->type_mouvement, self::MANUAL_TYPES, true)) {
            throw new InvalidArgumentException('type_mouvement invalide.');
        }

        if ((float) $m->quantite <= 0) {
            throw new InvalidArgumentException('quantite doit être > 0.');
        }

        switch ($m->type_mouvement) {
            case 'PRD':
            case 'PTE':
                if (!$m->entrepot_source_id) {
                    throw new InvalidArgumentException("{$m->type_mouvement}: entrepot_source_id requis.");
                }
                if (!$m->lot_id) {
                    throw new InvalidArgumentException("{$m->type_mouvement}: lot_id requis.");
                }
                break;

            case 'CDD':
                if (!$m->entrepot_source_id) {
                    throw new InvalidArgumentException('CDD: entrepot_source_id requis.');
                }
                if (!$m->entrepot_destination_id) {
                    throw new InvalidArgumentException('CDD: entrepot_destination_id requis.');
                }
                if ((int) $m->entrepot_source_id === (int) $m->entrepot_destination_id) {
                    throw new InvalidArgumentException('CDD: la source et la destination doivent être différentes.');
                }
                if (!$m->lot_id) {
                    throw new InvalidArgumentException('CDD: lot_id requis.');
                }
                break;

            case 'SPL':
                if (!$m->entrepot_destination_id) {
                    throw new InvalidArgumentException('SPL: entrepot_destination_id requis.');
                }
                break;
        }
    }

    private function lockAndValidateLotForMovement(MouvementStock $m): ?Lot
    {
        if (!$m->lot_id) {
            return null;
        }

        $lot = Lot::whereKey($m->lot_id)->lockForUpdate()->first();

        if (!$lot) {
            throw new InvalidArgumentException('Lot introuvable.');
        }

        if ((int) $lot->emballage_id !== (int) $m->emballage_id) {
            throw new InvalidArgumentException("Le lot sélectionné n'appartient pas à l'emballage choisi.");
        }

        if (in_array($m->type_mouvement, ['PRD', 'CDD', 'PTE'], true)) {
            $available = $this->getAvailableStock(
                entrepotId: (int) $m->entrepot_source_id,
                emballageId: (int) $m->emballage_id,
                lotId: (int) $m->lot_id
            );

            if ($available < (float) $m->quantite) {
                throw new InvalidArgumentException("Stock insuffisant dans l'entrepôt source. Disponible={$available}, demandé={$m->quantite}.");
            }

            if ((float) $lot->quantite < (float) $m->quantite) {
                throw new InvalidArgumentException("Quantité insuffisante dans le lot. Disponible={$lot->quantite}, demandé={$m->quantite}.");
            }
        }

        return $lot;
    }

    private function applyProduction(MouvementStock $m, Lot $sourceLot): void
    {
        $qty = (float) $m->quantite;

        $this->createStockLine(
            entrepotId: (int) $m->entrepot_source_id,
            emballageId: (int) $m->emballage_id,
            lotId: (int) $sourceLot->id,
            quantite: $qty,
            sens: 'SORTIE',
            date: $m->date_mouvement,
            userId: Auth::id() ?? $m->user_id
        );

        $this->decreaseLotQuantity($sourceLot, $qty);
        $this->increaseEntrepotAvailableCapacity((int) $m->entrepot_source_id, $qty);
    }

    private function applyPerte(MouvementStock $m, Lot $sourceLot): void
    {
        $qty = (float) $m->quantite;

        $this->createStockLine(
            entrepotId: (int) $m->entrepot_source_id,
            emballageId: (int) $m->emballage_id,
            lotId: (int) $sourceLot->id,
            quantite: $qty,
            sens: 'sortie',
            date: $m->date_mouvement,
            userId: Auth::id() ?? $m->user_id
        );

        $this->decreaseLotQuantity($sourceLot, $qty);
        $this->increaseEntrepotAvailableCapacity((int) $m->entrepot_source_id, $qty);
    }

    private function applyTransfer(MouvementStock $m, Lot $sourceLot): void
    {
        $qty = (float) $m->quantite;
        $userId = Auth::id() ?? $m->user_id;

        $this->createStockLine(
            entrepotId: (int) $m->entrepot_source_id,
            emballageId: (int) $m->emballage_id,
            lotId: (int) $sourceLot->id,
            quantite: $qty,
            sens: 'sortie',
            date: $m->date_mouvement,
            userId: $userId
        );

        $this->decreaseLotQuantity($sourceLot, $qty);
        $this->increaseEntrepotAvailableCapacity((int) $m->entrepot_source_id, $qty);

        $destinationLot = $this->createTransferDestinationLot(
            sourceLot: $sourceLot,
            quantity: $qty,
            date: $m->date_mouvement,
            userId: $userId,
            sourceEntrepotId: (int) $m->entrepot_source_id,
            destinationEntrepotId: (int) $m->entrepot_destination_id
        );

        $this->createStockLine(
            entrepotId: (int) $m->entrepot_destination_id,
            emballageId: (int) $m->emballage_id,
            lotId: (int) $destinationLot->id,
            quantite: $qty,
            sens: 'entree',
            date: $m->date_mouvement,
            userId: $userId
        );

        $this->decreaseEntrepotAvailableCapacity((int) $m->entrepot_destination_id, $qty);
    }

    private function applySurplus(MouvementStock $m): void
    {
        $qty = (float) $m->quantite;
        $userId = Auth::id() ?? $m->user_id;

        $targetLot = null;

        if ($m->lot_id) {
            $targetLot = Lot::whereKey($m->lot_id)->lockForUpdate()->first();

            if (!$targetLot) {
                throw new InvalidArgumentException('Lot introuvable.');
            }

            if ((int) $targetLot->emballage_id !== (int) $m->emballage_id) {
                throw new InvalidArgumentException("Le lot sélectionné n'appartient pas à l'emballage choisi.");
            }

            $targetLot->update([
                'quantite' => (float) $targetLot->quantite + $qty,
                'date_mvt' => $m->date_mouvement ?? now(),
                'user_id' => $userId,
            ]);
        } else {
            $targetLot = Lot::create([
                'code_lot' => $this->generateLotCode('SPL'),
                'emballage_id' => $m->emballage_id,
                'quantite' => $qty,
                'user_id' => $userId,
                'date_mvt' => $m->date_mouvement ?? now(),
                'commentaire' => 'Lot créé automatiquement suite à un surplus.',
            ]);
        }

        $this->createStockLine(
            entrepotId: (int) $m->entrepot_destination_id,
            emballageId: (int) $m->emballage_id,
            lotId: (int) $targetLot->id,
            quantite: $qty,
            sens: 'entree',
            date: $m->date_mouvement,
            userId: $userId
        );

        $this->decreaseEntrepotAvailableCapacity((int) $m->entrepot_destination_id, $qty);
    }

    private function createTransferDestinationLot(
        Lot $sourceLot,
        float $quantity,
        $date,
        ?int $userId,
        int $sourceEntrepotId,
        int $destinationEntrepotId
    ): Lot {
        return Lot::create([
            'code_lot' => $this->generateLotCode('CDD'),
            'emballage_id' => $sourceLot->emballage_id,
            'quantite' => $quantity,
            'user_id' => $userId,
            'date_mvt' => $date ?? now(),
            'commentaire' => "Lot créé automatiquement suite à transfert dépôt {$sourceEntrepotId} vers dépôt {$destinationEntrepotId}. Lot source #{$sourceLot->id}.",
        ]);
    }

    private function decreaseLotQuantity(Lot $lot, float $qty): void
    {
        $current = (float) $lot->quantite;

        if ($current < $qty) {
            throw new InvalidArgumentException("Quantité insuffisante dans le lot. Disponible={$current}, demandé={$qty}.");
        }

        $lot->update([
            'quantite' => $current - $qty,
            'date_mvt' => now(),
            'user_id' => Auth::id() ?? $lot->user_id,
        ]);
    }

    private function increaseEntrepotAvailableCapacity(int $entrepotId, float $qty): void
    {
        $entrepot = Entrepot::lockForUpdate()->find($entrepotId);

        if (!$entrepot || !isset($entrepot->capacite_disponible)) {
            return;
        }

        $entrepot->update([
            'capacite_disponible' => (float) $entrepot->capacite_disponible + $qty,
        ]);
    }

    private function decreaseEntrepotAvailableCapacity(int $entrepotId, float $qty): void
    {
        $entrepot = Entrepot::lockForUpdate()->find($entrepotId);

        if (!$entrepot || !isset($entrepot->capacite_disponible)) {
            return;
        }

        $entrepot->update([
            'capacite_disponible' => max(0, (float) $entrepot->capacite_disponible - $qty),
        ]);
    }

    private function createStockLine(
        int $entrepotId,
        int $emballageId,
        ?int $lotId,
        float $quantite,
        string $sens,
        $date,
        ?int $userId
    ): Stock {
        if (!in_array($sens, self::STOCK_SENSES, true)) {
            throw new InvalidArgumentException('sens invalide.');
        }

        return Stock::create([
            'entrepot_id' => $entrepotId,
            'emballage_id' => $emballageId,
            'lot_id' => $lotId,
            'date_stock' => $date ?? now(),
            'quantite' => $quantite,
            'sens' => $sens,
            'user_id' => $userId,
        ]);
    }

    public function getAvailableStock(int $entrepotId, int $emballageId, ?int $lotId = null): float
    {
        $query = Stock::query()
            ->where('entrepot_id', $entrepotId)
            ->where('emballage_id', $emballageId);

        if ($lotId !== null) {
            $query->where('lot_id', $lotId);
        } else {
            $query->whereNull('lot_id');
        }

        $entrees = (clone $query)->where('sens', 'entree')->sum('quantite');
        $sorties = (clone $query)->where('sens', 'sortie')->sum('quantite');

        return (float) $entrees - (float) $sorties;
    }

    private function generateCodeMouvement(string $type): string
    {
        $date = now()->format('YmdHis');
        $suffix = strtoupper(substr(uniqid(), -4));

        return "{$type}-{$date}-{$suffix}";
    }

    private function generateLotCode(string $prefix): string
    {
        return "{$prefix}-LOT-" . now()->format('YmdHis') . '-' . strtoupper(substr(uniqid(), -4));
    }
}