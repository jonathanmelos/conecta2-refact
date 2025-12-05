<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'client_id',
        'invoice_id',
        'payment_date',
        'amount',
        'payment_method',
        'transaction_reference',
        'bank_name',
        'account_number',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'datetime',
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    // Accessors
    public function getIsCashPaymentAttribute(): bool
    {
        return $this->payment_method === 'cash';
    }

    public function getIsTransferAttribute(): bool
    {
        return $this->payment_method === 'transfer';
    }

    public function getIsCardPaymentAttribute(): bool
    {
        return $this->payment_method === 'card';
    }

    // Scopes
    public function scopeByMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('payment_date', today());
    }
}