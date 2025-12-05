<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    protected $fillable = [
        'client_id',
        'reservation_type',
        'event_title',
        'event_description',
        'event_mode',
        'attendees_count',
        'reservation_date',
        'start_time',
        'end_time',
        'status',
        'notes',
        'google_calendar_id',
        'google_event_synced',
    ];

    protected $casts = [
        'reservation_date' => 'date',
        'attendees_count' => 'integer',
        'google_event_synced' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    // Accessors
    public function getFullDateTimeStartAttribute(): string
    {
        return $this->reservation_date->format('Y-m-d') . ' ' . $this->start_time;
    }

    public function getFullDateTimeEndAttribute(): string
    {
        return $this->reservation_date->format('Y-m-d') . ' ' . $this->end_time;
    }

    public function getIsCorporateEventAttribute(): bool
    {
        return $this->reservation_type === 'corporate_event';
    }

    public function getIsConfirmedAttribute(): bool
    {
        return $this->status === 'confirmed';
    }

    public function getIsSyncedWithGoogleAttribute(): bool
    {
        return $this->google_event_synced && !empty($this->google_calendar_id);
    }

    // Scopes
    public function scopeUpcoming($query)
    {
        return $query->where('reservation_date', '>=', today())
            ->where('status', 'confirmed')
            ->orderBy('reservation_date')
            ->orderBy('start_time');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('reservation_date', today());
    }

    public function scopeByType($query, $type)
    {
        return $query->where('reservation_type', $type);
    }

    public function scopeCorporateEvents($query)
    {
        return $query->where('reservation_type', 'corporate_event');
    }
}