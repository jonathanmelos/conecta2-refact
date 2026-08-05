<section>
    <header class="mb-3">
        <h6 class="fw-bold mb-1"><i class="bi bi-diagram-3-fill me-1 text-primary"></i> {{ __('Conexión MCP') }}</h6>
        <p class="text-muted small mb-0">
            {{ __('Permite a Claude consultar datos del sistema (solo lectura) mediante una conexión segura OAuth.') }}
        </p>
    </header>

    @if ($user->role === 'admin')
        <div class="d-flex align-items-center justify-content-between bg-light rounded p-3 small mb-3">
            <span>{{ __('Conexiones activas de tu cuenta') }}</span>
            <span class="badge {{ $mcpConnectionsCount > 0 ? 'bg-success' : 'bg-secondary' }}">
                {{ $mcpConnectionsCount }}
            </span>
        </div>

        <a href="{{ route('admin.mcp.permissions') }}" class="btn btn-sm btn-primary w-100">
            {{ __('Ir a MCP Connector') }} <i class="bi bi-arrow-right ms-1"></i>
        </a>
    @else
        <div class="alert alert-secondary py-2 small mb-0">
            {{ __('Solo los usuarios administradores pueden configurar y usar la conexión MCP.') }}
        </div>
    @endif
</section>
