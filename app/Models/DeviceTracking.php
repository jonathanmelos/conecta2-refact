<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceTracking extends Model
{
    protected $table = 'device_tracking';

    protected $fillable = [
        'client_id',
        'device_ip',
        'device_mac',
        'device_name',
        'device_type',
        'last_seen',
        'area_id',
        'is_connected',
    ];

    protected $casts = [
        'last_seen' => 'datetime',
        'is_connected' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    // Accessors
    public function getIsOnlineAttribute(): bool
    {
        return $this->is_connected && $this->last_seen > now()->subMinutes(5);
    }

    public function getMinutesSinceLastSeenAttribute(): int
    {
        return now()->diffInMinutes($this->last_seen);
    }

    public function getIsLaptopAttribute(): bool
    {
        return $this->device_type === 'laptop';
    }

    public function getIsPhoneAttribute(): bool
    {
        return $this->device_type === 'phone';
    }

    public function getIsTabletAttribute(): bool
    {
        return $this->device_type === 'tablet';
    }

    // Scopes
    public function scopeOnline($query)
    {
        return $query->where('is_connected', true)
            ->where('last_seen', '>', now()->subMinutes(5));
    }

    public function scopeOffline($query)
    {
        return $query->where('is_connected', false)
            ->orWhere('last_seen', '<=', now()->subMinutes(5));
    }

    public function scopeByType($query, $type)
    {
        return $query->where('device_type', $type);
    }

    public function scopeInArea($query, $areaId)
    {
        return $query->where('area_id', $areaId);
    }

    public function scopeSeenToday($query)
    {
        return $query->whereDate('last_seen', today());
    }
}