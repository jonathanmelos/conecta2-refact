@extends('layouts.conecta')

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
            <div class="col-md-2">
                <div class="card text-center">
                    <div class="card-body">
                        <h2 class="text-primary mb-0">{{ $stats['total_cowork'] }}</h2>
                        <p class="mb-0">Registros Cowork</p>
                        <small class="text-muted">{{ $stats['cowork_activos'] }} en uso</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
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
            <div class="col-md-2">
                <div class="card text-center">
                    <div class="card-body">
                        <h2 class="text-primary mb-0">{{ $stats['total_impresiones'] }}</h2>
                        <p class="mb-0">Impresiones</p>
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
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
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
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            No hay registros de sala para esta fecha
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Tabla de Impresiones del Día --}}
        <h4 class="mb-3">
            <img src="{{ asset('images/impresion.png') }}" width="20" height="20" alt="Impresiones">
            Impresiones del Día
        </h4>
        
        <div class="table-responsive mb-5">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Teléfono</th>
                        <th>Servicio</th>
                        <th>Hora</th>
                        <th class="text-center">Cantidad</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($registrosImpresiones as $item)
                    <tr>
                        <td>
                            <strong>{{ $item['client']->full_name }}</strong>
                            @if($item['client']->currentSubscription)
                                <br><small class="text-muted">{{ $item['client']->currentSubscription->plan->name }}</small>
                            @endif
                        </td>
                        <td>{{ $item['client']->phone }}</td>
                        <td>
                            @if($item['service_type'] === 'Cowork')
                                <span class="badge bg-primary">Cowork</span>
                            @elseif($item['service_type'] === 'Sala de Reuniones')
                                <span class="badge bg-info">Sala de Reuniones</span>
                            @else
                                <span class="badge bg-secondary">Ocasional</span>
                            @endif
                        </td>
                        <td>{{ $item['time']->format('H:i') }}</td>
                        <td class="text-center">
                            <strong class="text-primary">{{ $item['prints'] }}</strong>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            No hay impresiones registradas para esta fecha
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Auto-refresh cada 5 minutos para datos en tiempo real
setTimeout(function() {
    location.reload();
}, 300000);
</script>
@endpush