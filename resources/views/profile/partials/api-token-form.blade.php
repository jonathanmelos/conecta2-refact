<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('API Analytics') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Genera un token para conectar Power BI o IA y elegir datasets de clientes, planes, suscripciones y registros.') }}
        </p>
    </header>

    <div class="mt-6 space-y-4">
        @if (session('api_token_plain'))
            <div class="rounded-md bg-green-50 p-4 text-sm text-green-800">
                <div class="font-semibold">Token generado. Guardalo ahora:</div>
                <div class="mt-2 break-all">{{ session('api_token_plain') }}</div>
            </div>
        @endif

        @if (session('api_token_revoked'))
            <div class="rounded-md bg-yellow-50 p-4 text-sm text-yellow-800">
                Token revocado.
            </div>
        @endif

        <div class="rounded-md bg-gray-50 p-4 text-sm text-gray-700">
            <div class="font-semibold mb-2">Endpoints disponibles</div>
            <div class="space-y-1">
                <div><code>/api/analytics/clients</code></div>
                <div><code>/api/analytics/plans</code></div>
                <div><code>/api/analytics/subscriptions</code></div>
                <div><code>/api/analytics/usage-records</code></div>
            </div>
            <div class="mt-3">
                Header: <code>X-API-KEY: TU_TOKEN</code>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <form method="POST" action="{{ route('profile.api-token') }}">
                @csrf
                <x-primary-button>
                    {{ __('Generar token') }}
                </x-primary-button>
            </form>

            @if ($user->api_token_hash)
                <form method="POST" action="{{ route('profile.api-token.revoke') }}">
                    @csrf
                    @method('DELETE')
                    <x-danger-button>
                        {{ __('Revocar token') }}
                    </x-danger-button>
                </form>
            @endif
        </div>
    </div>
</section>
