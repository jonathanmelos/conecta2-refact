@extends('layouts.conecta')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Gestión de Clientes</h2>
            <a href="{{ route('admin.clientes.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Crear nuevo cliente
            </a>
        </div>

        {{-- Buscador que busca en toda la base de datos --}}
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.clientes.index') }}" id="searchForm">
                    <div class="row">
                        <div class="col-md-8">
                            <label for="searchInput" class="form-label">Buscar cliente:</label>
                            <input type="text"
                                   class="form-control"
                                   id="searchInput"
                                   name="search"
                                   value="{{ request('search') }}"
                                   placeholder="Buscar por documento, nombre o apellido...">
                        </div>
                        <div class="col-md-4 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Buscar
                            </button>
                            @if(request('search'))
                                <a href="{{ route('admin.clientes.index') }}" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-lg"></i> Limpiar
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Tabla de clientes --}}
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
                                <th>Link de invitación</th>
                                <th class="text-center" style="min-width: 280px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($clients as $client)
                                @php
                                    $tienePlan = $client->subscription_status === 'active' && $client->currentSubscription;
                                    $planVigente = false;
                                    $planVencido = false;

                                    if ($tienePlan && $client->currentSubscription) {
                                        $hoy = now();
                                        $inicio = \Carbon\Carbon::parse($client->currentSubscription->start_date);
                                        $fin = $client->currentSubscription->end_date
                                            ? \Carbon\Carbon::parse($client->currentSubscription->end_date)
                                            : null;
                                        $planVigente = $fin ? $hoy->between($inicio, $fin) : $hoy->gte($inicio);
                                        $planVencido = $fin ? $hoy->gt($fin) : false;
                                    }

                                    // Determinar clase de fila (solo amarillo para sin plan)
                                    $rowClass = '';
                                    if (!$tienePlan) {
                                        $rowClass = 'table-warning'; // Sin plan - amarillo
                                    }
                                @endphp
                                <tr class="{{ $rowClass }}">
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
                                            @if($planVigente)
                                                <span class="badge bg-success">
                                                    {{ $client->currentSubscription->plan->name ?? 'Plan Activo' }}
                                                    {{ $client->currentSubscription->plan && $client->currentSubscription->plan->is_pilot ? ' (Piloto)' : '' }}
                                                </span>
                                                <br><small class="text-success">
                                                    Vigente hasta {{ $client->currentSubscription->end_date ? \Carbon\Carbon::parse($client->currentSubscription->end_date)->format('d/m/Y') : 'Sin vencimiento' }}
                                                </small>
                                            @elseif($planVencido)
                                                <span class="badge bg-danger">
                                                    {{ $client->currentSubscription->plan->name ?? 'Plan' }}
                                                    {{ $client->currentSubscription->plan && $client->currentSubscription->plan->is_pilot ? ' (Piloto)' : '' }}
                                                </span>
                                                <br><small class="text-danger">
                                                    Vencido {{ $client->currentSubscription->end_date ? \Carbon\Carbon::parse($client->currentSubscription->end_date)->format('d/m/Y') : 'Sin vencimiento' }}
                                                </small>
                                            @else
                                                <span class="badge bg-info">
                                                    {{ $client->currentSubscription->plan->name ?? 'Plan' }}
                                                    {{ $client->currentSubscription->plan && $client->currentSubscription->plan->is_pilot ? ' (Piloto)' : '' }}
                                                </span>
                                                <br><small class="text-info">Inicia {{ \Carbon\Carbon::parse($client->currentSubscription->start_date)->format('d/m/Y') }}</small>
                                            @endif
                                            @if($client->invited_by_client_id)
                                                <br><small class="text-muted"><i class="bi bi-person-plus"></i> Invitado</small>
                                            @endif
                                        @else
                                            <span class="badge bg-warning text-dark">Sin Plan</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($client->invitation_link)
                                            <div class="d-flex gap-1 flex-wrap">
                                                <a href="{{ $client->invitation_link }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                                    Link
                                                </a>
                                                <a href="{{ $client->whatsapp_invitation_url }}" target="_blank" class="btn btn-outline-success btn-sm">
                                                    WhatsApp
                                                </a>
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if(!$tienePlan)
                                            {{-- Botón SUSCRIBIR (azul) --}}
                                            <a href="{{ route('admin.clientes.suscribirForm', $client) }}"
                                               class="btn btn-primary btn-sm">
                                                SUSCRIBIR
                                            </a>
                                        @elseif($planVencido)
                                            {{-- Botón RENOVAR (naranja) cuando plan vencido --}}
                                            <a href="{{ route('admin.clientes.plan', $client) }}"
                                               class="btn btn-outline-danger btn-sm">
                                                RENOVAR
                                            </a>
                                        @else
                                            {{-- Botón VER PLAN (verde) --}}
                                            <a href="{{ route('admin.clientes.plan', $client) }}"
                                               class="btn btn-success btn-sm">
                                                VER PLAN
                                            </a>
                                        @endif

                                        {{-- Botón Modificar (amarillo) --}}
                                        <a href="{{ route('admin.clientes.edit', $client) }}"
                                           class="btn btn-warning btn-sm">
                                            Modificar
                                        </a>

                                        {{-- Botón Eliminar (rojo) --}}
                                        <form action="{{ route('admin.clientes.destroy', $client) }}"
                                              method="POST"
                                              class="d-inline"
                                              onsubmit="return confirm('¿Está seguro de eliminar este cliente?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                Eliminar
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
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
                        <span class="text-muted">
                            Mostrando {{ $clients->firstItem() ?? 0 }} - {{ $clients->lastItem() ?? 0 }} de {{ $clients->total() }} clientes
                        </span>
                    </div>
                    <div>
                        {{ $clients->withQueryString()->links() }}
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
    const searchForm = document.getElementById('searchForm');
    let searchTimeout;

    // Búsqueda automática al escribir (con debounce de 500ms)
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            searchForm.submit();
        }, 500);
    });

    // Enfocar el input de búsqueda
    searchInput.focus();
    // Mover cursor al final del texto
    searchInput.setSelectionRange(searchInput.value.length, searchInput.value.length);
});
</script>
@endpush
