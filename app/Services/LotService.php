<?php

namespace App\Services;

use App\Models\Lot;
use App\Models\MouvementStock;
use App\Models\Stock;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class MouvementStockService
{
    private const TYPES = ['ENT', 'PRD', 'CDD', 'PTE', 'SPL', 'EMC'];
    private const SENS = ['ENTREE', 'SORTIE'];

    public function createDraft(array $data): MouvementStock
    {
        $this->validateDraft($data);

        return MouvementStock::create([
            'code_mouvement' => $data['code_mouvement'] ?? $this->generateCodeMouvement(),
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
            $this->assertLotBelongsToEmballage($m);
            $this->assertStockAvailability($m);
            $this->writeStockEntries($m);

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
            throw new InvalidArgumentException('Impossible de supprimer un mouvement validé.');
        }

        return (bool) $m->delete();
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

        $entrees = (clone $query)
            ->where('sens', 'ENTREE')
            ->sum('quantite');

        $sorties = (clone $query)
            ->where('sens', 'SORTIE')
            ->sum('quantite');

        return (float) $entrees - (float) $sorties;
    }

    private function validateDraft(array $data): void
    {
        if (empty($data['type_mouvement']) || !in_array($data['type_mouvement'], self::TYPES, true)) {
            throw new InvalidArgumentException('type_mouvement invalide.');
        }

        if (empty($data['emballage_id'])) {
            throw new InvalidArgumentException('emballage_id requis.');
        }

        if (!isset($data['quantite']) || (float) $data['quantite'] <= 0) {
            throw new InvalidArgumentException('quantite doit être > 0.');
        }

        if (
            ($data['type_mouvement'] ?? null) === 'CDD'
            && !empty($data['entrepot_source_id'])
            && !empty($data['entrepot_destination_id'])
            && (int) $data['entrepot_source_id'] === (int) $data['entrepot_destination_id']
        ) {
            throw new InvalidArgumentException('CDD: la source et la destination doivent être différentes.');
        }
    }

    private function validateBeforeApply(MouvementStock $m): void
    {
        if (!in_array($m->type_mouvement, self::TYPES, true)) {
            throw new InvalidArgumentException('type_mouvement invalide.');
        }

        if ((float) $m->quantite <= 0) {
            throw new InvalidArgumentException('quantite doit être > 0.');
        }

        switch ($m->type_mouvement) {
            case 'ENT':
            case 'PRD':
            case 'EMC':
                if (!$m->entrepot_destination_id) {
                    throw new InvalidArgumentException("{$m->type_mouvement}: entrepot_destination_id requis.");
                }
                break;

            case 'PTE':
            case 'SPL':
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
        }
    }

    private function assertLotBelongsToEmballage(MouvementStock $m): void
    {
        if (!$m->lot_id) {
            return;
        }

        $lot = Lot::find($m->lot_id);

        if (!$lot) {
            throw new InvalidArgumentException('Lot introuvable.');
        }

        if ((int) $lot->emballage_id !== (int) $m->emballage_id) {
            throw new InvalidArgumentException("Le lot sélectionné n'appartient pas à l'emballage choisi.");
        }
    }

    private function assertStockAvailability(MouvementStock $m): void
    {
        $qty = (float) $m->quantite;

        switch ($m->type_mouvement) {
            case 'PTE':
            case 'SPL':
            case 'CDD':
                $available = $this->getAvailableStock(
                    (int) $m->entrepot_source_id,
                    (int) $m->emballage_id,
                    $m->lot_id ? (int) $m->lot_id : null
                );

                if ($available < $qty) {
                    throw new InvalidArgumentException(
                        "Stock insuffisant. Disponible={$available}, demandé={$qty}."
                    );
                }
                break;

            case 'ENT':
            case 'PRD':
            case 'EMC':
                break;
        }
    }

    private function writeStockEntries(MouvementStock $m): void
    {
        $qty = (float) $m->quantite;
        $date = $m->date_mouvement ?? now();
        $userId = Auth::id() ?? $m->user_id;

        switch ($m->type_mouvement) {
            case 'ENT':
            case 'PRD':
            case 'EMC':
                $this->createStockLine(
                    entrepotId: (int) $m->entrepot_destination_id,
                    emballageId: (int) $m->emballage_id,
                    lotId: $m->lot_id ? (int) $m->lot_id : null,
                    quantite: $qty,
                    sens: 'ENTREE',
                    date: $date,
                    userId: $userId
                );
                break;

            case 'PTE':
            case 'SPL':
                $this->createStockLine(
                    entrepotId: (int) $m->entrepot_source_id,
                    emballageId: (int) $m->emballage_id,
                    lotId: $m->lot_id ? (int) $m->lot_id : null,
                    quantite: $qty,
                    sens: 'SORTIE',
                    date: $date,
                    userId: $userId
                );
                break;

            case 'CDD':
                $this->createStockLine(
                    entrepotId: (int) $m->entrepot_source_id,
                    emballageId: (int) $m->emballage_id,
                    lotId: $m->lot_id ? (int) $m->lot_id : null,
                    quantite: $qty,
                    sens: 'SORTIE',
                    date: $date,
                    userId: $userId
                );

                $this->createStockLine(
                    entrepotId: (int) $m->entrepot_destination_id,
                    emballageId: (int) $m->emballage_id,
                    lotId: $m->lot_id ? (int) $m->lot_id : null,
                    quantite: $qty,
                    sens: 'ENTREE',
                    date: $date,
                    userId: $userId
                );
                break;

            default:
                throw new InvalidArgumentException("Type de mouvement non supporté : {$m->type_mouvement}");
        }
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
        if (!in_array($sens, self::SENS, true)) {
            throw new InvalidArgumentException('sens invalide.');
        }

        return Stock::create([
            'entrepot_id' => $entrepotId,
            'emballage_id' => $emballageId,
            'lot_id' => $lotId,
            'date_stock' => $date,
            'quantite' => $quantite,
            'sens' => $sens,
            'user_id' => $userId,
        ]);
    }

    private function generateCodeMouvement(): string
    {
        return 'MVT-' . now()->format('Ymd-His') . '-' . strtoupper(substr(uniqid(), -4));
    }
}