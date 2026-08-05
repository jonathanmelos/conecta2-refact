<section>
    <header class="mb-3">
        <h6 class="fw-bold mb-1"><i class="bi bi-key-fill me-1 text-primary"></i> {{ __('API Analytics') }}</h6>
        <p class="text-muted small mb-0">
            {{ __('Genera un token para conectar Power BI o IA y elegir datasets de clientes, planes, suscripciones y registros.') }}
        </p>
    </header>

    <div class="mt-3">
        @if (session('api_token_plain'))
            <div class="alert alert-success py-2 small">
                <div class="fw-semibold">{{ __('Token generado. Guárdalo ahora:') }}</div>
                <div class="mt-1 text-break"><code>{{ session('api_token_plain') }}</code></div>
            </div>
        @endif

        @if (session('api_token_revoked'))
            <div class="alert alert-warning py-2 small">
                {{ __('Token revocado.') }}
            </div>
        @endif

        <div class="bg-light rounded p-3 small mb-3">
            <div class="fw-semibold mb-2">{{ __('Endpoints disponibles') }}</div>
            <div class="d-flex flex-column gap-1">
                <code>/api/analytics/clients</code>
                <code>/api/analytics/plans</code>
                <code>/api/analytics/subscriptions</code>
                <code>/api/analytics/usage-records</code>
            </div>
            <div class="mt-2">
                {{ __('Header:') }} <code>X-API-KEY: TU_TOKEN</code>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <form method="POST" action="{{ route('profile.api-token') }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-primary">{{ __('Generar token') }}</button>
            </form>

            @if ($user->api_token_hash)
                <form method="POST" action="{{ route('profile.api-token.revoke') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Revocar token') }}</button>
                </form>
            @endif
        </div>
    </div>
</section>
