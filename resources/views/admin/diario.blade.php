@extends('layouts.conecta')

@php
    $showAlert = $registrosPendientes->count() > 0 || $registrosNoConcluidos->count() > 0;
@endphp

@section('content')
<div class="row">
    <div class="col-12">
        {{-- Buscador por fecha con navegación --}}
        <div class="card mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <form method="GET" action="{{ route('admin.diario') }}" class="d-flex align-items-center gap-2">
                            <label for="fecha" class="form-label mb-0 me-2">Día:</label>
                            
                            {{-- Botón día anterior --}}
                            <a href="{{ route('admin.diario', ['fecha' => $fechaCarbon->copy()->subDay()->format('Y-m-d')]) }}" 
                               class="btn btn-outline-secondary">
                                ◀ Anterior
                            </a>
                            
                            {{-- Input de fecha --}}
                            <input type="date" class="form-control" id="fecha" name="fecha" 
                                   value="{{ $fecha }}" 
                                   max="{{ date('Y-m-d') }}"
                                   onchange="this.form.submit()" 
                                   style="max-width: 180px;"
                                   required>
                            
                            {{-- Botón día siguiente --}}
                            @if($fecha < date('Y-m-d'))
                            <a href="{{ route('admin.diario', ['fecha' => $fechaCarbon->copy()->addDay()->format('Y-m-d')]) }}" 
                               class="btn btn-outline-secondary">
                                Siguiente ▶
                            </a>
                            @endif
                            
                            {{-- Botón HOY --}}
                            @if($fecha != date('Y-m-d'))
                            <a href="{{ route('admin.diario', ['fecha' => date('Y-m-d')]) }}" 
                               class="btn btn-primary">
                                Hoy
                            </a>
                            @endif
                        </form>
                    </div>
                    <div class="col-md-6 text-end">
                        <h5 class="mb-0">{{ $fechaCarbon->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}</h5>
                    </div>
                </div>
            </div>
        </div>

        {{-- Estadísticas del día --}}
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h2 class="text-primary mb-0">{{ $stats['total_cowork'] }}</h2>
                        <p class="mb-0">Registros Cowork</p>
                        <small class="text-muted">{{ $stats['cowork_activos'] }} en uso</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h2 class="text-info mb-0">{{ $stats['total_sala'] }}</h2>
                        <p class="mb-0">Registros Sala</p>
                        <small class="text-muted">{{ $stats['sala_activos'] }} en uso</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h2 class="text-success mb-0">{{ number_format($stats['horas_cowork'], 1) }}</h2>
                        <p class="mb-0">Horas Cowork</p>
                        <small class="text-muted">del día</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h2 class="text-warning mb-0">{{ number_format($stats['horas_sala'], 1) }}</h2>
                        <p class="mb-0">Horas Sala</p>
                        <small class="text-muted">del día</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Registros de Coworking --}}
        <h4 class="mb-3">
            <img src="{{ asset('images/entrada.png') }}" width="20" height="20" alt="Cowork">
            Clientes Cowork del Día
        </h4>
        
        <div class="table-responsive mb-5">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Teléfono</th>
                        <th>Entrada</th>
                        <th>Salida</th>
                        <th>Horas Usadas</th>
                        <th>Impresiones</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($registrosCowork as $registro)
                    <tr>
                        <td>
                            <strong>{{ $registro->client->full_name }}</strong>
                            @if($registro->client->currentSubscription)
                                <br><small class="text-muted">{{ $registro->client->currentSubscription->plan->name }}</small>
                            @endif
                        </td>
                        <td>{{ $registro->client->phone }}</td>
                        <td>
                            <img src="{{ asset('images/entrada.png') }}" width="20" height="20" alt="Entrada">
                            {{ $registro->check_in->format('H:i') }}
                        </td>
                        <td>
                            @if($registro->check_out)
                                <img src="{{ asset('images/salida.png') }}" width="20" height="20" alt="Salida">
                                {{ $registro->check_out->format('H:i') }}
                            @else
                                <span class="badge bg-warning text-dark">En cowork</span>
                            @endif
                        </td>
                        <td>
                            @if($registro->duration_in_hours)
                                <img src="{{ asset('images/total_horas.png') }}" width="20" height="20" alt="Horas">
                                {{ number_format($registro->duration_in_hours, 2) }} hrs
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-center">
                            {{ $registro->quantity }}
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                <button class="btn btn-warning" title="Modificar">
                                    Modificar
                                </button>
                                <button class="btn btn-danger" title="Eliminar">
                                    Eliminar
                                </button>
                                @if($registro->is_completed && !$registro->invoiced)
                                    <form method="POST" action="#" style="display: inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-success" title="Aprobar y contabilizar horas">
                                            Aprobar
                                        </button>
                                    </form>
                                @elseif($registro->invoiced)
                                    <button class="btn btn-secondary" disabled title="Ya aprobado">
                                        ✓ Aprobado
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            No hay registros de cowork para esta fecha
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Registros de Sala de Reuniones --}}
        <h4 class="mb-3">
            <img src="{{ asset('images/entrada.png') }}" width="20" height="20" alt="Sala">
            Sala de Reuniones del Día
        </h4>
        
        <div class="table-responsive mb-5">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Teléfono</th>
                        <th>Entrada</th>
                        <th>Salida</th>
                        <th>Horas Usadas</th>
                        <th>Impresiones</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($registrosSala as $registro)
                    <tr>
                        <td>
                            <strong>{{ $registro->client->full_name }}</strong>
                            @if($registro->client->currentSubscription)
                                <br><small class="text-muted">{{ $registro->client->currentSubscription->plan->name }}</small>
                            @endif
                        </td>
                        <td>{{ $registro->client->phone }}</td>
                        <td>
                            <img src="{{ asset('images/entrada.png') }}" width="20" height="20" alt="Entrada">
                            {{ $registro->check_in->format('H:i') }}
                        </td>
                        <td>
                            @if($registro->check_out)
                                <img src="{{ asset('images/salida.png') }}" width="20" height="20" alt="Salida">
                                {{ $registro->check_out->format('H:i') }}
                            @else
                                <span class="badge bg-warning text-dark">En sala</span>
                            @endif
                        </td>
                        <td>
                            @if($registro->duration_in_hours)
                                <img src="{{ asset('images/total_horas.png') }}" width="20" height="20" alt="Horas">
                                {{ number_format($registro->duration_in_hours, 2) }} hrs
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-center">
                            {{ $registro->quantity }}
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                <button class="btn btn-warning">
                                    Modificar
                                </button>
                                <button class="btn btn-danger">
                                    Eliminar
                                </button>
                                @if($registro->is_completed && !$registro->invoiced)
                                    <form method="POST" action="#" style="display: inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-success">
                                            Aprobar
                                        </button>
                                    </form>
                                @elseif($registro->invoiced)
                                    <button class="btn btn-secondary" disabled>
                                        ✓ Aprobado
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            No hay registros de sala para esta fecha
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('alert-content')
{{-- Alerta lateral con registros pendientes --}}
<h5 class="text-warning">⚠️ Registros Pendientes de Aprobación</h5>
<p class="small">Estos registros necesitan ser aprobados para contabilizar las horas:</p>
<table class="table table-sm">
    <thead>
        <tr>
            <th>Cliente</th>
            <th>Fecha</th>
            <th>Horas</th>
        </tr>
    </thead>
    <tbody>
        @foreach($registrosPendientes->take(8) as $pendiente)
        <tr>
            <td>{{ $pendiente->client->full_name }}</td>
            <td>{{ $pendiente->check_in->format('d/m') }}</td>
            <td>{{ number_format($pendiente->duration_in_hours ?? 0, 1) }}h</td>
        </tr>
        @endforeach
    </tbody>
</table>

@if($registrosPendientes->count() > 8)
    <p class="small text-muted mb-0">... y {{ $registrosPendientes->count() - 8 }} más</p>
@endif

<hr>

<h5 class="text-danger">🔴 Registros No Concluidos</h5>
<p class="small">Clientes que no han registrado su salida:</p>
<table class="table table-sm">
    <thead>
        <tr>
            <th>Cliente</th>
            <th>Servicio</th>
            <th>Entrada</th>
        </tr>
    </thead>
    <tbody>
        @foreach($registrosNoConcluidos->take(8) as $noConcluido)
        <tr>
            <td>{{ $noConcluido->client->full_name }}</td>
            <td>
                @if($noConcluido->service_type === 'cowork')
                    Cowork
                @else
                    Sala
                @endif
            </td>
            <td>{{ $noConcluido->check_in->format('d/m H:i') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

@if($registrosNoConcluidos->count() > 8)
    <p class="small text-muted mb-0">... y {{ $registrosNoConcluidos->count() - 8 }} más</p>
@endif
@endsection

@push('scripts')
<script>
// Auto-refresh cada 5 minutos para datos en tiempo real
setTimeout(function() {
    location.reload();
}, 300000);
</script>
@endpush