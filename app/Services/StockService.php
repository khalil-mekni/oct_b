<?php

namespace App\Services;

use App\Models\Stock;
use InvalidArgumentException;

class StockService
{
    public function __construct(private LotService $lotService) {}

    public function create(array $data): Stock
    {
        $this->validateSnapshot($data, isCreate: true);

        $init   = (float) ($data['quantite_init'] ?? 0);
        $entree = (float) ($data['quantite_entree'] ?? 0);
        $sortie = (float) ($data['quantite_sortie'] ?? 0);

        $finale = $this->computeFinale($init, $entree, $sortie);

        return Stock::create([
            'entrepot_id' => $data['entrepot_id'],
            'emballage_id' => $data['emballage_id'],
            'lot_id' => $data['lot_id'] ?? null,
            'date_stock' => $data['date_stock'],
            'quantite_init' => $init,
            'quantite_entree' => $entree,
            'quantite_sortie' => $sortie,
            'quantite_finale' => $finale,
            'user_id' => $data['user_id'] ?? null,
        ])->refresh();
    }

    public function createWithAutoLot(array $data): Stock
    {
        $this->validateSnapshot($data, isCreate: true);

        // ✅ Toujours créer un nouveau lot
        $lot = $this->lotService->createAutoLot();

        $init   = (float) ($data['quantite_init'] ?? 0);
        $entree = (float) ($data['quantite_entree'] ?? 0);
        $sortie = (float) ($data['quantite_sortie'] ?? 0);

        $finale = $this->computeFinale($init, $entree, $sortie);

        return Stock::create([
            'entrepot_id' => $data['entrepot_id'],
            'emballage_id' => $data['emballage_id'],
            'lot_id' => $lot->id,
            'date_stock' => $data['date_stock'],
            'quantite_init' => $init,
            'quantite_entree' => $entree,
            'quantite_sortie' => $sortie,
            'quantite_finale' => $finale,
            'user_id' => $data['user_id'] ?? null,
        ])->refresh();
    }

    public function update(Stock $stock, array $data): Stock
    {
        $this->validateSnapshot($data, isCreate: false);

        $init   = array_key_exists('quantite_init', $data) ? (float) $data['quantite_init'] : (float) $stock->quantite_init;
        $entree = array_key_exists('quantite_entree', $data) ? (float) $data['quantite_entree'] : (float) $stock->quantite_entree;
        $sortie = array_key_exists('quantite_sortie', $data) ? (float) $data['quantite_sortie'] : (float) $stock->quantite_sortie;

        $finale = $this->computeFinale($init, $entree, $sortie);

        $allowed = [];
        foreach (['date_stock','quantite_init','quantite_entree','quantite_sortie','user_id','lot_id'] as $f) {
            if (array_key_exists($f, $data)) {
                $allowed[$f] = $data[$f];
            }
        }

        $allowed['quantite_finale'] = $finale;

        $stock->update($allowed);
        return $stock->refresh();
    }

    public function delete(Stock $stock): bool
    {
        return (bool) $stock->delete();
    }

    private function computeFinale(float $init, float $entree, float $sortie): float
    {
        if ($init < 0 || $entree < 0 || $sortie < 0) {
            throw new InvalidArgumentException("Les quantités doivent être >= 0.");
        }

        $finale = $init + $entree - $sortie;

        if ($finale < 0) {
            throw new InvalidArgumentException("quantite_finale ne peut pas être négative (init + entree - sortie).");
        }

        return $finale;
    }

    private function validateSnapshot(array $data, bool $isCreate): void
    {
        if ($isCreate) {
            if (empty($data['entrepot_id']) || empty($data['emballage_id']) || empty($data['date_stock'])) {
                throw new InvalidArgumentException("entrepot_id, emballage_id et date_stock sont requis.");
            }
        }

        foreach (['quantite_init','quantite_entree','quantite_sortie'] as $f) {
            if (isset($data[$f]) && (float)$data[$f] < 0) {
                throw new InvalidArgumentException("$f doit être >= 0");
            }
        }
    }
}