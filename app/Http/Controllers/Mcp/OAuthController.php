<?php

namespace App\Http\Controllers\Mcp;

use App\Http\Controllers\Controller;
use App\Models\McpOauthAuthCode;
use App\Models\McpOauthClient;
use App\Models\McpOauthToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * OAuth 2.1 authorization server for the MCP connector: metadata discovery,
 * dynamic client registration (RFC 7591), the authorize/consent screen,
 * token issuance (authorization_code + refresh_token, PKCE S256 required),
 * and revocation. Read-only resource server — see McpServerController for
 * the actual data endpoint this protects.
 */
class OAuthController extends Controller
{
    private const ACCESS_TOKEN_TTL_MINUTES = 60;
    private const REFRESH_TOKEN_TTL_DAYS = 30;
    private const AUTH_CODE_TTL_MINUTES = 5;

    public function authorizationServerMetadata(): JsonResponse
    {
        return response()->json([
            'issuer' => url('/'),
            'authorization_endpoint' => route('mcp.oauth.authorize'),
            'token_endpoint' => route('mcp.oauth.token'),
            'registration_endpoint' => route('mcp.oauth.register'),
            'revocation_endpoint' => route('mcp.oauth.revoke'),
            'response_types_supported' => ['code'],
            'grant_types_supported' => ['authorization_code', 'refresh_token'],
            'code_challenge_methods_supported' => ['S256'],
            'token_endpoint_auth_methods_supported' => ['none'],
            'scopes_supported' => ['mcp'],
        ]);
    }

    public function protectedResourceMetadata(): JsonResponse
    {
        return response()->json([
            'resource' => route('mcp.server'),
            'authorization_servers' => [url('/')],
            'bearer_methods_supported' => ['header'],
            'scopes_supported' => ['mcp'],
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $this->requireHttps();

        $validated = $request->validate([
            'client_name' => ['nullable', 'string', 'max:191'],
            'redirect_uris' => ['required', 'array', 'min:1'],
            'redirect_uris.*' => ['required', 'url'],
        ]);

        $client = McpOauthClient::create([
            'client_id' => 'mcp_' . Str::random(32),
            'client_name' => $validated['client_name'] ?? 'MCP Client',
            'redirect_uris' => $validated['redirect_uris'],
            'created_at' => now(),
        ]);

        return response()->json([
            'client_id' => $client->client_id,
            'client_name' => $client->client_name,
            'redirect_uris' => $client->redirect_uris,
            'token_endpoint_auth_method' => 'none',
            'grant_types' => ['authorization_code', 'refresh_token'],
            'response_types' => ['code'],
        ], 201);
    }

    public function showAuthorize(Request $request): RedirectResponse|\Illuminate\View\View
    {
        $this->requireHttps();

        $params = $this->extractAuthorizeParams($request);

        abort_if($params['response_type'] !== 'code', 400, 'Unsupported response_type. Only "code" is supported.');
        abort_if(empty($params['code_challenge']) || $params['code_challenge_method'] !== 'S256', 400, 'PKCE (S256) is required.');

        $client = McpOauthClient::where('client_id', $params['client_id'])->first();
        abort_if(!$client, 400, 'Unknown OAuth client.');
        abort_if(!$client->redirectUriIsAllowed($params['redirect_uri']), 400, 'redirect_uri does not match the registered client.');

        // Laravel's own session guard handles this natively — no custom
        // current-user re-resolution hack was needed here, unlike the
        // WordPress version of this connector (see wp-mcp-connector's
        // OAuth_Server::current_visitor_is_logged_in() for that story).
        // redirect()->guest() stores this URL as the "intended" destination,
        // which AuthenticatedSessionController::store() already honors via
        // redirect()->intended() after a successful login.
        if (!Auth::check()) {
            return redirect()->guest(route('login'));
        }

        return view('mcp.authorize', [
            'client' => $client,
            'params' => $params,
        ]);
    }

    public function submitAuthorize(Request $request): RedirectResponse
    {
        $this->requireHttps();
        abort_unless(Auth::check(), 401, 'Session expired. Please restart the connection.');

        $validated = $request->validate([
            'decision' => ['required', 'in:allow,deny'],
            'client_id' => ['required', 'string'],
            'redirect_uri' => ['required', 'url'],
            'state' => ['nullable', 'string'],
            'code_challenge' => ['required', 'string'],
            'code_challenge_method' => ['required', 'in:S256'],
            'scope' => ['nullable', 'string'],
        ]);

        $client = McpOauthClient::where('client_id', $validated['client_id'])->first();
        abort_if(!$client, 400, 'Unknown OAuth client.');
        abort_if(!$client->redirectUriIsAllowed($validated['redirect_uri']), 400, 'redirect_uri does not match the registered client.');

        if ($validated['decision'] !== 'allow') {
            $redirect = $validated['redirect_uri'] . '?' . http_build_query(array_filter([
                'error' => 'access_denied',
                'state' => $validated['state'] ?? null,
            ]));

            // redirect()->away() intentionally has no same-host restriction
            // (unlike WordPress's wp_safe_redirect(), whose silent fallback
            // to admin_url() broke this exact step there) — the target was
            // already validated above against the client's registered
            // redirect_uris, so redirecting off-host here is correct.
            return redirect()->away($redirect);
        }

        $scopes = array_filter(explode(' ', $validated['scope'] ?? 'mcp'));

        $code = 'ac_' . Str::random(48);
        McpOauthAuthCode::create([
            'code_hash' => hash('sha256', $code),
            'client_id' => $client->client_id,
            'user_id' => Auth::id(),
            'redirect_uri' => $validated['redirect_uri'],
            'scopes' => array_values($scopes),
            'code_challenge' => $validated['code_challenge'],
            'code_challenge_method' => $validated['code_challenge_method'],
            'expires_at' => now()->addMinutes(self::AUTH_CODE_TTL_MINUTES),
            'used' => false,
            'created_at' => now(),
        ]);

        $redirect = $validated['redirect_uri'] . '?' . http_build_query(array_filter([
            'code' => $code,
            'state' => $validated['state'] ?? null,
        ]));

        return redirect()->away($redirect);
    }

    public function token(Request $request): JsonResponse
    {
        $this->requireHttps();

        $grantType = $request->input('grant_type');

        return match ($grantType) {
            'authorization_code' => $this->grantAuthorizationCode($request),
            'refresh_token' => $this->grantRefreshToken($request),
            default => response()->json([
                'error' => 'unsupported_grant_type',
                'error_description' => 'Only authorization_code and refresh_token are supported.',
            ], 400),
        };
    }

    private function grantAuthorizationCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
            'redirect_uri' => ['required', 'string'],
            'client_id' => ['required', 'string'],
            'code_verifier' => ['required', 'string'],
        ]);

        $authCode = McpOauthAuthCode::where('code_hash', hash('sha256', $validated['code']))->first();

        if (!$authCode) {
            return response()->json(['error' => 'invalid_grant', 'error_description' => 'Authorization code not found.'], 400);
        }
        if ($authCode->used) {
            return response()->json(['error' => 'invalid_grant', 'error_description' => 'Authorization code already used.'], 400);
        }
        if ($authCode->isExpired()) {
            return response()->json(['error' => 'invalid_grant', 'error_description' => 'Authorization code expired.'], 400);
        }
        if ($authCode->client_id !== $validated['client_id'] || $authCode->redirect_uri !== $validated['redirect_uri']) {
            return response()->json(['error' => 'invalid_grant', 'error_description' => 'Client or redirect_uri mismatch.'], 400);
        }
        if (!$this->verifyPkce($validated['code_verifier'], $authCode->code_challenge)) {
            return response()->json(['error' => 'invalid_grant', 'error_description' => 'PKCE verification failed.'], 400);
        }

        $authCode->update(['used' => true]);

        $tokens = $this->issueTokens($authCode->client_id, $authCode->user_id, $authCode->scopes ?? ['mcp']);

        return response()->json($tokens);
    }

    private function grantRefreshToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'refresh_token' => ['required', 'string'],
            'client_id' => ['required', 'string'],
        ]);

        $token = McpOauthToken::where('refresh_token_hash', hash('sha256', $validated['refresh_token']))
            ->where('client_id', $validated['client_id'])
            ->first();

        if (!$token || $token->revoked) {
            return response()->json(['error' => 'invalid_grant', 'error_description' => 'Refresh token not found or revoked.'], 400);
        }
        if (!$token->refresh_expires_at || $token->refresh_expires_at->isPast()) {
            return response()->json(['error' => 'invalid_grant', 'error_description' => 'Refresh token expired.'], 400);
        }

        // Rotate: revoke the old pair, issue a fresh one.
        $token->update(['revoked' => true]);

        $tokens = $this->issueTokens($token->client_id, $token->user_id, $token->scopes ?? ['mcp']);

        return response()->json($tokens);
    }

    public function revoke(Request $request): JsonResponse
    {
        $validated = $request->validate(['token' => ['required', 'string']]);

        McpOauthToken::where('access_token_hash', hash('sha256', $validated['token']))
            ->update(['revoked' => true]);

        return response()->json(['revoked' => true]);
    }

    private function issueTokens(string $clientId, int $userId, array $scopes): array
    {
        $accessToken = 'at_' . Str::random(48);
        $refreshToken = 'rt_' . Str::random(48);

        McpOauthToken::create([
            'access_token_hash' => hash('sha256', $accessToken),
            'refresh_token_hash' => hash('sha256', $refreshToken),
            'client_id' => $clientId,
            'user_id' => $userId,
            'scopes' => array_values($scopes),
            'access_expires_at' => now()->addMinutes(self::ACCESS_TOKEN_TTL_MINUTES),
            'refresh_expires_at' => now()->addDays(self::REFRESH_TOKEN_TTL_DAYS),
            'revoked' => false,
            'created_at' => now(),
        ]);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => self::ACCESS_TOKEN_TTL_MINUTES * 60,
            'token_type' => 'Bearer',
            'scope' => implode(' ', $scopes),
        ];
    }

    private function verifyPkce(string $verifier, string $challenge): bool
    {
        if (strlen($verifier) < 43 || strlen($verifier) > 128) {
            return false;
        }
        $computed = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        return hash_equals($challenge, $computed);
    }

    private function extractAuthorizeParams(Request $request): array
    {
        return [
            'response_type' => $request->query('response_type'),
            'client_id' => $request->query('client_id'),
            'redirect_uri' => $request->query('redirect_uri'),
            'state' => $request->query('state'),
            'code_challenge' => $request->query('code_challenge'),
            'code_challenge_method' => $request->query('code_challenge_method', 'S256'),
            'scope' => $request->query('scope', 'mcp'),
        ];
    }

    private function requireHttps(): void
    {
        abort_if(!request()->secure() && app()->environment('production'), 400, 'HTTPS required.');
    }
}
