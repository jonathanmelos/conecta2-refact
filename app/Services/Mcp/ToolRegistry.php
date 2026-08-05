<?php

namespace App\Services\Mcp;

use App\Models\McpPermission;
use App\Models\User;
use App\Services\Mcp\Tools\ClientsTool;
use App\Services\Mcp\Tools\BillingTool;
use App\Services\Mcp\Tools\OccupancyTool;
use App\Services\Mcp\Tools\SubscriptionsTool;
use App\Services\Mcp\Tools\UsageTool;

/**
 * Central registry of MCP tools for the Laravel read-only connector.
 * Every tool here only ever queries data — none of them create, update, or
 * delete anything. Visibility in tools/list is filtered by what's enabled
 * in the "Permisos MCP" admin panel (mcp_permissions), and every tool call
 * additionally requires the authenticated user to have the admin role,
 * since this exposes operational/financial data.
 */
class ToolRegistry
{
    /** @var array<string,object> tool_name => handler instance */
    private array $handlers = [];

    /** @var array<string,array> tool_name => definition */
    private array $definitions = [];

    /** @var array<string,string> tool_name => required mcp_permissions resource */
    private array $resourceForTool = [];

    public function __construct()
    {
        $this->register(new ClientsTool());
        $this->register(new SubscriptionsTool());
        $this->register(new BillingTool());
        $this->register(new UsageTool());
        $this->register(new OccupancyTool());
    }

    /**
     * Every tool definition must declare its own 'resource' key (the
     * mcp_permissions row that gates it) — tools like BillingTool expose
     * both "invoices" and "payments" under one class, so there is no single
     * default resource per handler class.
     */
    private function register(object $handler): void
    {
        foreach ($handler->definitions() as $definition) {
            $name = $definition['name'];
            $this->definitions[$name] = $definition;
            $this->handlers[$name] = $handler;
            $this->resourceForTool[$name] = $definition['resource'];
        }
    }

    public function listForUser(User $user): array
    {
        if ($user->role !== 'admin') {
            return [];
        }

        $enabled = McpPermission::where('read_enabled', true)->pluck('resource')->all();

        $visible = [];
        foreach ($this->definitions as $name => $definition) {
            $resource = $this->resourceForTool[$name] ?? null;
            if ($resource && in_array($resource, $enabled, true)) {
                $visible[] = $definition;
            }
        }

        return $visible;
    }

    public function call(string $toolName, array $arguments, User $user): array|McpToolError
    {
        if (!isset($this->handlers[$toolName])) {
            return new McpToolError('unknown_tool', "Tool no reconocida: {$toolName}");
        }

        if ($user->role !== 'admin') {
            return new McpToolError('insufficient_role', 'Se requiere rol admin para consultar datos vía MCP.');
        }

        $resource = $this->resourceForTool[$toolName];
        if (!McpPermission::isResourceEnabled($resource)) {
            return new McpToolError(
                'module_disabled',
                "El recurso \"{$resource}\" está desactivado en el panel MCP Connector."
            );
        }

        return $this->handlers[$toolName]->call($toolName, $arguments);
    }

    public function hasTool(string $toolName): bool
    {
        return isset($this->handlers[$toolName]);
    }
}
