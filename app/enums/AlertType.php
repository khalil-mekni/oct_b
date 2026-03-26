<?php

namespace App\Enums;

class AlertType
{
    public const LOW_STOCK = 'LOW_STOCK';
    public const LOT_EXPIRING = 'LOT_EXPIRING';
    public const CONTRAT_EXPIRING = 'CONTRAT_EXPIRING';
    public const SUPPLIER_DELAY = 'SUPPLIER_DELAY';
    public const INVENTORY_ANOMALY = 'INVENTORY_ANOMALY';
    public const WAREHOUSE_CAPACITY_HIGH = 'WAREHOUSE_CAPACITY_HIGH';
}