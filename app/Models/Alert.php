<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Alert extends Model
{
    protected $fillable = [
        'type',
        'title',
        'message',
        'severity',
        'status',
        'entity_type',
        'entity_id',
        'action_url',
        'metadata',
        'is_active',
        'read_at',
        'archived_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
        'read_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    protected $appends = [
        'user_status',
        'user_read_at',
        'user_archived_at',
    ];

    public function userStatuses()
    {
        return $this->hasMany(AlertUserStatus::class);
    }

    public function getUserStatusAttribute()
    {
        $status = $this->currentUserStatus();

        return $status?->status;
    }

    public function getUserReadAtAttribute()
    {
        $status = $this->currentUserStatus();

        return $status?->read_at;
    }

    public function getUserArchivedAtAttribute()
    {
        $status = $this->currentUserStatus();

        return $status?->archived_at;
    }

    private function currentUserStatus(): ?AlertUserStatus
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return null;
        }

        if ($this->relationLoaded('userStatuses')) {
            return $this->userStatuses
                ->where('user_id', $user->id)
                ->first();
        }

        return $this->userStatuses()
            ->where('user_id', $user->id)
            ->first();
    }
}