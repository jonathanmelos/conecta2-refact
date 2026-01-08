@extends('layouts.conecta')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>Detalle de Registro #{{ $subscription->id }}</h2>
                <p class="text-muted mb-0">
                    {{ $client->full_name }} - {{ $subscription->plan->name ?? 'Plan' }}
                    ({{ $subscription->start_date->format('d/m/Y') }} - {{ $subscription->end_date->format('d/m/Y') }})
                </p>
            </div>
            <div class="d-flex">
                <a href="{{ route('admin.clientes.detalleRegistroExcel', [$client, $subscription]) }}" class="btn btn-success me-2">
                    Descargar Excel
                </a>
                <a href="{{ route('admin.clientes.detalleRegistroPdf', [$client, $subscription]) }}" class="btn btn-danger me-2">
                    Descargar PDF
                </a>
                <a href="{{ route('admin.clientes.plan', $client) }}" class="btn btn-secondary">
                    Volver a Planes de Cliente
                </a>
            </div>
        </div>

        {{-- Resumen de Contadores con efecto vaso --}}
        @php
            // Calcular porcentajes de uso
            $porcentajeCowork = $horasCoworkContratadas > 0
                ? min(100, ($horasCoworkUsadas / $horasCoworkContratadas) * 100)
                : 0;
            $porcentajeSala = $horasSalaContratadas > 0
                ? min(100, ($horasSalaUsadas / $horasSalaContratadas) * 100)
                : 0;
            $porcentajeImpresiones = $impresionesContratadas > 0
                ? min(100, ($impresionesUsadas / $impresionesContratadas) * 100)
                : 0;
        @endphp
        <style>
            .glass-container {
                height: 120px;
                overflow: hidden;
            }
            .glass-liquid {
                position: absolute;
                bottom: 0;
                width: 100%;
                transition: height 0.5s ease;
            }
            .glass-text {
                position: absolute;
                width: 100%;
                height: 100%;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                z-index: 2;
            }
            /* Texto con sombra para mejor legibilidad */
            .glass-text-shadow-light {
                text-shadow: 0 0 8px rgba(255,255,255,0.9), 0 0 4px rgba(255,255,255,1);
            }
            .glass-text-shadow-dark {
                text-shadow: 0 0 8px rgba(0,0,0,0.5), 0 0 4px rgba(0,0,0,0.3);
            }
        </style>
        <div class="row mb-4">
            {{-- Cowork --}}
            <div class="col-md-4">
                <div class="card border-primary glass-container">
                    <div class="card-body text-center p-0 position-relative h-100">
                        {{-- Fondo claro --}}
                        <div class="position-absolute w-100 h-100" style="background: linear-gradient(to bottom, #e3f2fd 0%, #bbdefb 100%);"></div>
                        {{-- Liquido azul --}}
                        <div class="glass-liquid bg-primary" style="height: {{ $porcentajeCowork }}%;"></div>
                        {{-- Contenido con texto adaptativo --}}
                        <div class="glass-text {{ $porcentajeCowork > 60 ? 'text-white glass-text-shadow-dark' : 'text-primary glass-text-shadow-light' }}">
                            <h3 class="mb-0 fw-bold">{{ number_format($horasCoworkUsadas, 1) }}h</h3>
                            <small class="fw-bold">de {{ number_format($horasCoworkContratadas, 1) }}h Cowork</small>
                            <small class="mt-1">({{ number_format($porcentajeCowork, 0) }}% usado)</small>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Sala --}}
            <div class="col-md-4">
                <div class="card border-success glass-container">
                    <div class="card-body text-center p-0 position-relative h-100">
                        {{-- Fondo claro --}}
                        <div class="position-absolute w-100 h-100" style="background: linear-gradient(to bottom, #e8f5e9 0%, #c8e6c9 100%);"></div>
                        {{-- Liquido verde --}}
                        <div class="glass-liquid bg-success" style="height: {{ $porcentajeSala }}%;"></div>
                        {{-- Contenido con texto adaptativo --}}
                        <div class="glass-text {{ $porcentajeSala > 60 ? 'text-white glass-text-shadow-dark' : 'text-success glass-text-shadow-light' }}">
                            <h3 class="mb-0 fw-bold">{{ number_format($horasSalaUsadas, 1) }}h</h3>
                            <small class="fw-bold">de {{ number_format($horasSalaContratadas, 1) }}h Sala</small>
                            <small class="mt-1">({{ number_format($porcentajeSala, 0) }}% usado)</small>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Impresiones --}}
            <div class="col-md-4">
                <div class="card border-warning glass-container">
                    <div class="card-body text-center p-0 position-relative h-100">
                        {{-- Fondo claro --}}
                        <div class="position-absolute w-100 h-100" style="background: linear-gradient(to bottom, #fff8e1 0%, #ffecb3 100%);"></div>
                        {{-- Liquido amarillo --}}
                        <div class="glass-liquid bg-warning" style="height: {{ $porcentajeImpresiones }}%;"></div>
                        {{-- Contenido con texto adaptativo --}}
                        <div class="glass-text {{ $porcentajeImpresiones > 60 ? 'text-dark glass-text-shadow-light' : 'glass-text-shadow-light' }}" style="{{ $porcentajeImpresiones <= 60 ? 'color: #b57c00;' : '' }}">
                            <h3 class="mb-0 fw-bold">{{ $impresionesUsadas }}</h3>
                            <small class="fw-bold">de {{ $impresionesContratadas }} Impresiones</small>
                            <small class="mt-1">({{ number_format($porcentajeImpresiones, 0) }}% usado)</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Resumen Detallado del Plan --}}
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">Resumen del Plan: {{ $subscription->plan->name ?? 'Plan' }}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    {{-- Horas en COWORK --}}
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <h6 class="text-primary fw-bold mb-3">
                                <i class="bi bi-laptop"></i> Horas en COWORK
                            </h6>
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td>Contratadas:</td>
                                    <td class="text-end fw-bold">{{ sprintf('%d:%02d', floor($horasCoworkContratadas), ($horasCoworkContratadas - floor($horasCoworkContratadas)) * 60) }}</td>
                                </tr>
                                <tr>
                                    <td>Usadas:</td>
                                    <td class="text-end fw-bold text-danger">{{ sprintf('%d:%02d', floor($horasCoworkUsadas), ($horasCoworkUsadas - floor($horasCoworkUsadas)) * 60) }}</td>
                                </tr>
                                <tr class="border-top">
                                    <td>Restantes:</td>
                                    <td class="text-end fw-bold text-success">{{ sprintf('%d:%02d', floor($horasCoworkRestantes), ($horasCoworkRestantes - floor($horasCoworkRestantes)) * 60) }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    {{-- Horas en Sala Reuniones --}}
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <h6 class="text-success fw-bold mb-3">
                                <i class="bi bi-people"></i> Horas en Sala Reuniones
                            </h6>
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td>Contratadas:</td>
                                    <td class="text-end fw-bold">{{ sprintf('%d:%02d', floor($horasSalaContratadas), ($horasSalaContratadas - floor($horasSalaContratadas)) * 60) }}</td>
                                </tr>
                                <tr>
                                    <td>Usadas:</td>
                                    <td class="text-end fw-bold text-danger">{{ sprintf('%d:%02d', floor($horasSalaUsadas), ($horasSalaUsadas - floor($horasSalaUsadas)) * 60) }}</td>
                                </tr>
                                <tr class="border-top">
                                    <td>Restantes:</td>
                                    <td class="text-end fw-bold text-success">{{ sprintf('%d:%02d', floor($horasSalaRestantes), ($horasSalaRestantes - floor($horasSalaRestantes)) * 60) }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    {{-- Cantidad Impresiones --}}
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <h6 class="text-warning fw-bold mb-3">
                                <i class="bi bi-printer"></i> Cantidad Impresiones
                            </h6>
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td>Contratadas:</td>
                                    <td class="text-end fw-bold">{{ $impresionesContratadas }}</td>
                                </tr>
                                <tr>
                                    <td>Usadas:</td>
                                    <td class="text-end fw-bold text-danger">{{ $impresionesUsadas }}</td>
                                </tr>
                                <tr class="border-top">
                                    <td>Restantes:</td>
                                    <td class="text-end fw-bold text-success">{{ $impresionesRestantes }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                {{-- Precio del Plan --}}
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="bg-light rounded p-2 text-end">
                            <span class="text-muted">Precio del Plan:</span>
                            <strong class="ms-2">S/. {{ number_format($subscription->monthly_price ?? $subscription->plan->price ?? 0, 2) }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabla de registros --}}
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Registros de Uso</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Cliente</th>
                                <th>Fecha</th>
                                <th>Servicio</th>
                                <th>Entrada</th>
                                <th>Salida</th>
                                <th>Duracion</th>
                                <th>Impresiones</th>
                                <th>Estado</th>
                                <th class="text-center">Accion</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($registros as $registro)
                                <tr>
                                    <td>
                                        @if($registro->client)
                                            {{ $registro->client->full_name }}
                                            <small class="text-muted">({{ $registro->id }})</small>
                                            @if($registro->client_id != $client->id)
                                                <br><span class="badge bg-secondary">Invitado</span>
                                            @endif
                                        @else
                                            {{ $client->full_name }}
                                            <small class="text-muted">({{ $registro->id }})</small>
                                        @endif
                                    </td>
                                    <td>{{ $registro->check_in->format('d/m/Y') }}</td>
                                    <td>
                                        @switch($registro->service_type)
                                            @case('cowork')
                                                <span class="badge bg-primary">Cowork</span>
                                                @break
                                            @case('meeting_room')
                                                <span class="badge bg-success">Sala Reuniones</span>
                                                @break
                                            @case('print')
                                                <span class="badge bg-warning text-dark">Impresion</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary">{{ $registro->service_type }}</span>
                                        @endswitch
                                    </td>
                                    <td>
                                        @if($registro->service_type !== 'print')
                                            {{ $registro->check_in->format('H:i') }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($registro->service_type !== 'print' && $registro->check_out)
                                            {{ $registro->check_out->format('H:i') }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($registro->check_out && in_array($registro->service_type, ['cowork', 'meeting_room']))
                                            @php
                                                $duracion = $registro->check_in->diffInMinutes($registro->check_out);
                                                $horas = floor($duracion / 60);
                                                $minutos = $duracion % 60;
                                            @endphp
                                            {{ sprintf('%02d:%02d', $horas, $minutos) }}
                                        @elseif($registro->status === 'in_progress' && $registro->service_type !== 'print')
                                            <span class="badge bg-info">En curso</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($registro->quantity && $registro->quantity > 0)
                                            <strong>{{ $registro->quantity }}</strong>
                                        @else
                                            <span class="text-muted">0</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($registro->status === 'in_progress')
                                            <span class="badge bg-warning text-dark">En Curso</span>
                                        @elseif($registro->status === 'completed')
                                            <span class="badge bg-success">Completado</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $registro->status }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <form action="{{ route('admin.clientes.eliminarRegistro', $registro) }}"
                                              method="POST"
                                              class="d-inline"
                                              onsubmit="return confirm('Esta seguro de eliminar este registro?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Eliminar registro">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        No hay registros para este plan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($registros->hasPages())
                <div class="card-footer">
                    {{ $registros->links() }}
                </div>
            @endif
        </div>

        {{-- Espacio inferior --}}
        <div class="mb-5"></div>
    </div>
</div>
@endsection
