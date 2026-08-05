<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'admin' => \App\Http\Middleware\AdminMiddleware::class,
        'regular' => \App\Http\Middleware\RegularMiddleware::class,
        'api_token' => \App\Http\Middleware\ApiTokenMiddleware::class,
        'mcp.auth' => \App\Http\Middleware\McpAuth::class,
    ]);

    // These three are called directly by the MCP client (Claude.ai), not
    // from a browser session with a Blade-rendered CSRF token — they're
    // protected by their own logic instead (PKCE, token hash lookups).
    // oauth/mcp/authorize (the consent screen POST) is intentionally left
    // CSRF-protected: that one *does* originate from our own Blade form
    // inside an authenticated session, so the normal protection applies.
    $middleware->validateCsrfTokens(except: [
        'oauth/mcp/register',
        'oauth/mcp/token',
        'oauth/mcp/revoke',
    ]);
})
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
