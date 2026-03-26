<?php

namespace App\Services;

use App\Models\Contrat;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ContratService
{
    public function list()
    {
        return Contrat::query()
            ->with(['fournisseur','emballage'])
            ->orderByDesc('id')
            ->get();
    }

    public function find(int $id): Contrat
    {
        return Contrat::query()
            ->with(['fournisseur','emballage'])
            ->findOrFail($id);
    }

    /*public function create(array $data): Contrat
    {
        $this->validateCreate($data);

        return Contrat::create([
            'numero_contrat' => $data['numero_contrat'],
            'date_debut' => $data['date_debut'],
            'date_fin' => $data['date_fin'],
            'quantite_contractuelle' => $data['quantite_contractuelle'],
            'taux_depassement_autorise' => $data['taux_depassement_autorise'] ?? 0.20,
            'quantite_realisee' => $data['quantite_realisee'] ?? 0.00,
            'statut' => $data['statut'] ?? 'ACTIF',
            'fournisseur_id' => (int) $data['fournisseur_id'],
        ]);
    }/* --- IGNORE --- */


   public function create(array $data): Contrat
    {
        $this->validateCreate($data);

       return Contrat::create([
    'numero_contrat' => $data['numero_contrat'],
    'date_debut' => $data['date_debut'],
    'date_fin' => $data['date_fin'],
    'quantite_contractuelle' => $data['quantite_contractuelle'],
    'taux_depassement_autorise' => $data['taux_depassement_autorise'] ?? 0.20,
    'quantite_realisee' => $data['quantite_realisee'] ?? 0.00,
    'prix' => $data['prix'],
    'statut' => $data['statut'] ?? 'ACTIF',
    'fournisseur_id' => (int) $data['fournisseur_id'],
    'emballage_id' => (int) $data['emballage_id'],
]);
    }

    public function update(int $id, array $data): Contrat
    {
        $contrat = Contrat::query()->findOrFail($id);

        $this->validateUpdate($id, $data);

        $contrat->update($data);

        return $contrat->load(['fournisseur','emballage']);    }

    public function delete(int $id): bool
    {
        $contrat = Contrat::query()->findOrFail($id);
        return (bool) $contrat->delete();
    }

    private function validateCreate(array $data): void
    {
        $validator = Validator::make($data, [
            'numero_contrat' => ['required','string','max:255','unique:contrats,numero_contrat'],
            'date_debut' => ['required','date'],
            'date_fin' => ['required','date','after_or_equal:date_debut'],
            'quantite_contractuelle' => ['required','numeric','min:0'],
            'taux_depassement_autorise' => ['nullable','numeric','min:0'],
            'quantite_realisee' => ['nullable','numeric','min:0'],
            'prix' => ['required', 'numeric', 'min:0'],
            'statut' => ['nullable','in:ACTIF,EXPIRE,SUSPENDU'],
            'fournisseur_id' => ['required','integer','exists:fournisseurs,id'],
            'emballage_id' => ['required', 'integer', 'exists:emballages,id'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    private function validateUpdate(int $id, array $data): void
    {
        $validator = Validator::make($data, [
            'numero_contrat' => ['sometimes','string','max:255',"unique:contrats,numero_contrat,{$id}"],
            'date_debut' => ['sometimes','date'],
            'date_fin' => ['sometimes','date'],
            'quantite_contractuelle' => ['sometimes','numeric','min:0'],
            'taux_depassement_autorise' => ['sometimes','numeric','min:0'],
            'quantite_realisee' => ['sometimes','numeric','min:0'],
            'prix' => ['sometimes', 'numeric', 'min:0'],
            'statut' => ['sometimes','in:ACTIF,EXPIRE,SUSPENDU'],
            'fournisseur_id' => ['sometimes','integer','exists:fournisseurs,id'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Contrôle cohérence dates si les deux sont présents
        if (isset($data['date_debut'], $data['date_fin']) && $data['date_fin'] < $data['date_debut']) {
            throw ValidationException::withMessages([
                'date_fin' => ['date_fin doit être >= date_debut.']
            ]);
        }
    }
}