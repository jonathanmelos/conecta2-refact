@extends('layouts.conecta')

@section('content')
@php
    $formatMinutes = function ($minutes) {
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;
        return sprintf('%02d:%02d', $hours, $mins);
    };
@endphp
<div class="row">
    <div class="col-12">
        <form method="GET" action="{{ route('admin.reportes.periodo') }}" class="mb-4">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label for="fecha_i" class="form-label">Desde</label>
                    <input type="date" class="form-control" id="fecha_i" name="fecha_i" value="{{ $fechaInicio }}">
                </div>
                <div class="col-md-3">
                    <label for="fecha" class="form-label">Hasta</label>
                    <input type="date" class="form-control" id="fecha" name="fecha" value="{{ $fechaFin }}">
                </div>
                <div class="col-md-3">
                    <label for="busqueda" class="form-label">Tipo</label>
                    <select class="form-select" id="busqueda" name="busqueda">
                        <option value="reg" {{ $busqueda === 'reg' ? 'selected' : '' }}>Registros</option>
                        <option value="plan" {{ $busqueda === 'plan' ? 'selected' : '' }}>Planes</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">Consultar</button>
                </div>
            </div>
        </form>

        @if($busqueda === 'reg')
            <h4 class="mb-3">Registros por fecha</h4>
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre y Apellido</th>
                                    <th>Fecha</th>
                                    <th>Entrada</th>
                                    <th>Salida</th>
                                    <th>Uso</th>
                                    <th>Servicio</th>
                                    <th>Impresion</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($registros as $registro)
                                    @php
                                        $cliente = $registro->client;
                                        $fecha = $registro->check_in ? $registro->check_in->format('d/m/Y') : '-';
                                        $entrada = $registro->check_in ? $registro->check_in->format('H:i') : '-';
                                        $salida = $registro->check_out ? $registro->check_out->format('H:i') : '-';
                                        $duracionMin = $registro->check_out
                                            ? $registro->check_in->diffInMinutes($registro->check_out)
                                            : null;
                                        $estadoLabel = $registro->status === 'completed' ? 'APROBADO' : 'POR APROBAR';
                                        $servicioLabel = match ($registro->service_type) {
                                            'meeting_room' => 'sala',
                                            'print' => 'impresion',
                                            default => $registro->service_type,
                                        };
                                    @endphp
                                    <tr>
                                        <td>
                                            {{ $cliente ? $cliente->full_name : 'Cliente' }}
                                            @if($cliente)
                                                @include('components.badges.invitado', ['client' => $cliente])
                                            @endif
                                            @if($cliente && $cliente->invitedBy)
                                            <br><small class="text-muted">Invitado por {{ $cliente->invitedBy->full_name }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $fecha }}</td>
                                        <td>{{ $entrada }}</td>
                                        <td>{{ $salida }}</td>
                                        <td>
                                            @if($duracionMin !== null && in_array($registro->service_type, ['cowork', 'meeting_room']))
                                                {{ $formatMinutes($duracionMin) }}
                                            @elseif($registro->status === 'in_progress')
                                                En curso
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $servicioLabel }}</td>
                                        <td>{{ $registro->quantity ?? 0 }}</td>
                                        <td>{{ $estadoLabel }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            No hay registros en el rango.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        @if($busqueda === 'plan')
            <h4 class="mb-3">Planes por fecha</h4>
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre y Apellido</th>
                                    <th>Fecha Inicio</th>
                                    <th>Fecha Concluye</th>
                                    <th>Plan</th>
                                    <th>Precio</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($planes as $subscription)
                                    <tr>
                                        <td>
                                            {{ $subscription->client?->full_name ?? 'Cliente' }}
                                            @if($subscription->client)
                                                @include('components.badges.invitado', ['client' => $subscription->client])
                                            @endif
                                        </td>
                                        <td>{{ $subscription->start_date->format('Y-m-d') }}</td>
                                        <td>{{ $subscription->end_date ? $subscription->end_date->format('Y-m-d') : 'Sin vencimiento' }}</td>
                                        <td>{{ $subscription->plan?->name ?? 'Plan' }}</td>
                                        <td>S/. {{ number_format($subscription->monthly_price ?? $subscription->plan?->price ?? 0, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            No hay planes en el rango.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
