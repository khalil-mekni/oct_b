<?php

namespace App\Services\Mercure;

use App\Models\Alert;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MercurePublisherService
{
    public function publish(string $event, Alert $alert): void
    {
        $hubUrl = config('services.mercure.hub_url');
        $secret = config('services.mercure.jwt_secret');
        $topic = config('services.mercure.topic', 'alerts/general');

        if (empty($hubUrl) || empty($secret)) {
            Log::warning('Mercure non configuré : hub_url ou jwt_secret manquant.');
            return;
        }

        $jwt = JWT::encode([
            'mercure' => [
                'publish' => ['*'],
            ],
        ], $secret, 'HS256');

        $payload = [
            'event' => $event,
            'alert' => [
                'id' => $alert->id,
                'type' => $alert->type,
                'title' => $alert->title,
                'message' => $alert->message,
                'severity' => $alert->severity,
                'status' => $alert->status,
                'entity_type' => $alert->entity_type,
                'entity_id' => $alert->entity_id,
                'action_url' => $alert->action_url,
                'metadata' => $alert->metadata,
                'is_active' => (bool) $alert->is_active,
                'read_at' => $alert->read_at,
                'archived_at' => $alert->archived_at,
                'created_at' => optional($alert->created_at)?->toISOString(),
                'updated_at' => optional($alert->updated_at)?->toISOString(),
            ],
        ];

        try {
            $response = Http::asForm()
                ->withToken($jwt)
                ->timeout(5)
                ->post($hubUrl, [
                    'topic' => $topic,
                    'data' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                ]);

            if ($response->failed()) {
                Log::error('Erreur publication Mercure', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'event' => $event,
                    'alert_id' => $alert->id,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Exception lors de la publication Mercure', [
                'message' => $e->getMessage(),
                'event' => $event,
                'alert_id' => $alert->id,
            ]);
        }
    }
}