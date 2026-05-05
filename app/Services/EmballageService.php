<?php

namespace App\Services;

use App\Models\Emballage;

class EmballageService
{
    public function create(array $data): Emballage
    {
        // Validation des champs obligatoires
        if (empty($data['name'])) {
            throw new \InvalidArgumentException("Le champ 'name' est obligatoire.");
        }
        if (empty($data['code'])) {
            throw new \InvalidArgumentException("Le champ 'code' est obligatoire.");
        }
        if (empty($data['type'])) {
            throw new \InvalidArgumentException("Le champ 'type' est obligatoire.");
        }

        $data['type'] = strtoupper($data['type']);

        // Normalisation : null si vide
        $data['capacity_value'] = $data['capacity_value'] ?? null;
        $data['capacity_unit']  = !empty($data['capacity_unit']) ? $data['capacity_unit'] : null;

        if (!empty($data['capacity_value']) && empty($data['capacity_unit'])) {
            throw new \InvalidArgumentException("capacity_unit est requis quand capacity_value est fourni.");
        }

        // Valeur par défaut du status
        $data['status'] = $data['status'] ?? 'ACTIVE';

        return Emballage::create($data);
    }

    public function update(Emballage $emballage, array $data): Emballage
    {
        if (isset($data['type'])) {
            $data['type'] = strtoupper($data['type']);
        }

        $capacityValue = array_key_exists('capacity_value', $data) ? $data['capacity_value'] : $emballage->capacity_value;
        $capacityUnit  = array_key_exists('capacity_unit', $data)  ? $data['capacity_unit']  : $emballage->capacity_unit;

        if (!empty($capacityValue) && empty($capacityUnit)) {
            throw new \InvalidArgumentException("capacity_unit est requis quand capacity_value est fourni.");
        }

        $emballage->update($data);
        return $emballage->refresh();
    }

    public function softDelete(Emballage $emballage): Emballage
    {
        $emballage->delete();
        return $emballage;
    }

    public function restore(int $id): Emballage
    {
        $emballage = Emballage::withTrashed()->findOrFail($id);
        $emballage->restore();
        return $emballage;
    }

    public function forceDelete(int $id): bool
    {
        $emballage = Emballage::withTrashed()->findOrFail($id);
        return (bool) $emballage->forceDelete();
    }
}