<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'description',
        'item_type',
        'usage_record_id',
        'quantity',
        'unit_price',
        'subtotal',
        'discount',
        'total',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public $timestamps = false; // Solo created_at
    
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            $model->created_at = now();
        });
    }

    // Relaciones
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function usageRecord(): BelongsTo
    {
        return $this->belongsTo(UsageRecord::class);
    }

    // Accessors
    public function getIsSubscriptionItemAttribute(): bool
    {
        return $this->item_type === 'subscription';
    }

    public function getIsExtraChargeAttribute(): bool
    {
        return in_array($this->item_type, ['extra_hours', 'prints', 'event', 'other']);
    }
}