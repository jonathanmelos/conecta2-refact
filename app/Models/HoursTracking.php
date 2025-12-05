<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HoursTracking extends Model
{
    protected $table = 'hours_tracking';

    protected $fillable = [
        'client_id',
        'subscription_id',
        'service_type',
        'hours_used',
        'total_hours_available',
        'last_updated',
    ];

    protected $casts = [
        'hours_used' => 'decimal:2',
        'total_hours_available' => 'decimal:2',
        'last_updated' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    // Accessors
    public function getUsagePercentageAttribute(): float
    {
        if ($this->total_hours_available <= 0) {
            return 0;
        }
        
        return ($this->hours_used / $this->total_hours_available) * 100;
    }

    public function getIsAlmostFullAttribute(): bool
    {
        return $this->usage_percentage >= 80;
    }

    public function getIsOverLimitAttribute(): bool
    {
        return $this->hours_used > $this->total_hours_available;
    }

    // Scopes
    public function scopeByServiceType($query, $type)
    {
        return $query->where('service_type', $type);
    }

    public function scopeAlmostFull($query)
    {
        return $query->whereRaw('(hours_used / total_hours_available) * 100 >= 80');
    }

    public function scopeOverLimit($query)
    {
        return $query->whereRaw('hours_used > total_hours_available');
    }
}