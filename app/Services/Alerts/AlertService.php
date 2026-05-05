<?php

namespace App\Services\Alerts;

use App\Enums\AlertStatus;
use App\Models\Alert;
use App\Models\AlertUserStatus;
use App\Models\User;
use App\Services\Mercure\MercurePublisherService;
use Illuminate\Support\Facades\Auth;

class AlertService
{
    public function __construct(
        protected MercurePublisherService $mercurePublisherService
    ) {
    }

    public function createOrUpdate(array $data): Alert
    {
        $alert = Alert::where('type', $data['type'])
            ->where('entity_type', $data['entity_type'] ?? null)
            ->where('entity_id', $data['entity_id'] ?? null)
            ->where('is_active', true)
            ->first();

        if ($alert) {
            $alert->update([
                'title' => $data['title'],
                'message' => $data['message'],
                'severity' => $data['severity'],
                'action_url' => $data['action_url'] ?? null,
                'metadata' => $data['metadata'] ?? null,
                'status' => $alert->status === AlertStatus::ARCHIVED
                    ? AlertStatus::UNREAD
                    : $alert->status,
            ]);

            $freshAlert = $alert->fresh();

            $this->attachAlertToUsers($freshAlert);

            $this->mercurePublisherService->publish('alert.updated', $freshAlert);

            return $freshAlert;
        }

        $newAlert = Alert::create([
            'type' => $data['type'],
            'title' => $data['title'],
            'message' => $data['message'],
            'severity' => $data['severity'],
            'status' => AlertStatus::UNREAD,
            'entity_type' => $data['entity_type'] ?? null,
            'entity_id' => $data['entity_id'] ?? null,
            'action_url' => $data['action_url'] ?? null,
            'metadata' => $data['metadata'] ?? null,
            'is_active' => true,
        ]);

        $freshAlert = $newAlert->fresh();

        $this->attachAlertToUsers($freshAlert);

        $this->mercurePublisherService->publish('alert.created', $freshAlert);

        return $freshAlert;
    }

    private function attachAlertToUsers(Alert $alert): void
    {
        $roles = match ($alert->entity_type) {
            'contrat' => ['ADMIN', 'RESPONSABLE_APPROVISIONNEMENT'],
            'bon_livraison' => ['ADMIN', 'RESPONSABLE_APPROVISIONNEMENT'],
            'commande' => ['ADMIN', 'RESPONSABLE_APPROVISIONNEMENT'],
            'fournisseur' => ['ADMIN', 'RESPONSABLE_APPROVISIONNEMENT'],

            'entrepot' => ['ADMIN', 'RESPONSABLE_STOCKAGE'],
            'stock' => ['ADMIN', 'RESPONSABLE_STOCKAGE'],
            'mouvement_stock' => ['ADMIN', 'RESPONSABLE_STOCKAGE'],

            'facture' => ['ADMIN', 'RESPONSABLE_FINANCE'],

            default => ['ADMIN'],
        };

        $users = User::whereIn('role', $roles)
            ->where('is_active', true)
            ->get();

        foreach ($users as $user) {
            AlertUserStatus::firstOrCreate(
                [
                    'alert_id' => $alert->id,
                    'user_id' => $user->id,
                ],
                [
                    'status' => AlertStatus::UNREAD,
                ]
            );
        }
    }

    public function resolve(string $type, ?string $entityType, $entityId): void
    {
        Alert::where('type', $type)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
            ]);
    }

    public function markAsRead(int $id): ?Alert
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return null;
        }

        $alert = Alert::find($id);

        if (!$alert) {
            return null;
        }

        AlertUserStatus::updateOrCreate(
            [
                'alert_id' => $alert->id,
                'user_id' => $user->id,
            ],
            [
                'status' => AlertStatus::READ,
                'read_at' => now(),
            ]
        );

        return $alert->fresh();
    }

    public function markAllAsRead(): int
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return 0;
        }

        return AlertUserStatus::where('user_id', $user->id)
            ->where('status', AlertStatus::UNREAD)
            ->update([
                'status' => AlertStatus::READ,
                'read_at' => now(),
            ]);
    }

    public function archive(int $id): ?Alert
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return null;
        }

        $alert = Alert::find($id);

        if (!$alert) {
            return null;
        }

        AlertUserStatus::updateOrCreate(
            [
                'alert_id' => $alert->id,
                'user_id' => $user->id,
            ],
            [
                'status' => AlertStatus::ARCHIVED,
                'archived_at' => now(),
            ]
        );

        return $alert->fresh();
    }


    public function attachExistingAlertsToUser(User $user): int
{
    $alerts = Alert::where('is_active', true)->get();

    $created = 0;

    foreach ($alerts as $alert) {
        $status = AlertUserStatus::firstOrCreate(
            [
                'alert_id' => $alert->id,
                'user_id' => $user->id,
            ],
            [
                'status' => AlertStatus::UNREAD,
            ]
        );

        if ($status->wasRecentlyCreated) {
            $created++;
        }
    }

    return $created;
}
}