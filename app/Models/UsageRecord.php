<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class UsageRecord extends Model
{
    protected $fillable = [
        'client_id',
        'subscription_id',
        'area_id',
        'service_type',
        'check_in',
        'check_out',
        'quantity',
        'status',
        'registration_method',
        'is_billable',
        'unit_price',
        'total_charged',
        'invoiced',
        'invoice_item_id',
        'notes',
    ];

    protected $casts = [
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'quantity' => 'integer',
        'is_billable' => 'boolean',
        'unit_price' => 'decimal:2',
        'total_charged' => 'decimal:2',
        'invoiced' => 'boolean',
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

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class);
    }

    // Accessors
    public function getDurationInHoursAttribute(): ?float
    {
        if (!$this->check_out) {
            return null;
        }
        
        return $this->check_in->diffInHours($this->check_out, true);
    }

    public function getDurationInMinutesAttribute(): ?int
    {
        if (!$this->check_out) {
            return null;
        }
        
        return $this->check_in->diffInMinutes($this->check_out);
    }

    public function getIsInProgressAttribute(): bool
    {
        return $this->status === 'in_progress';
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === 'completed';
    }

    // Scopes
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('check_in', today());
    }

    public function scopeByServiceType($query, $type)
    {
        return $query->where('service_type', $type);
    }

    public function scopeUnbilled($query)
    {
        return $query->where('is_billable', true)
            ->where('invoiced', false);
    }

}
