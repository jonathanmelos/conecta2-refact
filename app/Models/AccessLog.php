<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessLog extends Model
{
    protected $fillable = [
        'client_id',
        'access_method',
        'access_value',
        'event_type',
        'timestamp',
        'area_id',
        'device_ip',
        'device_mac',
        'device_name',
        'iot_device_id',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
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
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    // Accessors
    public function getIsEntryAttribute(): bool
    {
        return $this->event_type === 'entry';
    }

    public function getIsExitAttribute(): bool
    {
        return $this->event_type === 'exit';
    }

    public function getIsManualAttribute(): bool
    {
        return $this->access_method === 'manual';
    }

    public function getIsAutomaticAttribute(): bool
    {
        return in_array($this->access_method, ['pin_code', 'rfid_card', 'face_recognition']);
    }

    // Scopes
    public function scopeEntries($query)
    {
        return $query->where('event_type', 'entry');
    }

    public function scopeExits($query)
    {
        return $query->where('event_type', 'exit');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('timestamp', today());
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('access_method', $method);
    }
}