<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'cowork_hours',
        'meeting_room_hours',
        'prints_included',
        'events_included',
        'price',
        'setup_fee',
        'deposit_required',
        'operational_cost',
        'discount_percentage',
        'description',
        'is_pilot',
        'is_ultra_custom',
        'is_active',
    ];

    protected $casts = [
        'cowork_hours' => 'integer',
        'meeting_room_hours' => 'integer',
        'prints_included' => 'integer',
        'events_included' => 'integer',
        'price' => 'decimal:2',
        'setup_fee' => 'decimal:2',
        'deposit_required' => 'decimal:2',
        'operational_cost' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'is_pilot' => 'boolean',
        'is_ultra_custom' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    // Accessors
    public function getProfitMarginAttribute(): float
    {
        if ($this->price <= 0) {
            return 0;
        }
        return (($this->price - $this->operational_cost) / $this->price) * 100;
    }

    public function getFinalPriceAttribute(): float
    {
        $discount = ($this->price * $this->discount_percentage) / 100;
        return $this->price - $discount;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePopular($query)
    {
        return $query->withCount('subscriptions')
            ->orderBy('subscriptions_count', 'desc');
    }
}
