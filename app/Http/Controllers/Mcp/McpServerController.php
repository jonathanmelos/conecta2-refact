<?php

namespace App\Http\Controllers\Mcp;

use App\Http\Controllers\Controller;
use App\Models\McpAuditLog;
use App\Models\User;
use App\Services\Mcp\McpToolError;
use App\Services\Mcp\ToolRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * MCP server endpoint: JSON-RPC 2.0 over a single HTTP POST route
 * (non-streaming Streamable HTTP transport, same shape as the WordPress
 * connector's wp-mcp/v1/mcp endpoint). Read-only by construction — see
 * ToolRegistry / app/Services/Mcp/Tools/*, none of which touch write paths.
 */
class McpServerController extends Controller
{
    private const PROTOCOL_VERSION = '2025-06-18';

    public function __construct(private readonly ToolRegistry $registry)
    {
    }

    public function handle(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('mcp_user');

        $body = $request->json()->all();
        if (!is_array($body) || !isset($body['method'])) {
            return response()->json($this->jsonRpcError(null, -32600, 'Invalid Request.'), 400);
        }

        $id = $body['id'] ?? null;
        $method = $body['method'];
        $params = is_array($body['params'] ?? null) ? $body['params'] : [];

        return match ($method) {
            'initialize' => response()->json($this->jsonRpcResult($id, $this->initializeResult())),
            'notifications/initialized' => response()->json(null, 202),
            'tools/list' => response()->json($this->jsonRpcResult($id, ['tools' => $this->registry->listForUser($user)])),
            'tools/call' => $this->handleToolsCall($id, $params, $user, $request),
            default => response()->json($this->jsonRpcError($id, -32601, "Method not found: {$method}")),
        };
    }

    private function initializeResult(): array
    {
        return [
            'protocolVersion' => self::PROTOCOL_VERSION,
            'capabilities' => ['tools' => new \stdClass()],
            'serverInfo' => ['name' => 'conecta-sistema-mcp', 'version' => '1.0.0'],
        ];
    }

    private function handleToolsCall(mixed $id, array $params, User $user, Request $request): JsonResponse
    {
        $toolName = $params['name'] ?? '';
        $arguments = is_array($params['arguments'] ?? null) ? $params['arguments'] : [];

        if (!$this->registry->hasTool($toolName)) {
            $this->log($user, $toolName, $arguments, 'error', 'unknown_tool', $request);
            return response()->json($this->jsonRpcError($id, -32602, "Unknown tool: {$toolName}"));
        }

        $result = $this->registry->call($toolName, $arguments, $user);

        if ($result instanceof McpToolError) {
            $this->log($user, $toolName, $arguments, 'denied', $result->message, $request);

            return response()->json($this->jsonRpcResult($id, [
                'isError' => true,
                'content' => [['type' => 'text', 'text' => $result->message]],
            ]));
        }

        $this->log($user, $toolName, $arguments, 'success', '', $request);

        return response()->json($this->jsonRpcResult($id, [
            'content' => [['type' => 'text', 'text' => json_encode($result, JSON_UNESCAPED_UNICODE)]],
            'isError' => false,
        ]));
    }

    private function log(User $user, string $toolName, array $arguments, string $status, string $summary, Request $request): void
    {
        McpAuditLog::create([
            'user_id' => $user->id,
            'client_id' => $request->attributes->get('mcp_client_id'),
            'tool_name' => $toolName,
            'arguments' => $arguments,
            'result_status' => $status,
            'result_summary' => mb_substr($summary, 0, 500),
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);
    }

    private function jsonRpcResult(mixed $id, array $result): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result];
    }

    private function jsonRpcError(mixed $id, int $code, string $message): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => $code, 'message' => $message]];
    }
}
