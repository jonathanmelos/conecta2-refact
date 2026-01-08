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
        <h4 class="mb-3">Clientes Diario</h4>

        <form method="GET" action="{{ route('admin.reportes.fecha') }}" class="mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label for="fecha" class="form-label">Dia</label>
                    <input type="date" class="form-control" id="fecha" name="fecha" value="{{ $fecha }}">
                </div>
                <div class="col-md-4">
                    <input type="hidden" name="estado" value="{{ $estado }}">
                    <button type="submit" class="btn btn-primary">Consultar</button>
                </div>
            </div>
        </form>

        <div class="d-flex flex-wrap gap-2 mb-3">
            <a href="{{ route('admin.reportes.fecha', ['estado' => '%', 'fecha' => $fecha]) }}" class="btn btn-outline-info">TODOS</a>
            <a href="{{ route('admin.reportes.fecha', ['estado' => 'A', 'fecha' => $fecha]) }}" class="btn btn-outline-success">APROBADOS</a>
            <a href="{{ route('admin.reportes.fecha', ['estado' => 'C', 'fecha' => $fecha]) }}" class="btn btn-outline-warning">POR APROBAR</a>
            <a href="{{ route('admin.reportes.fecha', ['estado' => 'E', 'fecha' => $fecha]) }}" class="btn btn-outline-danger">ELIMINADOS</a>
        </div>

        <div class="alert alert-secondary d-flex flex-wrap gap-3">
            <span><strong>Total Cowork:</strong> {{ $formatMinutes($totalCoworkMin) }}</span>
            <span><strong>Total Sala:</strong> {{ $formatMinutes($totalSalaMin) }}</span>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nombre y Apellido</th>
                                <th>Telefono</th>
                                <th>Entrada</th>
                                <th>Salida</th>
                                <th>Horas Usadas</th>
                                <th>Servicio</th>
                                <th>Impresion</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($registros as $registro)
                                @php
                                    $cliente = $registro->client;
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
                                        @if($cliente && $cliente->invitedBy)
                                        <br><small class="text-muted">Invitado por {{ $cliente->invitedBy->full_name }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $cliente?->phone ?? '-' }}</td>
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
                                        No hay registros para esta fecha.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
