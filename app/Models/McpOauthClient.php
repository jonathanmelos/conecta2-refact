<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class McpOauthClient extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'client_id',
        'client_name',
        'redirect_uris',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'redirect_uris' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function redirectUriIsAllowed(string $redirectUri): bool
    {
        return in_array($redirectUri, $this->redirect_uris ?? [], true);
    }
}
