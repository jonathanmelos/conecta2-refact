<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AreaOccupancy extends Model
{
    protected $table = 'area_occupancy'; // ✅ Nombre correcto de tabla

    protected $fillable = [
        'client_id',
        'area_id',
        'check_in',
        'check_out',
        'detected_by',
        'device_ip',
        'device_mac',
        'sensor_device_id',
    ];

    protected $casts = [
        'check_in' => 'datetime',
        'check_out' => 'datetime',
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
    public function getIsActiveAttribute(): bool
    {
        return is_null($this->check_out);
    }

    public function getDurationInMinutesAttribute(): ?int
    {
        if (!$this->check_out) {
            return null;
        }
        return $this->check_in->diffInMinutes($this->check_out);
    }

    public function getDurationInHoursAttribute(): ?float
    {
        if (!$this->check_out) {
            return null;
        }
        return $this->check_in->diffInHours($this->check_out, true);
    }

    public function getWasDetectedBySensorAttribute(): bool
    {
        return $this->detected_by === 'sensor';
    }

    public function getWasDetectedByIpAttribute(): bool
    {
        return $this->detected_by === 'ip_tracking';
    }

    public function getWasManualAttribute(): bool
    {
        return $this->detected_by === 'manual';
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereNull('check_out');
    }

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('check_out');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('check_in', today());
    }

    public function scopeByDetectionMethod($query, $method)
    {
        return $query->where('detected_by', $method);
    }

    public function scopeInArea($query, $areaId)
    {
        return $query->where('area_id', $areaId);
    }
}