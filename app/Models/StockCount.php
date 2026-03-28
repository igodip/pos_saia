<?php

namespace App\Models;

use App\Models\Concerns\HasAuditLogs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockCount extends Model
{
    use HasAuditLogs;

    protected $fillable = [
        'warehouse_id',
        'product_variant_id',
        'counted_qty',
        'system_qty',
        'difference_qty',
        'counted_at',
        'counted_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'counted_qty' => 'decimal:3',
            'system_qty' => 'decimal:3',
            'difference_qty' => 'decimal:3',
            'counted_at' => 'datetime',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
