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
        'matricule_fiscale' => $data['matricule_fiscale'],
        'registre_entreprise' => $data['registre_entreprise'] ?? null,
        'logo' => $data['logo'] ?? null,
        'telephone' => $data['telephone'] ?? null,
        'email' => $data['email'] ?? null,
        'adresse' => $data['adresse'] ?? null,
        'representant_nom' => $data['representant_nom'] ?? null,
        'representant_role' => $data['representant_role'] ?? null,
        'statut' => $data['statut'] ?? 'ACTIF',
        'latitude' => $data['latitude'] ?? null,
        'longitude' => $data['longitude'] ?? null,
        'adresse_geocodee' => $data['adresse_geocodee'] ?? null,
        'geocoded_at' => $data['geocoded_at'] ?? null,
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
        'raison_sociale' => ['required', 'string', 'max:255'],
        'matricule_fiscale' => ['required','string','max:255','unique:fournisseurs,matricule_fiscale'],
        // AJOUT DE 'nullable' PARTOUT OU CE N'EST PAS REQUIS
        'registre_entreprise' => ['nullable','string','max:255'],
        'representant_nom' => ['nullable','string','max:255'],
        'representant_role' => ['nullable','string','max:255'],
        'logo' => ['nullable','string'],
        'telephone' => ['nullable','string','max:30'],
        'email' => ['nullable','email','max:255'],
        'adresse' => ['nullable','string','max:255'],
        'adresse_geocodee' => ['nullable','string'],
        'geocoded_at' => ['nullable','date'],
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
        // IDEM ICI : nullable est indispensable pour éviter l'erreur "must be a string"
        'registre_entreprise' => ['nullable','sometimes','string','max:255'],
        'representant_nom' => ['nullable','sometimes','string','max:255'],
        'representant_role' => ['nullable','sometimes','string','max:255'],
        'logo' => ['nullable','sometimes','string'],
        'telephone' => ['nullable','sometimes','string','max:30'],
        'email' => ['nullable','sometimes','email','max:255'],
        'adresse' => ['nullable','sometimes','string','max:255'],
        'adresse_geocodee' => ['nullable','sometimes','string'],
        'latitude' => ['nullable','sometimes','numeric'],
        'longitude' => ['nullable','sometimes','numeric'],
        'statut' => ['sometimes','in:ACTIF,INACTIF'],
    ]);

    if ($validator->fails()) {
        throw new ValidationException($validator);
    }
}
}