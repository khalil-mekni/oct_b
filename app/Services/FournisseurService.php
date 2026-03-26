<?php

namespace App\Services;

use App\Models\Fournisseur;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class FournisseurService
{
    public function list()
    {
        return Fournisseur::query()->orderByDesc('id')->get();
    }

    public function find(int $id): Fournisseur
    {
        return Fournisseur::query()->findOrFail($id);
    }

    public function create(array $data): Fournisseur
    {
        $this->validateCreate($data);

       return Fournisseur::create([
    'raison_sociale' => $data['raison_sociale'],
    'logo' => $data['logo'] ?? null,
    'matricule_fiscale' => $data['matricule_fiscale'],
    'telephone' => $data['telephone'] ?? null,
    'adresse' => $data['adresse'] ?? null,
    'statut' => $data['statut'] ?? 'ACTIF',
    'latitude' => $data['latitude'] ?? null,
    'longitude' => $data['longitude'] ?? null,
    'adresse_geocodee' => $data['adresse_geocodee'] ?? null,
]);
    }

    public function update(int $id, array $data): Fournisseur
    {
        $f = Fournisseur::query()->findOrFail($id);

        $this->validateUpdate($id, $data);

        $f->update($data);

        return $f->refresh();
    }

    public function delete(int $id): bool
    {
        $f = Fournisseur::query()->findOrFail($id);
        return (bool) $f->delete();
    }

    private function validateCreate(array $data): void
    {
        $validator = Validator::make($data, [
            'raison_sociale' => ['required','string','max:255'],
            
            'matricule_fiscale' => ['required','string','max:255','unique:fournisseurs,matricule_fiscale'],
            'telephone' => ['nullable','string','max:30'],
            'adresse' => ['nullable','string','max:255'],
            'statut' => ['nullable','in:ACTIF,INACTIF'],
            'latitude' => ['nullable','numeric','between:-90,90'],
'longitude' => ['nullable','numeric','between:-180,180'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    private function validateUpdate(int $id, array $data): void
    {
        $validator = Validator::make($data, [
            'raison_sociale' => ['sometimes','string','max:255'],
            'matricule_fiscale' => ['sometimes','string','max:255',"unique:fournisseurs,matricule_fiscale,{$id}"],
            'telephone' => ['nullable','string','max:30'],
            'adresse' => ['nullable','string','max:255'],
            'statut' => ['nullable','in:ACTIF,INACTIF'],
            'latitude' => ['nullable','numeric','between:-90,90'],
'longitude' => ['nullable','numeric','between:-180,180'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}