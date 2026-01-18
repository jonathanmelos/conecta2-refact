@extends('layouts.conecta')

@section('content')
<div class="row">
    <div class="col-12">
        <h2 class="mb-4">Listado de Clientes</h2>

        {{-- Buscador en tiempo real --}}
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <label for="searchInput" class="form-label">Buscar cliente:</label>
                        <input type="text"
                               class="form-control"
                               id="searchInput"
                               placeholder="Buscar por documento, nombre o apellido..."
                               autofocus>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabla de clientes (solo lectura) --}}
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="clientesTable">
                        <thead class="table-light">
                            <tr>
                                <th>Documento</th>
                                <th>Nombre</th>
                                <th>Apellido</th>
                                <th>Teléfono</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($clients as $client)
                                @php
                                    $tienePlan = $client->subscription_status === 'active' && $client->currentSubscription;
                                @endphp
                                <tr class="client-row {{ !$tienePlan ? 'table-warning' : '' }}"
                                    data-documento="{{ strtolower($client->document_number) }}"
                                    data-nombre="{{ strtolower($client->first_name) }}"
                                    data-apellido="{{ strtolower($client->last_name ?? '') }}">
                                    <td>
                                        <strong>{{ $client->document_number }}</strong>
                                    </td>
                                    <td>
                                        {{ $client->first_name }}
                                        @include('components.badges.invitado', ['client' => $client])
                                    </td>
                                    <td>{{ $client->last_name ?? '-' }}</td>
                                    <td>{{ $client->phone ?? '-' }}</td>
                                    <td>
                                        @if($tienePlan)
                                            <span class="badge bg-success">
                                                {{ $client->currentSubscription->plan->name ?? 'Plan Activo' }}
                                            </span>
                                        @else
                                            <span class="badge bg-warning text-dark">Sin Plan</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        No hay clientes registrados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Paginación --}}
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        <span class="text-muted" id="resultCount">
                            Mostrando {{ $clients->count() }} de {{ $clients->total() }} clientes
                        </span>
                    </div>
                    <div>
                        {{ $clients->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const rows = document.querySelectorAll('.client-row');

    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        let visibleCount = 0;

        rows.forEach(function(row) {
            const documento = row.dataset.documento;
            const nombre = row.dataset.nombre;
            const apellido = row.dataset.apellido;

            const matchesSearch = searchTerm === '' ||
                documento.includes(searchTerm) ||
                nombre.includes(searchTerm) ||
                apellido.includes(searchTerm);

            if (matchesSearch) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        document.getElementById('resultCount').textContent =
            'Mostrando ' + visibleCount + ' de {{ $clients->total() }} clientes';
    }

    searchInput.addEventListener('input', filterTable);
});
</script>
@endpush
