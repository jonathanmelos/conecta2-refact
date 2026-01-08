<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Event extends Model
{
    protected $fillable = [
        'title',
        'description',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'all_day',
        'color',
        'location',
        'client_id',
        'user_id',
        'status',
        'type',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'all_day' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeByMonth($query, $year, $month)
    {
        return $query->whereYear('start_date', $year)
                     ->whereMonth('start_date', $month);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>=', today())
                     ->orderBy('start_date')
                     ->orderBy('start_time');
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // Accessors
    public function getFormattedDateAttribute(): string
    {
        if ($this->end_date && $this->start_date->ne($this->end_date)) {
            return $this->start_date->format('d/m/Y') . ' - ' . $this->end_date->format('d/m/Y');
        }
        return $this->start_date->format('d/m/Y');
    }

    public function getFormattedTimeAttribute(): string
    {
        if ($this->all_day) {
            return 'Todo el día';
        }

        $time = $this->start_time;
        if ($this->end_time) {
            $time .= ' - ' . $this->end_time;
        }
        return $time ?? '';
    }
}
