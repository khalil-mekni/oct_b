<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends Model
{
    protected $table = 'stocks';

    protected $fillable = [
        'entrepot_id',
        'emballage_id',
        'lot_id',
        'date_stock',
        //'quantite_init',
        'quantite',
        'sens',
       // 'quantite_finale',
        'user_id',
    ];

    protected $casts = [
        'date_stock' => 'datetime',      
        //'quantite_init' => 'decimal:2',
        'quantite' => 'decimal:2',
        //'quantite_finale' => 'decimal:2',
    ];

    public function entrepot(): BelongsTo
    {
        return $this->belongsTo(Entrepot::class, 'entrepot_id');
    }

    public function emballage(): BelongsTo
    {
        return $this->belongsTo(Emballage::class, 'emballage_id');
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class, 'lot_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}