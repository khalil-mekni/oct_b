<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlertUserStatus extends Model
{
    protected $fillable = [
        'alert_id',
        'user_id',
        'status',
        'read_at',
        'archived_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    public function alert()
    {
        return $this->belongsTo(Alert::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}