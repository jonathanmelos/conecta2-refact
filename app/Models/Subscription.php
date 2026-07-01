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
        'is_ultra_custom',
        'custom_cowork_hours',
        'custom_meeting_room_hours',
        'custom_prints_included',
        'custom_events_included',
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
        'is_ultra_custom' => 'boolean',
        'custom_cowork_hours' => 'decimal:2',
        'custom_meeting_room_hours' => 'decimal:2',
        'custom_prints_included' => 'integer',
        'custom_events_included' => 'integer',
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

    public function members(): HasMany
    {
        return $this->hasMany(SubscriptionMember::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    // Accessors
    public function getIsActiveAttribute(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if (!$this->end_date) {
            return $this->start_date <= now();
        }

        return $this->end_date >= now();
    }

    public function getIsExpiredAttribute(): bool
    {
        if (!$this->end_date) {
            return false;
        }

        return $this->end_date < now();
    }

    public function getDaysRemainingAttribute(): ?int
    {
        if (!$this->end_date) {
            return null;
        }

        return now()->diffInDays($this->end_date, false);
    }

    public function getEffectiveCoworkHoursAttribute(): float
    {
        if ($this->is_ultra_custom && $this->custom_cowork_hours !== null) {
            return (float) $this->custom_cowork_hours;
        }

        return (float) ($this->plan->cowork_hours ?? 0);
    }

    public function getEffectiveMeetingRoomHoursAttribute(): float
    {
        if ($this->is_ultra_custom && $this->custom_meeting_room_hours !== null) {
            return (float) $this->custom_meeting_room_hours;
        }

        return (float) ($this->plan->meeting_room_hours ?? 0);
    }

    public function getEffectivePrintsIncludedAttribute(): int
    {
        if ($this->is_ultra_custom && $this->custom_prints_included !== null) {
            return (int) $this->custom_prints_included;
        }

        return (int) ($this->plan->prints_included ?? 0);
    }

    public function getEffectiveEventsIncludedAttribute(): int
    {
        if ($this->is_ultra_custom && $this->custom_events_included !== null) {
            return (int) $this->custom_events_included;
        }

        return (int) ($this->plan->events_included ?? 0);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->whereDate('start_date', '<=', now())
            ->where(function ($q) {
                $q->where('end_date', '>=', now())
                    ->orWhereNull('end_date');
            });
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('end_date')
            ->where('end_date', '<', now());
    }

    public function scopeExpiringSoon($query, $days = 7)
    {
        return $query->where('status', 'active')
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [now(), now()->addDays($days)]);
    }
}
