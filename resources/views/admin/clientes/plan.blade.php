@extends('layouts.conecta')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Gestión de Plan - {{ $client->full_name }}</h2>
            <a href="{{ route('admin.clientes.index') }}" class="btn btn-secondary">
                Volver al listado
            </a>
        </div>

        <div class="row">
            {{-- Columna izquierda: Datos del cliente y Plan Vigente --}}
            <div class="col-md-8">
                {{-- Datos del Cliente (solo lectura) --}}
                <div class="card mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">Datos del Cliente</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <strong>Documento:</strong><br>
                                {{ $client->document_number }}
                            </div>
                            <div class="col-md-4 mb-2">
                                <strong>Nombre:</strong><br>
                                {{ $client->first_name }}
                            </div>
                            <div class="col-md-4 mb-2">
                                <strong>Apellido:</strong><br>
                                {{ $client->last_name ?? '-' }}
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <strong>Correo:</strong><br>
                                {{ $client->email ?? '-' }}
                            </div>
                            <div class="col-md-4 mb-2">
                                <strong>Teléfono:</strong><br>
                                {{ $client->phone ?? '-' }}
                            </div>
                            <div class="col-md-4 mb-2">
                                <strong>Dirección:</strong><br>
                                {{ $client->address ?? '-' }}
                            </div>
                        </div>
                        <div class="mt-2">
                            <a href="{{ route('admin.clientes.edit', $client) }}" class="btn btn-sm btn-warning">
                                Editar datos del cliente
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Plan Vigente --}}
                @if($planVigente)
                    @php
                        $diasRestantes = now()->diffInDays($planVigente->end_date, false);
                        $colorBoton = $diasRestantes <= 5 ? 'btn-danger' : ($diasRestantes <= 10 ? 'btn-warning' : 'btn-success');
                    @endphp
                    <div class="card mb-4 border-success">
                        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Plan Vigente</h5>
                            <span class="badge bg-light text-dark">
                                {{ $diasRestantes }} días restantes
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <h4>{{ $planVigente->plan->name }}</h4>
                                    <p class="text-muted mb-1">
                                        <strong>Fecha de vencimiento:</strong><br>
                                        {{ $planVigente->end_date->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}
                                    </p>
                                    <p class="mb-0">
                                        <strong>Precio:</strong> ${{ number_format($planVigente->plan->price, 2) }}
                                    </p>
                                </div>
                                <div class="col-md-6 text-end">
                                    {{-- Botón Renovar --}}
                                    <button type="button" class="btn {{ $colorBoton }}" data-bs-toggle="modal" data-bs-target="#modalRenovar">
                                        Renovar Plan
                                    </button>
                                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalModificar">
                                        Modificar Plan
                                    </button>
                                    <a href="{{ route('admin.clientes.detalleRegistro', [$client, $planVigente]) }}" class="btn btn-outline-secondary">
                                        Detalle Registro
                                    </a>
                                </div>
                            </div>

                            <hr>

                            {{-- Consumo de recursos --}}
                            <h6>Consumo de Recursos</h6>
                            <div class="row">
                                {{-- Cowork --}}
                                <div class="col-md-6 mb-3">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title">Horas en COWORK</h6>
                                            @php
                                                $porcentajeCowork = $consumo['cowork']['contratadas'] > 0
                                                    ? ($consumo['cowork']['usadas'] / $consumo['cowork']['contratadas']) * 100
                                                    : 0;
                                                $colorCowork = $porcentajeCowork >= 90 ? 'bg-danger' : ($porcentajeCowork >= 70 ? 'bg-warning' : 'bg-success');
                                            @endphp
                                            <div class="progress mb-2" style="height: 20px;">
                                                <div class="progress-bar {{ $colorCowork }}"
                                                     role="progressbar"
                                                     style="width: {{ min($porcentajeCowork, 100) }}%"
                                                     aria-valuenow="{{ $porcentajeCowork }}"
                                                     aria-valuemin="0"
                                                     aria-valuemax="100">
                                                    {{ number_format($porcentajeCowork, 1) }}%
                                                </div>
                                            </div>
                                            <table class="table table-sm table-borderless mb-0">
                                                <tr>
                                                    <td>Contratadas:</td>
                                                    <td class="text-end"><strong>{{ $consumo['cowork']['contratadas'] }} hrs</strong></td>
                                                </tr>
                                                <tr>
                                                    <td>Usadas:</td>
                                                    <td class="text-end">{{ number_format($consumo['cowork']['usadas'], 2) }} hrs</td>
                                                </tr>
                                                <tr class="{{ $consumo['cowork']['restantes'] < 0 ? 'text-danger' : 'text-success' }}">
                                                    <td>Restantes:</td>
                                                    <td class="text-end"><strong>{{ number_format($consumo['cowork']['restantes'], 2) }} hrs</strong></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                {{-- Sala de Reuniones --}}
                                <div class="col-md-6 mb-3">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title">Horas en Sala Reuniones</h6>
                                            @php
                                                $porcentajeSala = $consumo['sala']['contratadas'] > 0
                                                    ? ($consumo['sala']['usadas'] / $consumo['sala']['contratadas']) * 100
                                                    : 0;
                                                $colorSala = $porcentajeSala >= 90 ? 'bg-danger' : ($porcentajeSala >= 70 ? 'bg-warning' : 'bg-success');
                                            @endphp
                                            <div class="progress mb-2" style="height: 20px;">
                                                <div class="progress-bar {{ $colorSala }}"
                                                     role="progressbar"
                                                     style="width: {{ min($porcentajeSala, 100) }}%"
                                                     aria-valuenow="{{ $porcentajeSala }}"
                                                     aria-valuemin="0"
                                                     aria-valuemax="100">
                                                    {{ number_format($porcentajeSala, 1) }}%
                                                </div>
                                            </div>
                                            <table class="table table-sm table-borderless mb-0">
                                                <tr>
                                                    <td>Contratadas:</td>
                                                    <td class="text-end"><strong>{{ $consumo['sala']['contratadas'] }} hrs</strong></td>
                                                </tr>
                                                <tr>
                                                    <td>Usadas:</td>
                                                    <td class="text-end">{{ number_format($consumo['sala']['usadas'], 2) }} hrs</td>
                                                </tr>
                                                <tr class="{{ $consumo['sala']['restantes'] < 0 ? 'text-danger' : 'text-success' }}">
                                                    <td>Restantes:</td>
                                                    <td class="text-end"><strong>{{ number_format($consumo['sala']['restantes'], 2) }} hrs</strong></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                {{-- Impresiones --}}
                                <div class="col-md-6 mb-3">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title">Impresiones</h6>
                                            @php
                                                $porcentajeImp = $consumo['impresiones']['contratadas'] > 0
                                                    ? ($consumo['impresiones']['usadas'] / $consumo['impresiones']['contratadas']) * 100
                                                    : 0;
                                                $colorImp = $porcentajeImp >= 90 ? 'bg-danger' : ($porcentajeImp >= 70 ? 'bg-warning' : 'bg-success');
                                            @endphp
                                            <div class="progress mb-2" style="height: 20px;">
                                                <div class="progress-bar {{ $colorImp }}"
                                                     role="progressbar"
                                                     style="width: {{ min($porcentajeImp, 100) }}%"
                                                     aria-valuenow="{{ $porcentajeImp }}"
                                                     aria-valuemin="0"
                                                     aria-valuemax="100">
                                                    {{ number_format($porcentajeImp, 1) }}%
                                                </div>
                                            </div>
                                            <table class="table table-sm table-borderless mb-0">
                                                <tr>
                                                    <td>Contratadas:</td>
                                                    <td class="text-end"><strong>{{ $consumo['impresiones']['contratadas'] }}</strong></td>
                                                </tr>
                                                <tr>
                                                    <td>Usadas:</td>
                                                    <td class="text-end">{{ $consumo['impresiones']['usadas'] }}</td>
                                                </tr>
                                                <tr class="{{ $consumo['impresiones']['restantes'] < 0 ? 'text-danger' : 'text-success' }}">
                                                    <td>Restantes:</td>
                                                    <td class="text-end"><strong>{{ $consumo['impresiones']['restantes'] }}</strong></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                {{-- Eventos --}}
                                <div class="col-md-6 mb-3">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title">Eventos Connecta</h6>
                                            <div class="display-6 text-center text-primary">
                                                {{ $consumo['eventos'] }}
                                            </div>
                                            <p class="text-center text-muted mb-0">eventos incluidos</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    {{-- Sin plan vigente --}}
                    <div class="card mb-4 border-warning">
                        <div class="card-header bg-warning">
                            <h5 class="mb-0">Sin Plan Vigente</h5>
                        </div>
                        <div class="card-body text-center py-4">
                            <p class="text-muted mb-3">Este cliente no tiene un plan activo actualmente.</p>
                            <a href="{{ route('admin.clientes.suscribirForm', $client) }}" class="btn btn-primary btn-lg">
                                Suscribir a un Plan
                            </a>
                        </div>
                    </div>
                @endif

                {{-- Plan Futuro (si existe) --}}
                @if($planFuturo)
                    <div class="card mb-4 border-info">
                        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Plan Futuro Programado</h5>
                            <span class="badge bg-light text-dark">
                                Inicia: {{ $planFuturo->start_date->format('d/m/Y') }}
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <h5>{{ $planFuturo->plan->name }}</h5>
                                    <p class="mb-1">
                                        <strong>Fecha inicio:</strong> {{ $planFuturo->start_date->format('d/m/Y') }}
                                    </p>
                                    <p class="mb-0">
                                        <strong>Fecha fin:</strong> {{ $planFuturo->end_date->format('d/m/Y') }}
                                    </p>
                                </div>
                                <div class="col-md-4 text-end">
                                    <form action="{{ route('admin.clientes.iniciarPlan', $client) }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="subscription_id" value="{{ $planFuturo->id }}">
                                        <button type="submit" class="btn btn-success"
                                                onclick="return confirm('¿Desea iniciar este plan ahora? La fecha de fin se recalculará.')">
                                            Iniciar Plan Ahora
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Columna derecha: Historial de Planes --}}
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">Historial de Planes</h5>
                    </div>
                    <div class="card-body p-0">
                        @if($historialPlanes->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>COD</th>
                                            <th>Plan</th>
                                            <th>Inicio</th>
                                            <th>Fin</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($historialPlanes as $subscription)
                                            @php
                                                $esVigente = $subscription->start_date <= now() && $subscription->end_date >= now();
                                                $esFuturo = $subscription->start_date > now();
                                            @endphp
                                            <tr class="{{ $esVigente ? 'table-success' : ($esFuturo ? 'table-info' : '') }}"
                                                style="cursor: pointer;"
                                                onclick="window.location='{{ route('admin.clientes.detalleRegistro', [$client, $subscription]) }}'">
                                                <td>{{ $subscription->id }}</td>
                                                <td>
                                                    {{ $subscription->plan->name ?? 'N/A' }}
                                                    @if($esVigente)
                                                        <span class="badge bg-success">Vigente</span>
                                                    @elseif($esFuturo)
                                                        <span class="badge bg-info">Futuro</span>
                                                    @endif
                                                </td>
                                                <td>{{ $subscription->start_date->format('d/m/Y') }}</td>
                                                <td>{{ $subscription->end_date->format('d/m/Y') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4 text-muted">
                                <p class="mb-0">No hay historial de planes.</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Clientes invitados (si los tiene) --}}
                @if($client->guests && $client->guests->count() > 0)
                    <div class="card mt-4">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0">Invitados Vinculados ({{ $client->guests->count() }})</h6>
                        </div>
                        <ul class="list-group list-group-flush">
                            @foreach($client->guests as $guest)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ $guest->full_name }}</strong><br>
                                        <small class="text-muted">{{ $guest->document_number }}</small>
                                    </div>
                                    <form action="{{ route('admin.clientes.unlinkGuest', $guest->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('¿Desvincular a {{ $guest->full_name }}?')">
                                            Desvincular
                                        </button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Modal Renovar Plan --}}
@if($planVigente)
<div class="modal fade" id="modalRenovar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.clientes.renovar', $client) }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Renovar Plan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Renovar plan para <strong>{{ $client->full_name }}</strong></p>
                    <p class="text-muted">El nuevo plan iniciará el <strong>{{ $planVigente->end_date->copy()->addDay()->format('d/m/Y') }}</strong></p>

                    <div class="mb-3">
                        <label for="renovar_plan_id" class="form-label">Seleccione el plan:</label>
                        <select class="form-select" name="plan_id" id="renovar_plan_id" required>
                            @foreach($plans as $plan)
                                <option value="{{ $plan->id }}"
                                        {{ $planVigente->plan_id == $plan->id ? 'selected' : '' }}>
                                    {{ $plan->name }} - ${{ number_format($plan->price, 2) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Renovar Plan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Modificar Plan --}}
<div class="modal fade" id="modalModificar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.clientes.modificarPlan', $client) }}" method="POST">
                @csrf
                <input type="hidden" name="subscription_id" value="{{ $planVigente->id }}">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Modificar Plan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Modificar plan de <strong>{{ $client->full_name }}</strong></p>

                    <div class="mb-3">
                        <label for="modificar_plan_id" class="form-label">Nuevo Plan:</label>
                        <select class="form-select" name="plan_id" id="modificar_plan_id" required>
                            @foreach($plans as $plan)
                                <option value="{{ $plan->id }}"
                                        {{ $planVigente->plan_id == $plan->id ? 'selected' : '' }}>
                                    {{ $plan->name }} - ${{ number_format($plan->price, 2) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="modificar_start_date" class="form-label">Nueva Fecha de Inicio:</label>
                        <input type="date" class="form-control" name="start_date" id="modificar_start_date"
                               value="{{ $planVigente->start_date->format('Y-m-d') }}" required>
                        <small class="text-muted">La fecha de fin se calculará automáticamente (+1 mes)</small>
                    </div>

                    <div class="alert alert-warning">
                        <strong>Atención:</strong> Modificar el plan puede afectar las horas disponibles.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
