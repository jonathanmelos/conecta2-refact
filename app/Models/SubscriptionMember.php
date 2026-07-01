<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionMember extends Model
{
    protected $fillable = [
        'subscription_id',
        'client_id',
        'can_use_cowork',
        'can_use_meeting_room',
        'is_default',
        'is_default_cowork',
        'is_default_meeting_room',
    ];

    protected $casts = [
        'can_use_cowork' => 'boolean',
        'can_use_meeting_room' => 'boolean',
        'is_default' => 'boolean',
        'is_default_cowork' => 'boolean',
        'is_default_meeting_room' => 'boolean',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
