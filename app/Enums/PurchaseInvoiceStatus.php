<?php

namespace App\Enums;

enum PurchaseInvoiceStatus: string
{
    case DRAFT = 'draft';
    case CONFIRMED = 'confirmed';
    case CANCELLED = 'cancelled';
}
