<?php

namespace App\Services;

use App\Models\Emballage;

class EmballageService
{
    public function create(array $data): Emballage
    {
        $data['type'] = strtoupper($data['type']);

        if (!empty($data['capacity_value']) && empty($data['capacity_unit'])) {
            throw new \InvalidArgumentException("capacity_unit is required when capacity_value is provided.");
        }

        return Emballage::create($data);
    }

    public function update(Emballage $emballage, array $data): Emballage
    {
        if (isset($data['type'])) $data['type'] = strtoupper($data['type']);

        $capacityValue = array_key_exists('capacity_value', $data) ? $data['capacity_value'] : $emballage->capacity_value;
        $capacityUnit  = array_key_exists('capacity_unit', $data) ? $data['capacity_unit'] : $emballage->capacity_unit;

        if (!empty($capacityValue) && empty($capacityUnit)) {
            throw new \InvalidArgumentException("capacity_unit is required when capacity_value is provided.");
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
