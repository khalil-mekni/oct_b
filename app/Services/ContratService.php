<?php

namespace App\Services;

use App\Models\Contrat;
use App\Services\Alerts\AlertScanTriggerService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class ContratService
{
    public function __construct(
        //private AlertScanTriggerService $alertScanTrigger
    ) {
    }

    public function list()
    {
        
        return Contrat::query()
            ->with(['fournisseur', 'emballage'])
            ->orderByDesc('id')
            ->get();
    }

    public function find(int $id): Contrat
    {
        return Contrat::query()
            ->with(['fournisseur', 'emballage'])
            ->findOrFail($id);
    }

    public function create(array $data): Contrat
    {
        $this->validateCreate($data);

       $contrat = Contrat::create([
        'numero_contrat' => $data['numero_contrat'],
        'objet' => $data['objet'] ?? null,
        'date_signature' => $data['date_signature'] ?? null,
        'date_debut' => $data['date_debut'],
        'date_fin' => $data['date_fin'],
        'quantite_contractuelle' => $data['quantite_contractuelle'],
        'taux_depassement_autorise' => $data['taux_depassement_autorise'] ?? 0.20,
        'quantite_realisee' => $data['quantite_realisee'] ?? 0.00,
        'montant_ht' => $data['montant_ht'] ?? null,
        'montant_tva' => $data['montant_tva'] ?? 0.00,
        'taux_cautionnement' => $data['taux_cautionnement'] ?? 3.00,
        'taux_penalite_retard' => $data['taux_penalite_retard'] ?? 0.0020,
        'plafond_penalite' => $data['plafond_penalite'] ?? 5.00,
        'prix_unitaire' => $data['prix_unitaire'] ?? null,
        'statut' => $data['statut'] ?? 'ACTIF',
        'fournisseur_id' => (int) $data['fournisseur_id'],
        'emballage_id' => (int) $data['emballage_id'],
    ]);


        //$this->alertScanTrigger->dispatch();

        return $contrat->refresh();
    }

    public function update(int $id, array $data): Contrat
    {
        $contrat = Contrat::query()->findOrFail($id);

        $this->validateUpdate($id, $data);
       

        $contrat->update($data);

        //$this->alertScanTrigger->dispatch();

        return $contrat->load(['fournisseur', 'emballage']);
    }

    public function delete(int $id): bool
    {
        $contrat = Contrat::query()->findOrFail($id);

        $deleted = (bool) $contrat->delete();

        //$this->alertScanTrigger->dispatch();

        return $deleted;
    }

    private function validateCreate(array $data): void
    {
        $validator = Validator::make($data, [
            'numero_contrat' => ['required', 'string', 'max:255', 'unique:contrats,numero_contrat'],
            'objet' => ['nullable', 'string'],
        'date_signature' => ['nullable', 'date'],
        'date_debut' => ['required', 'date'],
        'date_fin' => ['required', 'date', 'after_or_equal:date_debut'],
        'quantite_contractuelle' => ['required', 'numeric', 'min:0'],
        'taux_depassement_autorise' => ['nullable', 'numeric', 'min:0'],
        'quantite_realisee' => ['nullable', 'numeric', 'min:0'],
        'montant_ht' => ['nullable', 'numeric', 'min:0'],
        'montant_tva' => ['nullable', 'numeric', 'min:0'],
        'taux_cautionnement' => ['nullable', 'numeric', 'min:0'],
        'taux_penalite_retard' => ['nullable', 'numeric', 'min:0'],
        'plafond_penalite' => ['nullable', 'numeric', 'min:0'],
        'prix_unitaire' => ['nullable', 'numeric', 'min:0'],
        'statut' => ['nullable', 'in:ACTIF,EXPIRE,SUSPENDU'],
        'fournisseur_id' => ['required', 'integer', 'exists:fournisseurs,id'],
        'emballage_id' => ['required', 'integer', 'exists:emballages,id'],
    ]);
        

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
    private function validateUpdate(int $id, array $data): void
{
    $validator = Validator::make($data, [
        'numero_contrat' => ['sometimes', 'string', 'max:255', "unique:contrats,numero_contrat,{$id}"],
        'objet' => ['sometimes', 'nullable', 'string'],
        'date_signature' => ['sometimes', 'nullable', 'date'],
        'date_debut' => ['sometimes', 'date'],
        'date_fin' => ['sometimes', 'date'],
        'quantite_contractuelle' => ['sometimes', 'numeric', 'min:0'],
        'taux_depassement_autorise' => ['sometimes', 'numeric', 'min:0'],
        'quantite_realisee' => ['sometimes', 'numeric', 'min:0'],
        'montant_ht' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        'montant_tva' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        'taux_cautionnement' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        'taux_penalite_retard' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        'plafond_penalite' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        'prix_unitaire' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        'statut' => ['sometimes', 'in:ACTIF,EXPIRE,SUSPENDU'],
        'fournisseur_id' => ['sometimes', 'integer', 'exists:fournisseurs,id'],
        'emballage_id' => ['sometimes', 'integer', 'exists:emballages,id'],
    ]);

    if ($validator->fails()) {
        throw new ValidationException($validator);
    }

    if (isset($data['date_debut'], $data['date_fin']) && $data['date_fin'] < $data['date_debut']) {
        throw ValidationException::withMessages([
            'date_fin' => ['date_fin doit être >= date_debut.']
        ]);
    }
}
public function refreshStatuts()
{
    Contrat::query()
        ->where('statut', '!=', 'EXPIRE')
        ->whereDate('date_fin', '<=', Carbon::today())
        ->update(['statut' => 'EXPIRE']);

    return Contrat::query()
        ->with(['fournisseur', 'emballage'])
        ->orderByDesc('id')
        ->get();
}

   
}