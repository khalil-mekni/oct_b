<?php

namespace App\Services;

use App\Models\Entrepot;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class EntrepotService
{
    public function create(array $data): Entrepot
    {
        if (isset($data['statut'])) {
            $data['statut'] = strtoupper($data['statut']);
        }

        $this->validateCapacites($data);

        return Entrepot::create($data);
    }

    public function update(Entrepot $entrepot, array $data): Entrepot
    {
        if (isset($data['statut'])) {
            $data['statut'] = strtoupper($data['statut']);
        }

        $merged = array_merge($entrepot->toArray(), $data);
        $this->validateCapacites($merged);

        $entrepot->update($data);
        return $entrepot->refresh();
    }

    public function delete(Entrepot $entrepot): bool
    {
        return (bool) $entrepot->delete();
    }

    private function validateCapacites(array $data): void
    {
        $tot = Arr::get($data, 'capacite_totale');
        $dispo = Arr::get($data, 'capacite_disponible');

        if ($tot !== null && $dispo !== null && (float)$dispo > (float)$tot) {
            throw new InvalidArgumentException("capacite_disponible ne peut pas dépasser capacite_totale.");
        }
    }
}