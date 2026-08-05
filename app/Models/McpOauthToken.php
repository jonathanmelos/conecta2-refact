<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class McpOauthToken extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'access_token_hash',
        'refresh_token_hash',
        'client_id',
        'user_id',
        'scopes',
        'access_expires_at',
        'refresh_expires_at',
        'revoked',
        'last_used_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'scopes' => 'array',
            'access_expires_at' => 'datetime',
            'refresh_expires_at' => 'datetime',
            'revoked' => 'boolean',
            'last_used_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isAccessValid(): bool
    {
        return !$this->revoked && $this->access_expires_at->isFuture();
    }
}
