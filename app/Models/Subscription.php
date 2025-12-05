<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Subscription extends Model
{
    protected $fillable = [
        'client_id',
        'plan_id',
        'start_date',
        'end_date',
        'status',
        'monthly_price',
        'setup_fee_paid',
        'deposit_paid',
        'discount_applied',
        'total_paid',
        'billing_cycle',
        'next_billing_date',
        'auto_renew',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'next_billing_date' => 'date',
        'monthly_price' => 'decimal:2',
        'setup_fee_paid' => 'decimal:2',
        'deposit_paid' => 'decimal:2',
        'discount_applied' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'auto_renew' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function usageRecords(): HasMany
    {
        return $this->hasMany(UsageRecord::class);
    }

    public function hoursTracking(): HasMany
    {
        return $this->hasMany(HoursTracking::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    // Accessors
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active' && $this->end_date >= now();
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->end_date < now();
    }

    public function getDaysRemainingAttribute(): int
    {
        return now()->diffInDays($this->end_date, false);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('end_date', '>=', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('end_date', '<', now());
    }

    public function scopeExpiringSoon($query, $days = 7)
    {
        return $query->where('status', 'active')
            ->whereBetween('end_date', [now(), now()->addDays($days)]);
    }
}