<?php

namespace App\Http\Middleware;

use App\Models\McpOauthToken;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates MCP requests via "Authorization: Bearer <access_token>"
 * against mcp_oauth_tokens, resolving the underlying Laravel User and
 * attaching both to the request for the controller/tools to use.
 */
class McpAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization', '');

        if (!str_starts_with($header, 'Bearer ')) {
            return response()->json([
                'jsonrpc' => '2.0',
                'id' => null,
                'error' => ['code' => -32001, 'message' => 'Falta el header Authorization: Bearer <token>.'],
            ], 401);
        }

        $accessToken = trim(substr($header, 7));
        $tokenRow = McpOauthToken::where('access_token_hash', hash('sha256', $accessToken))->first();

        if (!$tokenRow || !$tokenRow->isAccessValid()) {
            return response()->json([
                'jsonrpc' => '2.0',
                'id' => null,
                'error' => ['code' => -32001, 'message' => 'Token de acceso inválido o expirado.'],
            ], 401);
        }

        $user = User::find($tokenRow->user_id);
        if (!$user) {
            return response()->json([
                'jsonrpc' => '2.0',
                'id' => null,
                'error' => ['code' => -32001, 'message' => 'El usuario asociado a este token ya no existe.'],
            ], 401);
        }

        $tokenRow->update(['last_used_at' => now()]);

        $request->attributes->set('mcp_user', $user);
        $request->attributes->set('mcp_client_id', $tokenRow->client_id);

        return $next($request);
    }
}
