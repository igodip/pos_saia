<?php

namespace App\Enums;

enum StockMovementType: string
{
    case INITIAL_LOAD = 'initial_load';
    case PURCHASE_LOAD = 'purchase_load';
    case MANUAL_OUT = 'manual_out';
    case ADJUSTMENT_IN = 'adjustment_in';
    case ADJUSTMENT_OUT = 'adjustment_out';
    case SUPPLIER_RETURN = 'supplier_return';

    public function direction(): StockMovementDirection
    {
        return match ($this) {
            self::INITIAL_LOAD, self::PURCHASE_LOAD, self::ADJUSTMENT_IN => StockMovementDirection::IN,
            self::MANUAL_OUT, self::ADJUSTMENT_OUT, self::SUPPLIER_RETURN => StockMovementDirection::OUT,
        };
    }
}
