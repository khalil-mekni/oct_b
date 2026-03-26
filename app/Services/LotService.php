<?php

namespace App\Services;

use App\Models\Lot;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LotService
{
    public function __construct(
        private StockService $stockService,
        private EntrepotService $entrepotService
    ) {}

    public function createLotAndApply(array $payload): Lot
    {
        return DB::transaction(function () use ($payload) {
            $this->validatePayload($payload);

            $sens = $payload['sens'] ?? 'E';

            $lot = Lot::create([
                'code_lot'     => $payload['code_lot'] ?? $this->generateCodeLot(),
                'emballage_id' => (int) $payload['emballage_id'],
                'quantite'     => (float) $payload['quantite'],
                'user_id'      => $payload['user_id'] ?? null,
                'date_mvt'     => $payload['date_mvt'],
                'commentaire'  => $payload['commentaire'] ?? null,
            ]);

            $this->stockService->createHistoryLine(
                entrepotId: (int) $payload['entrepot_id'],
                emballageId: (int) $lot->emballage_id,
                lotId: (int) $lot->id,
                dateStock: $lot->date_mvt,
                quantite: (float) $lot->quantite,
                sens: $sens,
                userId: $lot->user_id
            );

            if ($sens === 'E') {
                $this->entrepotService->addStock((int) $payload['entrepot_id'], (float) $lot->quantite);
            } else {
                $this->entrepotService->removeStock((int) $payload['entrepot_id'], (float) $lot->quantite);
            }

            return $lot->refresh();
        });
    }

    public function findLot(int $id): Lot
    {
        $lot = Lot::with(['emballage', 'user', 'stocks'])->find($id);

        if (!$lot) {
            throw ValidationException::withMessages([
                'id' => "Lot #{$id} introuvable."
            ]);
        }

        return $lot;
    }

    public function listLots(int $perPage = 10)
    {
        return Lot::with(['emballage', 'user'])
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function updateLot(int $id, array $input): Lot
    {
        return DB::transaction(function () use ($id, $input) {
            $lot = $this->findLot($id);

            $lot->update([
                'code_lot'     => $input['code_lot'] ?? $lot->code_lot,
                'emballage_id' => $input['emballage_id'] ?? $lot->emballage_id,
                'quantite'     => $input['quantite'] ?? $lot->quantite,
                'user_id'      => $input['user_id'] ?? $lot->user_id,
                'date_mvt'     => $input['date_mvt'] ?? $lot->date_mvt,
                'commentaire'  => $input['commentaire'] ?? $lot->commentaire,
            ]);

            return $lot->refresh();
        });
    }

    public function deleteLot(int $id): Lot
    {
        return DB::transaction(function () use ($id) {
            $lot = $this->findLot($id);

            $this->stockService->deleteStocksByLot($lot->id);
            $lot->delete();

            return $lot;
        });
    }

    private function generateCodeLot(): string
    {
        $lastLot = Lot::orderByDesc('id')->first();

        if (!$lastLot || !preg_match('/^L(\d+)$/', $lastLot->code_lot, $matches)) {
            return 'L001';
        }

        $next = ((int) $matches[1]) + 1;

        return 'L' . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }

    private function validatePayload(array $payload): void
    {
        if (empty($payload['emballage_id'])) {
            throw ValidationException::withMessages([
                'emballage_id' => 'emballage_id requis.'
            ]);
        }

        if (empty($payload['entrepot_id'])) {
            throw ValidationException::withMessages([
                'entrepot_id' => 'entrepot_id requis.'
            ]);
        }

        if (!isset($payload['quantite']) || (float) $payload['quantite'] <= 0) {
            throw ValidationException::withMessages([
                'quantite' => 'La quantité doit être supérieure à 0.'
            ]);
        }

        if (empty($payload['date_mvt'])) {
            throw ValidationException::withMessages([
                'date_mvt' => 'date_mvt requis.'
            ]);
        }

        if (!empty($payload['sens']) && !in_array($payload['sens'], ['E', 'S'], true)) {
            throw ValidationException::withMessages([
                'sens' => 'sens doit être E ou S.'
            ]);
        }
    }
}