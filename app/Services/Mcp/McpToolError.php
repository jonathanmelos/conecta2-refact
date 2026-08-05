<?php

namespace App\Services\Mcp;

/**
 * Lightweight error value object for MCP tool calls — avoids using
 * exceptions for expected rejection cases (disabled resource, missing
 * argument) so the JSON-RPC layer can turn it into a normal tool result
 * with isError: true instead of a 500.
 */
class McpToolError
{
    public function __construct(
        public readonly string $code,
        public readonly string $message
    ) {
    }
}
