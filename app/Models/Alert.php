<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
