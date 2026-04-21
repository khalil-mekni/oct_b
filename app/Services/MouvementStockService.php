<?php

namespace App\Services;

use App\Models\Lot;
use App\Models\MouvementStock;
use App\Services\Alerts\AlertScanTriggerService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

class MouvementStockService
{
    public function __construct(
        private StockService $stockService,
        private EntrepotService $entrepotService,
        private EntrepotLotService $entrepotLotService,
        private AlertScanTriggerService $alertScanTrigger
    ) {
    }

    public function createDraft(array $data): MouvementStock
    {
        $this->validateDraft($data);

        return MouvementStock::create([
            'code_mouvement' => $data['code_mouvement'] ?? $this->generateCodeMouvement(),
            'type_mouvement' => $data['type_mouvement'],
            'emballage_id' => (int) $data['emballage_id'],
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
            return $m->refresh();
        }

        $impactedEntrepotIds = [];

        $result = DB::transaction(function () use ($m, &$impactedEntrepotIds) {
            $m = MouvementStock::query()
                ->lockForUpdate()
                ->findOrFail($m->id);

            $this->validateBeforeApply($m);
            $this->applyToStocks($m);

            $m->update([
                'statut' => 'VALIDE',
                'date_mouvement' => $m->date_mouvement ?? now(),
                'user_id' => $m->user_id ?? Auth::id(),
            ]);

            $impactedEntrepotIds = $this->extractImpactedEntrepotIds($m);

            foreach ($impactedEntrepotIds as $entrepotId) {
                $this->entrepotService->syncStockFromLots($entrepotId, false);
            }

            return $m->refresh();
        });

        foreach ($impactedEntrepotIds as $entrepotId) {
            Log::info('MouvementStockService::dispatchWarehouseCapacityCheck', [
                'mouvement_id' => $result->id,
                'entrepot_id' => $entrepotId,
            ]);

            $this->alertScanTrigger->dispatchWarehouseCapacityCheck($entrepotId);
        }

        return $result;
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
        $dateMouvement = $m->date_mouvement ?? now();

        if ($qty <= 0) {
            throw new InvalidArgumentException("La quantité doit être supérieure à 0.");
        }

        switch ($m->type_mouvement) {
            case 'ENT':
                $this->applyEntree($m, $qty, $dateMouvement);
                break;

            case 'PTE':
            case 'PRD':
                $this->applySortieOuProduction($m, $qty, $dateMouvement);
                break;

            case 'CDD':
                $this->applyTransfert($m, $qty, $dateMouvement);
                break;

            case 'SPL':
                $this->applySplit($m, $qty, $dateMouvement);
                break;

            default:
                throw new InvalidArgumentException("type_mouvement invalide.");
        }
    }

    private function applyEntree(MouvementStock $m, float $qty, string|\DateTimeInterface $dateMouvement): void
    {
        if (!$m->entrepot_destination_id) {
            throw new InvalidArgumentException("ENT : destination requise.");
        }

        if ($m->lot_id) {
            throw new InvalidArgumentException("ENT : un mouvement d'entrée ne doit pas réutiliser un lot existant.");
        }

        $lot = Lot::create([
            'code_lot' => $this->generateCodeLot(),
            'emballage_id' => (int) $m->emballage_id,
            'quantite' => $qty,
            'date_mvt' => $dateMouvement,
            'user_id' => $m->user_id,
        ]);

        $m->update([
            'lot_id' => $lot->id,
        ]);

        $this->entrepotLotService->addToEntrepot(
            entrepotId: (int) $m->entrepot_destination_id,
            lotId: (int) $lot->id,
            emballageId: (int) $m->emballage_id,
            quantite: $qty
        );

        $this->stockService->createHistoryLine(
            entrepotId: (int) $m->entrepot_destination_id,
            emballageId: (int) $m->emballage_id,
            lotId: (int) $lot->id,
            dateStock: $dateMouvement,
            quantite: $qty,
            sens: 'E',
            userId: $m->user_id
        );
    }

    private function applySortieOuProduction(MouvementStock $m, float $qty, string|\DateTimeInterface $dateMouvement): void
    {
        if (!$m->entrepot_source_id) {
            throw new InvalidArgumentException("Source requise.");
        }

        if (!$m->lot_id) {
            throw new InvalidArgumentException("lot_id requis.");
        }

        $lot = $this->getLockedLotForMovement(
            lotId: (int) $m->lot_id,
            emballageId: (int) $m->emballage_id
        );

        $this->entrepotLotService->validateAvailableQuantity(
            entrepotId: (int) $m->entrepot_source_id,
            lotId: (int) $lot->id,
            emballageId: (int) $m->emballage_id,
            quantiteDemandee: $qty
        );

        $this->entrepotLotService->removeFromEntrepot(
            entrepotId: (int) $m->entrepot_source_id,
            lotId: (int) $lot->id,
            emballageId: (int) $m->emballage_id,
            quantite: $qty
        );

        if ((float) $lot->quantite < $qty) {
            throw new RuntimeException(
                "Quantité insuffisante dans le lot {$lot->code_lot}. Quantité globale du lot: {$lot->quantite}, demandé: {$qty}."
            );
        }

        $lot->update([
            'quantite' => (float) $lot->quantite - $qty,
        ]);

        $this->stockService->createHistoryLine(
            entrepotId: (int) $m->entrepot_source_id,
            emballageId: (int) $m->emballage_id,
            lotId: (int) $lot->id,
            dateStock: $dateMouvement,
            quantite: $qty,
            sens: 'S',
            userId: $m->user_id
        );
    }

    private function applyTransfert(MouvementStock $m, float $qty, string|\DateTimeInterface $dateMouvement): void
    {
        if (!$m->entrepot_source_id || !$m->entrepot_destination_id) {
            throw new InvalidArgumentException("CDD : source et destination requis.");
        }

        if (!$m->lot_id) {
            throw new InvalidArgumentException("lot_id requis.");
        }

        if ((int) $m->entrepot_source_id === (int) $m->entrepot_destination_id) {
            throw new InvalidArgumentException("CDD : la source et la destination doivent être différentes.");
        }

        $lot = $this->getLockedLotForMovement(
            lotId: (int) $m->lot_id,
            emballageId: (int) $m->emballage_id
        );

        $this->entrepotLotService->validateAvailableQuantity(
            entrepotId: (int) $m->entrepot_source_id,
            lotId: (int) $lot->id,
            emballageId: (int) $m->emballage_id,
            quantiteDemandee: $qty
        );

        $this->entrepotLotService->removeFromEntrepot(
            entrepotId: (int) $m->entrepot_source_id,
            lotId: (int) $lot->id,
            emballageId: (int) $m->emballage_id,
            quantite: $qty
        );

        $this->entrepotLotService->addToEntrepot(
            entrepotId: (int) $m->entrepot_destination_id,
            lotId: (int) $lot->id,
            emballageId: (int) $m->emballage_id,
            quantite: $qty
        );

        $this->stockService->createHistoryLine(
            entrepotId: (int) $m->entrepot_source_id,
            emballageId: (int) $m->emballage_id,
            lotId: (int) $lot->id,
            dateStock: $dateMouvement,
            quantite: $qty,
            sens: 'S',
            userId: $m->user_id
        );

        $this->stockService->createHistoryLine(
            entrepotId: (int) $m->entrepot_destination_id,
            emballageId: (int) $m->emballage_id,
            lotId: (int) $lot->id,
            dateStock: $dateMouvement,
            quantite: $qty,
            sens: 'E',
            userId: $m->user_id
        );
    }

    private function applySplit(MouvementStock $m, float $qty, string|\DateTimeInterface $dateMouvement): void
    {
        $target = $m->entrepot_destination_id ?? $m->entrepot_source_id;

        if (!$target) {
            throw new InvalidArgumentException("SPL : entrepôt requis.");
        }

        if (!$m->lot_id) {
            throw new InvalidArgumentException("lot_id requis.");
        }

        $lot = $this->getLockedLotForMovement(
            lotId: (int) $m->lot_id,
            emballageId: (int) $m->emballage_id
        );

        $this->entrepotLotService->addToEntrepot(
            entrepotId: (int) $target,
            lotId: (int) $lot->id,
            emballageId: (int) $m->emballage_id,
            quantite: $qty
        );

        $lot->update([
            'quantite' => (float) $lot->quantite + $qty,
        ]);

        $this->stockService->createHistoryLine(
            entrepotId: (int) $target,
            emballageId: (int) $m->emballage_id,
            lotId: (int) $lot->id,
            dateStock: $dateMouvement,
            quantite: $qty,
            sens: 'E',
            userId: $m->user_id
        );
    }

    private function extractImpactedEntrepotIds(MouvementStock $m): array
    {
        $ids = [];

        if ($m->entrepot_source_id) {
            $ids[] = (int) $m->entrepot_source_id;
        }

        if ($m->entrepot_destination_id) {
            $ids[] = (int) $m->entrepot_destination_id;
        }

        return array_values(array_unique($ids));
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
            throw new InvalidArgumentException("quantite doit être > 0.");
        }

        $type = $data['type_mouvement'];

        if ($type === 'ENT' && empty($data['entrepot_destination_id'])) {
            throw new InvalidArgumentException("ENT : entrepot_destination_id requis.");
        }

        if (in_array($type, ['PTE', 'PRD'], true)) {
            if (empty($data['entrepot_source_id'])) {
                throw new InvalidArgumentException("entrepot_source_id requis.");
            }

            if (empty($data['lot_id'])) {
                throw new InvalidArgumentException("lot_id requis.");
            }
        }

        if ($type === 'CDD') {
            if (empty($data['entrepot_source_id']) || empty($data['entrepot_destination_id'])) {
                throw new InvalidArgumentException("CDD : source et destination requis.");
            }

            if (empty($data['lot_id'])) {
                throw new InvalidArgumentException("lot_id requis.");
            }

            if ((int) $data['entrepot_source_id'] === (int) $data['entrepot_destination_id']) {
                throw new InvalidArgumentException("CDD : la source et la destination doivent être différentes.");
            }
        }
    }

    private function validateBeforeApply(MouvementStock $m): void
    {
        if ($m->type_mouvement === 'ENT') {
            if (!$m->entrepot_destination_id) {
                throw new InvalidArgumentException("ENT : destination requise.");
            }

            if ($m->lot_id) {
                throw new InvalidArgumentException("ENT : un mouvement d'entrée ne doit pas avoir de lot existant.");
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
                throw new InvalidArgumentException("CDD : source et destination requis.");
            }

            if (!$m->lot_id) {
                throw new InvalidArgumentException("lot_id requis.");
            }

            if ((int) $m->entrepot_source_id === (int) $m->entrepot_destination_id) {
                throw new InvalidArgumentException("CDD : la source et la destination doivent être différentes.");
            }

            return;
        }

        if ($m->type_mouvement === 'SPL') {
            $target = $m->entrepot_destination_id ?? $m->entrepot_source_id;

            if (!$target) {
                throw new InvalidArgumentException("SPL : entrepot requis.");
            }

            if (!$m->lot_id) {
                throw new InvalidArgumentException("lot_id requis.");
            }
        }
    }

    private function getLockedLotForMovement(int $lotId, int $emballageId): Lot
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

    private function generateCodeMouvement(): string
    {
        $last = MouvementStock::orderByDesc('id')->value('id') ?? 0;
        $next = $last + 1;

        return 'MVT-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function generateCodeLot(): string
    {
        $lastLotId = Lot::query()->max('id') ?? 0;
        $next = $lastLotId + 1;

        return 'LOT-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}