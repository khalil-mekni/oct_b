<?php

namespace App\GraphQL\Queries\Dashboard;

use App\Models\Alert;
use App\Models\Contrat;
use Carbon\Carbon;

final class ContractsWidget
{
    public function __invoke($_, array $args): array
    {
        $now = Carbon::now();
        $thirtyDaysLater = $now->copy()->addDays(30);

        $totalContracts = Contrat::query()->count();

        $activeContracts = Contrat::query()
            ->where(function ($query) use ($now) {
                $query->whereNull('date_fin')
                    ->orWhere('date_fin', '>=', $now->toDateString());
            })
            ->count();

        $expiringSoon = Contrat::query()
            ->whereNotNull('date_fin')
            ->whereBetween('date_fin', [
                $now->toDateString(),
                $thirtyDaysLater->toDateString(),
            ])
            ->count();

        $contractAlerts = Alert::query()
            ->where('is_active', true)
            ->whereIn('type', [
                'CONTRACT_EXPIRED',
                'CONTRACT_CONSUMPTION_HIGH',
                'CONTRACT_QUANTITY_EXCEEDED',
            ])
            ->count();

        $recentContracts = Contrat::query()
            ->with(['fournisseur', 'emballage'])
            ->latest('updated_at')
            ->limit(6)
            ->get()
            ->map(function ($contract) use ($now) {
                $status = strtolower($contract->statut ?? 'inconnu');

                if (!empty($contract->date_fin)) {
                    $endDate = Carbon::parse($contract->date_fin);

                    if ($endDate->lt($now->copy()->startOfDay())) {
                        $status = 'expired';
                    } elseif ($endDate->lte($now->copy()->addDays(30)->endOfDay())) {
                        $status = 'expiring_soon';
                    }
                }

                return [
                    'id' => $contract->id,
                    'reference' => $contract->numero_contrat ?? ('CTR-' . $contract->id),
                    'title' => $contract->emballage?->name ?? 'Contrat',
                    'partnerName' => $contract->fournisseur?->raison_sociale,
                    'endDate' => !empty($contract->date_fin)
                        ? Carbon::parse($contract->date_fin)->toDateString()
                        : null,
                    'status' => $status,
                ];
            })
            ->values()
            ->all();

        return [
            'totalContracts' => $totalContracts,
            'activeContracts' => $activeContracts,
            'expiringSoon' => $expiringSoon,
            'contractAlerts' => $contractAlerts,
            'recentContracts' => $recentContracts,
        ];
    }
}