<?php

namespace App\Services\Alerts;

use App\Enums\AlertStatus;
use App\Models\Alert;
use App\Services\Mercure\MercurePublisherService;

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

        $this->mercurePublisherService->publish('alert.created', $freshAlert);

        return $freshAlert;
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
        $alert = Alert::find($id);

        if (!$alert) {
            return null;
        }

        $alert->update([
            'status' => AlertStatus::READ,
            'read_at' => now(),
        ]);

        return $alert->fresh();
    }

    public function markAllAsRead(): int
    {
        return Alert::where('status', AlertStatus::UNREAD)
            ->update([
                'status' => AlertStatus::READ,
                'read_at' => now(),
            ]);
    }

    public function archive(int $id): ?Alert
    {
        $alert = Alert::find($id);

        if (!$alert) {
            return null;
        }

        $alert->update([
            'status' => AlertStatus::ARCHIVED,
            'archived_at' => now(),
            'is_active' => false,
        ]);

        return $alert->fresh();
    }
}