@extends('layouts.conecta')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Gestión de Plan - {{ $client->full_name }} @include('components.badges.invitado', ['client' => $client])</h2>
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
                            @if($client->invitation_link)
                                <a href="{{ $client->invitation_link }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    Link de invitación
                                </a>
                                <a href="{{ $client->whatsapp_invitation_url }}" target="_blank" class="btn btn-sm btn-outline-success">
                                    Enviar por WhatsApp
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Plan Vigente --}}
                @if($planVigente)
                    @php
                        $endDate = $planVigente->end_date;
                        $diasRestantes = $endDate ? now()->diffInDays($endDate, false) : null;
                        $colorBoton = $diasRestantes === null
                            ? 'btn-success'
                            : ($diasRestantes <= 5 ? 'btn-danger' : ($diasRestantes <= 10 ? 'btn-warning' : 'btn-success'));
                    @endphp
                    <div class="card mb-4 border-success">
                        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Plan Vigente</h5>
                            <span class="badge bg-light text-dark">
                                {{ $diasRestantes === null ? 'Sin vencimiento' : (int) $diasRestantes . ' días restantes' }}
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <h4>
                                        {{ $planVigente->plan->name }}
                                        @if($planVigente->plan->is_pilot)
                                            <span class="badge bg-info text-dark ms-1">Piloto</span>
                                        @endif
                                        @if($planVigente->is_ultra_custom)
                                            <span class="badge bg-dark ms-1">Ultra personalizado</span>
                                        @endif
                                    </h4>
                                    <p class="text-muted mb-1">
                                        <strong>Fecha de vencimiento:</strong><br>
                                        {{ $endDate ? $endDate->locale('es')->isoFormat('D [de] MMMM [de] YYYY') : 'Sin vencimiento' }}
                                    </p>
                                    <p class="mb-0">
                                        <strong>Precio:</strong> ${{ number_format($planVigente->monthly_price, 2) }}
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
                                    @if($planVigente->is_ultra_custom)
                                        <button type="button" class="btn btn-outline-dark" data-bs-toggle="modal" data-bs-target="#modalCuposUltra">
                                            Editar Cupos Ultra
                                        </button>
                                    @endif
                                    @if($detalleRegistroSubscription)
                                        <a href="{{ route('admin.clientes.detalleRegistro', [$client, $detalleRegistroSubscription]) }}" class="btn btn-outline-secondary">
                                            Detalle Registro
                                        </a>
                                    @endif
                                    <form method="POST"
                                          action="{{ route('admin.clientes.recalcularHorasTracking', $client) }}"
                                          class="d-inline-block"
                                          onsubmit="return confirm('¿Recalcular horas usadas del plan actual? Esto reemplazará hours_tracking con los registros reales.')">
                                        @csrf
                                        <input type="hidden" name="subscription_id" value="{{ $planVigente->id }}">
                                        <button type="submit" class="btn btn-outline-warning">
                                            Recalcular Horas
                                        </button>
                                    </form>
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
                                                    {{ $planVigente->plan->is_pilot ? 'Sin limite' : number_format($porcentajeCowork, 1) . '%' }}
                                                </div>
                                            </div>
                                            <table class="table table-sm table-borderless mb-0">
                                                <tr>
                                                    <td>Contratadas:</td>
                                                    <td class="text-end">
                                                        <strong>{{ $planVigente->plan->is_pilot ? 'Ilimitadas' : $consumo['cowork']['contratadas'] . ' hrs' }}</strong>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>Usadas:</td>
                                                    <td class="text-end">{{ number_format($consumo['cowork']['usadas'], 2) }} hrs</td>
                                                </tr>
                                                <tr class="{{ $planVigente->plan->is_pilot ? 'text-success' : ($consumo['cowork']['restantes'] < 0 ? 'text-danger' : 'text-success') }}">
                                                    <td>Restantes:</td>
                                                    <td class="text-end">
                                                        <strong>{{ $planVigente->plan->is_pilot ? 'Ilimitadas' : number_format($consumo['cowork']['restantes'], 2) . ' hrs' }}</strong>
                                                    </td>
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
                                                    {{ $planVigente->plan->is_pilot ? 'Sin limite' : number_format($porcentajeSala, 1) . '%' }}
                                                </div>
                                            </div>
                                            <table class="table table-sm table-borderless mb-0">
                                                <tr>
                                                    <td>Contratadas:</td>
                                                    <td class="text-end">
                                                        <strong>{{ $planVigente->plan->is_pilot ? 'Ilimitadas' : $consumo['sala']['contratadas'] . ' hrs' }}</strong>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>Usadas:</td>
                                                    <td class="text-end">{{ number_format($consumo['sala']['usadas'], 2) }} hrs</td>
                                                </tr>
                                                <tr class="{{ $planVigente->plan->is_pilot ? 'text-success' : ($consumo['sala']['restantes'] < 0 ? 'text-danger' : 'text-success') }}">
                                                    <td>Restantes:</td>
                                                    <td class="text-end">
                                                        <strong>{{ $planVigente->plan->is_pilot ? 'Ilimitadas' : number_format($consumo['sala']['restantes'], 2) . ' hrs' }}</strong>
                                                    </td>
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

                {{-- Plan del anfitrion (si es invitado y no tiene plan propio) --}}
                @if(!$planVigente && $planInvitador)
                    <div class="card mb-4 border-success">
                        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Plan del anfitrion</h5>
                            <span class="badge bg-light text-dark">
                                {{ $planInvitador->end_date ? 'Vigente' : 'Sin vencimiento' }}
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <h4>
                                        {{ $planInvitador->plan->name ?? 'Plan' }}
                                        {{ $planInvitador->plan && $planInvitador->plan->is_pilot ? ' (Piloto)' : '' }}
                                    </h4>
                                    <p class="text-muted mb-1">
                                        <strong>Cliente anfitrion:</strong><br>
                                        {{ $client->invitedBy->full_name }}
                                    </p>
                                    <p class="text-muted mb-1">
                                        <strong>Vigencia:</strong><br>
                                        {{ $planInvitador->start_date->format('d/m/Y') }} -
                                        {{ $planInvitador->end_date ? $planInvitador->end_date->format('d/m/Y') : 'Sin vencimiento' }}
                                    </p>
                                </div>
                                <div class="col-md-6 text-end">
                                    @if($detalleRegistroSubscription)
                                        <a href="{{ route('admin.clientes.detalleRegistro', [$client, $detalleRegistroSubscription]) }}" class="btn btn-outline-secondary">
                                            Ver registros del invitado
                                        </a>
                                    @endif
                                </div>
                            </div>
                            <div class="alert alert-info mb-0">
                                Los registros de este invitado se contabilizan dentro del plan del anfitrion.
                            </div>
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
                                    <h5>
                                        {{ $planFuturo->plan->name }}
                                        {{ $planFuturo->plan && $planFuturo->plan->is_pilot ? ' (Piloto)' : '' }}
                                    </h5>
                                    <p class="mb-1">
                                        <strong>Fecha inicio:</strong> {{ $planFuturo->start_date->format('d/m/Y') }}
                                    </p>
                                    <p class="mb-0">
                                        <strong>Fecha fin:</strong> {{ $planFuturo->end_date ? $planFuturo->end_date->format('d/m/Y') : 'Sin vencimiento' }}
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
                                                $esVigente = $subscription->start_date <= now()
                                                    && ($subscription->end_date ? $subscription->end_date >= now() : true);
                                                $esFuturo = $subscription->start_date > now();
                                            @endphp
                                            <tr class="{{ $esVigente ? 'table-success' : ($esFuturo ? 'table-info' : '') }}"
                                                style="cursor: pointer;"
                                                onclick="window.location='{{ route('admin.clientes.detalleRegistro', [$client, $subscription]) }}'">
                                                <td>{{ $subscription->id }}</td>
                                                <td>
                                                    {{ $subscription->plan->name ?? 'N/A' }}
                                                    {{ $subscription->plan && $subscription->plan->is_pilot ? ' (Piloto)' : '' }}
                                                    @if($esVigente)
                                                        <span class="badge bg-success">Vigente</span>
                                                    @elseif($esFuturo)
                                                        <span class="badge bg-info">Futuro</span>
                                                    @endif
                                                </td>
                                                <td>{{ $subscription->start_date->format('d/m/Y') }}</td>
                                                <td>{{ $subscription->end_date ? $subscription->end_date->format('d/m/Y') : 'Sin vencimiento' }}</td>
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

                @if($assignableSubscriptions->count() > 0)
                    <div class="card mt-4">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0">Asignaciones por Plan</h6>
                        </div>
                        <div class="card-body">
                            <p class="small text-muted mb-3">
                                Use esta opcion solo cuando una persona deba consumir un plan especifico. Si no hay asignaciones, el sistema mantiene el comportamiento normal.
                            </p>

                            @foreach($assignableSubscriptions as $subscription)
                                @php
                                    $subscription->loadMissing('members.client');
                                    $isCurrentWindow = $subscription->start_date <= now()
                                        && ($subscription->end_date ? $subscription->end_date >= now() : true);
                                @endphp
                                <div class="border rounded p-2 mb-3 {{ $isCurrentWindow ? 'border-success' : '' }}">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <strong>#{{ $subscription->id }} - {{ $subscription->plan->name ?? 'Plan' }}</strong>
                                            @if($isCurrentWindow)
                                                <span class="badge bg-success">Vigente</span>
                                            @endif
                                            <br>
                                            <small class="text-muted">
                                                {{ $subscription->start_date->format('d/m/Y') }} -
                                                {{ $subscription->end_date ? $subscription->end_date->format('d/m/Y') : 'Sin vencimiento' }}
                                            </small>
                                        </div>
                                    </div>

                                    @if($subscription->members->count() > 0)
                                        <div class="list-group list-group-flush mb-2">
                                            @foreach($subscription->members as $member)
                                                <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <strong>{{ $member->client->full_name ?? 'Cliente' }}</strong>
                                                        @if($member->is_default_cowork)
                                                            <span class="badge bg-primary">Cowork preferido</span>
                                                        @endif
                                                        @if($member->is_default_meeting_room)
                                                            <span class="badge bg-info">Sala preferida</span>
                                                        @endif
                                                        <br>
                                                        <small class="text-muted">
                                                            {{ $member->can_use_cowork ? 'Cowork' : '' }}
                                                            {{ $member->can_use_cowork && $member->can_use_meeting_room ? ' / ' : '' }}
                                                            {{ $member->can_use_meeting_room ? 'Sala' : '' }}
                                                        </small>
                                                    </div>
                                                    <form method="POST"
                                                          action="{{ route('admin.clientes.subscriptionMembers.destroy', [$client, $subscription, $member]) }}"
                                                          onsubmit="return confirm('¿Eliminar esta asignacion?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            Quitar
                                                        </button>
                                                    </form>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="small text-muted mb-2">Sin personas asignadas directamente a este plan.</div>
                                    @endif

                                    <form method="POST" action="{{ route('admin.clientes.subscriptionMembers.store', [$client, $subscription]) }}">
                                        @csrf
                                        <div class="mb-2">
                                            <select name="client_id" class="form-select form-select-sm" required>
                                                @foreach($assignableClients as $assignableClient)
                                                    <option value="{{ $assignableClient->id }}">
                                                        {{ $assignableClient->full_name }} - {{ $assignableClient->document_number }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="d-flex flex-wrap gap-2 align-items-center">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="can_use_cowork" value="1" id="cowork_{{ $subscription->id }}" checked>
                                                <label class="form-check-label small" for="cowork_{{ $subscription->id }}">Cowork</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="can_use_meeting_room" value="1" id="sala_{{ $subscription->id }}" checked>
                                                <label class="form-check-label small" for="sala_{{ $subscription->id }}">Sala</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="is_default" value="1" id="default_{{ $subscription->id }}">
                                                <label class="form-check-label small" for="default_{{ $subscription->id }}">Preferido para servicios seleccionados</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="is_default_cowork" value="1" id="default_cowork_{{ $subscription->id }}">
                                                <label class="form-check-label small" for="default_cowork_{{ $subscription->id }}">Preferido cowork</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="is_default_meeting_room" value="1" id="default_sala_{{ $subscription->id }}">
                                                <label class="form-check-label small" for="default_sala_{{ $subscription->id }}">Preferido sala</label>
                                            </div>
                                            <button type="submit" class="btn btn-sm btn-outline-primary ms-auto">
                                                Asignar
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

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
                                        <strong>{{ $guest->full_name }}</strong>
                                        @include('components.badges.invitado', ['client' => $guest])
                                        <br>
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
                    <p class="text-muted">
                        El nuevo plan iniciará el
                        <strong>{{ $planVigente->end_date ? $planVigente->end_date->copy()->addDay()->format('d/m/Y') : now()->format('d/m/Y') }}</strong>
                    </p>

                    <div class="mb-3">
                        <label for="renovar_plan_id" class="form-label">Seleccione el plan:</label>
                        <select class="form-select" name="plan_id" id="renovar_plan_id" required>
                            @foreach($plans as $plan)
                                <option value="{{ $plan->id }}"
                                        {{ $planVigente->plan_id == $plan->id ? 'selected' : '' }}>
                                    {{ $plan->name }}{{ $plan->is_pilot ? ' (Piloto)' : '' }} - ${{ number_format($plan->price, 2) }}
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
                                    {{ $plan->name }}{{ $plan->is_pilot ? ' (Piloto)' : '' }} - ${{ number_format($plan->price, 2) }}
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

@if($planVigente->is_ultra_custom)
<div class="modal fade" id="modalCuposUltra" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.clientes.actualizarCuposUltra', $client) }}" method="POST">
                @csrf
                <input type="hidden" name="subscription_id" value="{{ $planVigente->id }}">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">Editar Cupos Ultra Personalizados</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">
                        Personaliza los cupos contratados para <strong>{{ $client->full_name }}</strong> en esta suscripción.
                    </p>

                    <div class="mb-3">
                        <label for="custom_cowork_hours" class="form-label">Horas en COWORK (Contratadas)</label>
                        <input type="number" step="0.01" min="0" class="form-control"
                               id="custom_cowork_hours" name="custom_cowork_hours"
                               value="{{ old('custom_cowork_hours', $planVigente->effective_cowork_hours) }}" required>
                    </div>

                    <div class="mb-3">
                        <label for="custom_meeting_room_hours" class="form-label">Horas en Sala Reuniones (Contratadas)</label>
                        <input type="number" step="0.01" min="0" class="form-control"
                               id="custom_meeting_room_hours" name="custom_meeting_room_hours"
                               value="{{ old('custom_meeting_room_hours', $planVigente->effective_meeting_room_hours) }}" required>
                    </div>

                    <div class="mb-3">
                        <label for="custom_prints_included" class="form-label">Impresiones (Contratadas)</label>
                        <input type="number" min="0" class="form-control"
                               id="custom_prints_included" name="custom_prints_included"
                               value="{{ old('custom_prints_included', $planVigente->effective_prints_included) }}" required>
                    </div>

                    <div class="mb-1">
                        <label for="custom_events_included" class="form-label">Eventos Connecta (Contratados)</label>
                        <input type="number" min="0" class="form-control"
                               id="custom_events_included" name="custom_events_included"
                               value="{{ old('custom_events_included', $planVigente->effective_events_included) }}" required>
                    </div>

                    <div class="mb-1 mt-3">
                        <label for="custom_monthly_price" class="form-label">Precio Personalizado</label>
                        <input type="number" min="0" step="0.01" class="form-control"
                               id="custom_monthly_price" name="custom_monthly_price"
                               value="{{ old('custom_monthly_price', $planVigente->monthly_price) }}" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-dark">Guardar Cupos</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endif
@endsection
