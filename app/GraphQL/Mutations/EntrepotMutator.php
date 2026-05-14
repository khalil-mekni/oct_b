<?php

namespace App\GraphQL\Mutations;

use App\Models\Entrepot;

use RuntimeException;

class EntrepotMutator
{
    public function create($_, array $args): Entrepot
    {
        $input = $args['input'];

        $capaciteTotale = (float) $input['capacite_totale'];

        if ($capaciteTotale < 0) {
            throw new RuntimeException("La capacité totale doit être supérieure ou égale à 0.");
        }

        return Entrepot::create([
            'nom' => $input['nom'],
            'adresse' => $input['adresse'] ?? null,
            'capacite_totale' => $capaciteTotale,
            'stock_existant' => 0,
            'capacite_disponible' => $capaciteTotale,
            'statut' => $input['statut'] ?? 'ACTIVE',
        ])->refresh();
    }

    public function update($_, array $args): Entrepot
    {
        $input = $args['input'];

        $entrepot = Entrepot::findOrFail($input['id']);

        $data = [
            'nom' => $input['nom'] ?? $entrepot->nom,
            'adresse' => $input['adresse'] ?? $entrepot->adresse,
            'statut' => $input['statut'] ?? $entrepot->statut,
        ];

        if (isset($input['capacite_totale'])) {
            $nouvelleCapaciteTotale = (float) $input['capacite_totale'];
            $stockExistant = (float) $entrepot->stock_existant;

            if ($nouvelleCapaciteTotale < $stockExistant) {
                throw new RuntimeException(
                    "La capacité totale ne peut pas être inférieure au stock existant."
                );
            }

            $data['capacite_totale'] = $nouvelleCapaciteTotale;
            $data['capacite_disponible'] = $nouvelleCapaciteTotale - $stockExistant;
        }

        $entrepot->update($data);

        return $entrepot->refresh();

    }
}