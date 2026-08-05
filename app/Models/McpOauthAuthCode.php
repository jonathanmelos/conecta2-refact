<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class McpOauthAuthCode extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'code_hash',
        'client_id',
        'user_id',
        'redirect_uri',
        'scopes',
        'code_challenge',
        'code_challenge_method',
        'expires_at',
        'used',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'scopes' => 'array',
            'expires_at' => 'datetime',
            'used' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
