<?php

namespace App\Console\Commands;

use App\Enums\AlertStatus;
use App\Models\Alert;
use App\Models\AlertUserStatus;
use App\Models\User;
use Illuminate\Console\Command;

class SyncAlertUserStatuses extends Command
{
    protected $signature = 'alerts:sync-user-statuses';

    protected $description = 'Create alert user statuses for existing alerts';

    public function handle(): int
    {
        $alerts = Alert::where('is_active', true)->get();
        $users = User::where('is_active', true)
            ->where('role', '!=', 'PENDING')
            ->get();

        $created = 0;

        $this->info('Active alerts: ' . $alerts->count());
        $this->info('Active users: ' . $users->count());

        foreach ($alerts as $alert) {
            foreach ($users as $user) {
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
        }

        $this->info('Created alert_user_statuses: ' . $created);

        return self::SUCCESS;
    }
}