<?php
namespace App\GraphQL\Mutations;

use App\Services\Alerts\AlertService;

class AlertMutation
{
    public function __construct(private AlertService $alertService)
    {
    }

    public function markAsRead($_, array $args)
    {
        return $this->alertService->markAsRead((int) $args['id']);
    }

    public function markAllAsRead()
    {
        $this->alertService->markAllAsRead();
        return true;
    }

    public function archive($_, array $args)
    {
        return $this->alertService->archive((int) $args['id']);
    }
}