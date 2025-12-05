<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessMethod extends Model
{
    protected $fillable = [
        'client_id',
        'access_code',
        'rfid_card',
        'face_recognition_id',
        'is_active',
        'expires_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    // Accessors
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at < now();
    }

    public function getHasAccessCodeAttribute(): bool
    {
        return !empty($this->access_code);
    }

    public function getHasRfidCardAttribute(): bool
    {
        return !empty($this->rfid_card);
    }

    public function getHasFaceRecognitionAttribute(): bool
    {
        return !empty($this->face_recognition_id);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }
}