<?php

namespace App\Models;

use App\Models\Concerns\HasAuditLogs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use HasAuditLogs, SoftDeletes;

    protected $fillable = [
        'product_id',
        'sku',
        'barcode',
        'variant_name',
        'attributes_json',
        'default_cost',
        'default_price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'attributes_json' => 'array',
            'default_cost' => 'decimal:2',
            'default_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function stockCounts(): HasMany
    {
        return $this->hasMany(StockCount::class);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $inner) use ($term): void {
            $inner->where('sku', 'like', "%{$term}%")
                ->orWhere('barcode', 'like', "%{$term}%")
                ->orWhere('variant_name', 'like', "%{$term}%");
        });
    }
}
