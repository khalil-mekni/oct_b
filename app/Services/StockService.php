<?php

namespace App\Services;

use App\Models\Lot;
use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockService
{
    public function applyLotToStocks(Lot $lot, array $context = []): array
    {
        $entrepotId = $context['entrepot_id'] ?? null;
        $sens = $context['sens'] ?? 'entree';

        if (!$entrepotId) {
            throw new RuntimeException("entrepot_id est requis pour créer le mouvement de stock.");
        }

        if (!in_array($sens, ['entree', 'sortie'], true)) {
            throw new RuntimeException("sens invalide. Valeurs autorisées : entree, sortie.");
        }

        $ts = Carbon::parse($lot->date_mvt);

        $stock = $this->applySnapshot(
            $entrepotId,
            $lot->emballage_id,
            $ts,
            $lot->id,
            $lot->user_id,
            (float) $lot->quantite,
            $sens
        );

        return [$stock];
    }

    private function applySnapshot(
        int $entrepotId,
        int $emballageId,
        Carbon $at,
        int $lotId,
        ?int $userId,
        float $quantite,
        string $sens
    ): Stock {
        return Stock::create([
            'entrepot_id' => $entrepotId,
            'emballage_id' => $emballageId,
            'lot_id' => $lotId,
            'date_stock' => $at,
            'quantite' => $quantite,
            'sens' => $sens,
            'user_id' => $userId,
        ]);
    }

    /*public function getTheoriqueAt(int $entrepotId, int $emballageId, string $dateTime): float
    {
        $dt = Carbon::parse($dateTime);

        $stocks = Stock::where('entrepot_id', $entrepotId)
            ->where('emballage_id', $emballageId)
            ->where('date_stock', '<=', $dt)
            ->orderBy('date_stock', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $solde = 0;

        foreach ($stocks as $stock) {
            $quantite = (float) $stock->quantite;

            if ($stock->sens === 'entree') {
                $solde += $quantite;
            } elseif ($stock->sens === 'sortie') {
                $solde -= $quantite;
            }
        }

        return $solde;
    }*/

        public function getTheoriqueAt(int $entrepotId, int $emballageId, string $dateTime): float
{
    $dt = Carbon::parse($dateTime)->endOfDay();

    $stocks = Stock::where('entrepot_id', $entrepotId)
        ->where('emballage_id', $emballageId)
        ->where('date_stock', '<=', $dt)
        ->get();

    $entrees = $stocks
        ->where('sens', 'entree')
        ->sum('quantite');

    $sorties = $stocks
        ->where('sens', 'sortie')
        ->sum('quantite');

    return (float) $entrees - (float) $sorties;
}


    public function updateStockFromLotChange(Lot $oldLot, Lot $newLot, array $context = []): void
    {
        $this->deleteStocksByLot($oldLot);
        $this->applyLotToStocks($newLot, $context);
    }

    public function removeStocksFromLot(Lot $lot): void
    {
        $this->deleteStocksByLot($lot);
    }

    public function getImpactedScopes(Lot $lot, array $context = []): array
    {
        $scopes = Stock::where('lot_id', $lot->id)
            ->get(['entrepot_id', 'emballage_id'])
            ->map(fn ($stock) => [
                'entrepot_id' => $stock->entrepot_id,
                'emballage_id' => $stock->emballage_id,
            ])
            ->unique(fn ($s) => $s['entrepot_id'] . '-' . $s['emballage_id'])
            ->values()
            ->all();

        if (!empty($scopes)) {
            return $scopes;
        }

        if (!empty($context['entrepot_id'])) {
            return [[
                'entrepot_id' => $context['entrepot_id'],
                'emballage_id' => $lot->emballage_id,
            ]];
        }

        return [];
    }

    public function rebuildStockTimeline(int $entrepotId, int $emballageId, Carbon $fromDate): void
    {
        // Plus de recalcul nécessaire :
        // la table stock contient désormais uniquement des mouvements.
        return;
    }

    public function deleteStocksByLot(Lot $lot): void
    {
        Stock::where('lot_id', $lot->id)->delete();
    }


public function getStockSumPeriode(
    int $entrepotId,
    int $emballageId,
    string $periodeDebut,
    string $periodeFin
): float {
    $stocks = Stock::where('entrepot_id', $entrepotId)
        ->where('emballage_id', $emballageId)
        ->whereBetween('date_stock', [
            Carbon::parse($periodeDebut)->startOfDay(),
            Carbon::parse($periodeFin)->endOfDay(),
        ])
        ->get();

    $entrees = $stocks->where('sens', 'entree')->sum('quantite');
    $sorties = $stocks->where('sens', 'sortie')->sum('quantite');

    return (float) $entrees - (float) $sorties;
}


}