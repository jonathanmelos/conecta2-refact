<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\McpAuditLog;
use App\Models\McpOauthClient;
use App\Models\McpOauthToken;
use App\Models\McpPermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class McpController extends Controller
{
    public function permissions(): View
    {
        return view('admin.mcp.permissions', [
            'permissions' => McpPermission::orderBy('resource')->get(),
            'mcpEndpoint' => route('mcp.server'),
            'metadataEndpoint' => url('/.well-known/oauth-authorization-server'),
        ]);
    }

    public function updatePermissions(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'resources' => ['array'],
            'resources.*' => ['string'],
        ]);

        $enabled = $validated['resources'] ?? [];

        McpPermission::query()->update(['read_enabled' => false]);
        McpPermission::whereIn('resource', $enabled)->update(['read_enabled' => true]);

        return redirect()->route('admin.mcp.permissions')->with('status', 'Permisos MCP actualizados.');
    }

    public function connections(): View
    {
        $tokens = McpOauthToken::query()
            ->with('user')
            ->where('revoked', false)
            ->where('refresh_expires_at', '>', now())
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($token) {
                $client = McpOauthClient::where('client_id', $token->client_id)->first();
                return (object) [
                    'id' => $token->id,
                    'client_name' => $client?->client_name ?? $token->client_id,
                    'user_name' => $token->user?->name,
                    'created_at' => $token->created_at,
                    'last_used_at' => $token->last_used_at,
                    'refresh_expires_at' => $token->refresh_expires_at,
                ];
            });

        return view('admin.mcp.connections', ['tokens' => $tokens]);
    }

    public function revokeConnection(int $tokenId): RedirectResponse
    {
        McpOauthToken::where('id', $tokenId)->update(['revoked' => true]);

        return redirect()->route('admin.mcp.connections')->with('status', 'Conexión revocada.');
    }

    public function auditLog(): View
    {
        $logs = McpAuditLog::query()
            ->with('user')
            ->orderByDesc('created_at')
            ->paginate(30);

        return view('admin.mcp.audit', ['logs' => $logs]);
    }
}
