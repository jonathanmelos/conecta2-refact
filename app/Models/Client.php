<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $fillable = [
        'document_number',
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'ruc',
        'client_status',
        'subscription_status',
        'current_subscription_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function currentSubscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'current_subscription_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function usageRecords(): HasMany
    {
        return $this->hasMany(UsageRecord::class);
    }

    public function hoursTracking(): HasMany
    {
        return $this->hasMany(HoursTracking::class);
    }

    public function accessMethods(): HasMany
    {
        return $this->hasMany(AccessMethod::class);
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(AccessLog::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->client_status === 'active';
    }

    public function getHasActiveSubscriptionAttribute(): bool
    {
        return $this->subscription_status === 'active';
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('client_status', 'active');
    }

    public function scopeWithActiveSubscription($query)
    {
        return $query->where('subscription_status', 'active');
    }
}