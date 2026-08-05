<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class McpPermission extends Model
{
    protected $fillable = [
        'resource',
        'read_enabled',
    ];

    protected function casts(): array
    {
        return [
            'read_enabled' => 'boolean',
        ];
    }

    public static function isResourceEnabled(string $resource): bool
    {
        return (bool) static::where('resource', $resource)->value('read_enabled');
    }
}
