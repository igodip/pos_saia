<?php

namespace App\Models;

use App\Models\Concerns\HasAuditLogs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasAuditLogs, SoftDeletes;

    protected $fillable = [
        'company_name',
        'vat_number',
        'tax_code',
        'address',
        'email',
        'phone',
        'payment_terms',
        'notes',
    ];

    public function purchaseInvoices(): HasMany
    {
        return $this->hasMany(PurchaseInvoice::class);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $inner) use ($term): void {
            $inner->where('company_name', 'like', "%{$term}%")
                ->orWhere('vat_number', 'like', "%{$term}%");
        });
    }
}
