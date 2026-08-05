<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class McpAuditLog extends Model
{
    // Eloquent's default pluralization of "McpAuditLog" is "mcp_audit_logs",
    // but the migration (and every other mcp_* table) uses the singular
    // "mcp_audit_log" — explicit override needed to match.
    protected $table = 'mcp_audit_log';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'client_id',
        'tool_name',
        'arguments',
        'result_status',
        'result_summary',
        'ip_address',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'arguments' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
