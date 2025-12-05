<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServicePrice extends Model
{
    protected $fillable = [
        'service_name',
        'service_type',
        'price',
        'unit',
        'valid_from',
        'valid_until',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Accessors
    public function getIsCurrentlyValidAttribute(): bool
    {
        $now = today();
        
        if (!$this->is_active) {
            return false;
        }
        
        if ($now < $this->valid_from) {
            return false;
        }
        
        if ($this->valid_until && $now > $this->valid_until) {
            return false;
        }
        
        return true;
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->valid_until && today() > $this->valid_until;
    }

    public function getDaysUntilExpirationAttribute(): ?int
    {
        if (!$this->valid_until) {
            return null;
        }
        
        return today()->diffInDays($this->valid_until, false);
    }

    public function getFormattedPriceAttribute(): string
    {
        return '$' . number_format($this->price, 2) . ' ' . $this->unit;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValid($query)
    {
        return $query->where('is_active', true)
            ->where('valid_from', '<=', today())
            ->where(function ($q) {
                $q->whereNull('valid_until')
                  ->orWhere('valid_until', '>=', today());
            });
    }

    public function scopeByType($query, $type)
    {
        return $query->where('service_type', $type);
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('valid_until')
            ->where('valid_until', '<', today());
    }

    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->whereNotNull('valid_until')
            ->whereBetween('valid_until', [today(), today()->addDays($days)]);
    }
}