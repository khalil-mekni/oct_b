<?php

namespace App\Services;

use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockService
{
    public function createHistoryLine(
        int $entrepotId,
        int $emballageId,
        ?int $lotId,
        string|\DateTimeInterface $dateStock,
        float $quantite,
        string $sens,
        ?int $userId = null
    ): Stock {
        if ($quantite <= 0) {
            throw new RuntimeException("La quantité doit être supérieure à 0.");
        }

        if (!in_array($sens, ['E', 'S'], true)) {
            throw new RuntimeException("Sens invalide. Valeurs autorisées : E ou S.");
        }

        $dateStock = Carbon::parse($dateStock);

        return DB::transaction(function () use (
            $entrepotId,
            $emballageId,
            $lotId,
            $dateStock,
            $quantite,
            $sens,
            $userId
        ) {
            $lastStock = Stock::query()
                ->where('entrepot_id', $entrepotId)
                ->where('emballage_id', $emballageId)
                ->when(
                    $lotId !== null,
                    fn ($query) => $query->where('lot_id', $lotId),
                    fn ($query) => $query->whereNull('lot_id')
                )
                ->where('date_stock', '<=', $dateStock)
                ->orderByDesc('date_stock')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            $quantiteInit = $lastStock ? (float) $lastStock->quantite_finale : 0.0;

            $quantiteFinale = match ($sens) {
                'E' => $quantiteInit + $quantite,
                'S' => $quantiteInit - $quantite,
                default => throw new RuntimeException("Sens invalide."),
            };

            if ($quantiteFinale < 0) {
                throw new RuntimeException("Stock insuffisant pour créer la ligne d'historique.");
            }

            return Stock::create([
                'entrepot_id' => $entrepotId,
                'emballage_id' => $emballageId,
                'lot_id' => $lotId,
                'date_stock' => $dateStock,
                'quantite_init' => $quantiteInit,
                'quantite' => $quantite,
                'sens' => $sens,
                'quantite_finale' => $quantiteFinale,
                'user_id' => $userId,
            ]);
        });
    }

   public function getTheoriqueAt(
    int $entrepotId,
    int $emballageId,
    ?int $lotId = null,
    $dateTime = null
): float {
    $query = DB::table('entrepot_lots')
        ->where('entrepot_id', $entrepotId)
        ->where('emballage_id', $emballageId);

    if ($lotId !== null) {
        $query->where('lot_id', $lotId);
    }

    return (float) $query->sum('quantite');
}
    public function getDisponibleAt(
        int $entrepotId,
        int $emballageId,
        ?int $lotId = null
    ): float {
        $finale = Stock::query()
            ->where('entrepot_id', $entrepotId)
            ->where('emballage_id', $emballageId)
            ->when(
                $lotId !== null,
                fn ($query) => $query->where('lot_id', $lotId),
                fn ($query) => $query->whereNull('lot_id')
            )
            ->orderByDesc('date_stock')
            ->orderByDesc('id')
            ->value('quantite_finale');

        return $finale !== null ? (float) $finale : 0.0;
    }

    public function deleteStocksByLot(int $lotId): void
    {
        Stock::query()
            ->where('lot_id', $lotId)
            ->delete();
    }

    public function history(
        ?int $entrepotId = null,
        ?int $emballageId = null,
        ?int $lotId = null,
        ?string $from = null,
        ?string $to = null
    ): Collection {
        return Stock::query()
            ->with(['entrepot', 'emballage', 'lot', 'user'])
            ->when($entrepotId !== null, fn ($query) => $query->where('entrepot_id', $entrepotId))
            ->when($emballageId !== null, fn ($query) => $query->where('emballage_id', $emballageId))
            ->when($lotId !== null, fn ($query) => $query->where('lot_id', $lotId))
            ->when($from !== null, fn ($query) => $query->where('date_stock', '>=', Carbon::parse($from)))
            ->when($to !== null, fn ($query) => $query->where('date_stock', '<=', Carbon::parse($to)))
            ->orderByDesc('date_stock')
            ->orderByDesc('id')
            ->get();
    }
}