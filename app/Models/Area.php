<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Area extends Model
{
    protected $fillable = [
        'name',
        'code',
        'capacity',
        'description',
        'has_sensor',
        'sensor_device_id',
        'is_active',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'has_sensor' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function usageRecords(): HasMany
    {
        return $this->hasMany(UsageRecord::class);
    }

    public function occupancy(): HasMany
    {
        return $this->hasMany(AreaOccupancy::class);
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(AccessLog::class);
    }

    // Accessors
    public function getCurrentOccupancyAttribute(): int
    {
        return $this->occupancy()
            ->whereNull('check_out')
            ->count();
    }

    public function getIsFullAttribute(): bool
    {
        return $this->current_occupancy >= $this->capacity;
    }

    public function getAvailableSpaceAttribute(): int
    {
        return max(0, $this->capacity - $this->current_occupancy);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithSensor($query)
    {
        return $query->where('has_sensor', true);
    }
}