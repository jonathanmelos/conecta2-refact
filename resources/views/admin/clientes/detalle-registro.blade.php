@extends('layouts.conecta')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>Detalle de Registro #{{ $subscription->id }}</h2>
                <p class="text-muted mb-0">
                    {{ $client->full_name }}
                    @include('components.badges.invitado', ['client' => $client])
                    - {{ $subscription->plan->name ?? 'Plan' }}
                    {{ $subscription->plan && $subscription->plan->is_pilot ? ' (Piloto)' : '' }}
                    @if($client->invited_by_client_id && $subscription->client_id === $client->invited_by_client_id && $client->invitedBy)
                        <span class="ms-1">· Plan de {{ $client->invitedBy->full_name }}</span>
                    @endif
                    ({{ $subscription->start_date->format('d/m/Y') }} - {{ $subscription->end_date ? $subscription->end_date->format('d/m/Y') : 'Sin vencimiento' }})
                </p>
            </div>
            <div class="d-flex">
                <button type="button" class="btn btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#modalFechasPlan">
                    Editar Fechas
                </button>
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

        <div class="modal fade" id="modalFechasPlan" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('admin.clientes.actualizarFechasPlan', [$client, $subscription]) }}">
                        @csrf
                        @method('PUT')
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">Editar Fechas del Plan</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="plan_start_date" class="form-label">Fecha de inicio</label>
                                <input type="date"
                                       class="form-control"
                                       id="plan_start_date"
                                       name="start_date"
                                       value="{{ $subscription->start_date ? $subscription->start_date->format('Y-m-d') : '' }}"
                                       required>
                            </div>
                            <div class="mb-3">
                                <label for="plan_end_date" class="form-label">Fecha de finalización</label>
                                <input type="date"
                                       class="form-control"
                                       id="plan_end_date"
                                       name="end_date"
                                       value="{{ $subscription->end_date ? $subscription->end_date->format('Y-m-d') : '' }}">
                                <small class="text-muted">Si no aplica, deja el campo vacío.</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar Fechas</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Resumen de Contadores con efecto vaso --}}
        @php
            // Calcular porcentajes de uso
            $isPilotPlan = $subscription->plan && $subscription->plan->is_pilot;
            $porcentajeCowork = !$isPilotPlan && $horasCoworkContratadas > 0
                ? min(100, ($horasCoworkUsadas / $horasCoworkContratadas) * 100)
                : 0;
            $porcentajeSala = !$isPilotPlan && $horasSalaContratadas > 0
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
                            <small class="fw-bold">
                                {{ $isPilotPlan ? 'Uso acumulado Cowork' : 'de ' . number_format($horasCoworkContratadas, 1) . 'h Cowork' }}
                            </small>
                            <small class="mt-1">
                                {{ $isPilotPlan ? 'Sin limite' : '(' . number_format($porcentajeCowork, 0) . '% usado)' }}
                            </small>
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
                            <small class="fw-bold">
                                {{ $isPilotPlan ? 'Uso acumulado Sala' : 'de ' . number_format($horasSalaContratadas, 1) . 'h Sala' }}
                            </small>
                            <small class="mt-1">
                                {{ $isPilotPlan ? 'Sin limite' : '(' . number_format($porcentajeSala, 0) . '% usado)' }}
                            </small>
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
                <h5 class="mb-0">
                    Resumen del Plan: {{ $subscription->plan->name ?? 'Plan' }}
                    {{ $subscription->plan && $subscription->plan->is_pilot ? ' (Piloto)' : '' }}
                </h5>
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
                                    <td class="text-end fw-bold">
                                        {{ $isPilotPlan ? 'Ilimitadas' : sprintf('%d:%02d', floor($horasCoworkContratadas), ($horasCoworkContratadas - floor($horasCoworkContratadas)) * 60) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>Usadas:</td>
                                    <td class="text-end fw-bold text-danger">{{ sprintf('%d:%02d', floor($horasCoworkUsadas), ($horasCoworkUsadas - floor($horasCoworkUsadas)) * 60) }}</td>
                                </tr>
                                <tr class="border-top">
                                    <td>Restantes:</td>
                                    <td class="text-end fw-bold text-success">
                                        {{ $isPilotPlan ? 'Ilimitadas' : sprintf('%d:%02d', floor($horasCoworkRestantes), ($horasCoworkRestantes - floor($horasCoworkRestantes)) * 60) }}
                                    </td>
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
                                    <td class="text-end fw-bold">
                                        {{ $isPilotPlan ? 'Ilimitadas' : sprintf('%d:%02d', floor($horasSalaContratadas), ($horasSalaContratadas - floor($horasSalaContratadas)) * 60) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>Usadas:</td>
                                    <td class="text-end fw-bold text-danger">{{ sprintf('%d:%02d', floor($horasSalaUsadas), ($horasSalaUsadas - floor($horasSalaUsadas)) * 60) }}</td>
                                </tr>
                                <tr class="border-top">
                                    <td>Restantes:</td>
                                    <td class="text-end fw-bold text-success">
                                        {{ $isPilotPlan ? 'Ilimitadas' : sprintf('%d:%02d', floor($horasSalaRestantes), ($horasSalaRestantes - floor($horasSalaRestantes)) * 60) }}
                                    </td>
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
                                <tr class="{{ isset($highlightClientId) && $highlightClientId && $registro->client_id == $highlightClientId ? 'table-warning' : '' }}">
                                    <td>
                                        @if($registro->client)
                                            {{ $registro->client->full_name }}
                                            @include('components.badges.invitado', ['client' => $registro->client])
                                            <small class="text-muted">({{ $registro->id }})</small>
                                        @else
                                            {{ $client->full_name }}
                                            @include('components.badges.invitado', ['client' => $client])
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
                                        <button type="button"
                                                class="btn btn-outline-primary btn-sm me-1 edit-record-btn"
                                                title="Editar registro"
                                                data-record-id="{{ $registro->id }}"
                                                data-client-name="{{ $registro->client ? $registro->client->full_name : $client->full_name }}"
                                                data-service-type="{{ $registro->service_type }}"
                                                data-check-in="{{ $registro->check_in->format('Y-m-d\TH:i') }}"
                                                data-check-out="{{ $registro->check_out ? $registro->check_out->format('Y-m-d\TH:i') : '' }}"
                                                data-subscription-id="{{ $registro->subscription_id }}"
                                                data-client-id="{{ $registro->client_id }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>
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

{{-- Modal para editar registro --}}
<div class="modal fade" id="editRecordModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title">
                    <i class="bi bi-pencil-square"></i> Editar Registro
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form method="POST" id="editRecordForm" action="" onsubmit="console.log('Formulario enviado', this.action)">
                @csrf
                @method('PUT')

                <div class="modal-body">
                    {{-- INFORMACIÓN DEL CLIENTE Y SERVICIO --}}
                    <div class="alert alert-info">
                        <strong>Cliente:</strong> <span id="edit_client_name"></span><br>
                        <strong>Servicio:</strong> <span id="edit_service_type"></span>
                    </div>

                    {{-- ENTRADA --}}
                    <div class="card border-success mb-3">
                        <div class="card-header bg-success text-white">
                            <i class="bi bi-box-arrow-in-right"></i> Entrada
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="edit_check_in_date" class="form-label">
                                        Fecha: <span class="text-danger">*</span>
                                    </label>
                                    <input type="date"
                                           class="form-control"
                                           id="edit_check_in_date"
                                           name="check_in_date"
                                           required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="edit_check_in_time" class="form-label">
                                        Hora: <span class="text-danger">*</span>
                                    </label>
                                    <input type="time"
                                           class="form-control"
                                           id="edit_check_in_time"
                                           name="check_in_time"
                                           required>
                                    <small class="text-muted">Formato 24h recomendado (ej: 13:30). 12:00 a.m. = 00:00, 12:00 p.m. = 12:00.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- SALIDA --}}
                    <div class="card border-danger mb-3">
                        <div class="card-header bg-danger text-white">
                            <i class="bi bi-box-arrow-left"></i> Salida
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="edit_check_out_date" class="form-label">
                                        Fecha:
                                    </label>
                                    <input type="date"
                                           class="form-control"
                                           id="edit_check_out_date"
                                           name="check_out_date">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="edit_check_out_time" class="form-label">
                                        Hora:
                                    </label>
                                    <input type="time"
                                           class="form-control"
                                           id="edit_check_out_time"
                                           name="check_out_time">
                                    <small class="text-muted">Formato 24h recomendado (ej: 13:30). 12:00 a.m. = 00:00, 12:00 p.m. = 12:00.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- TIPO DE SERVICIO --}}
                    <div class="mb-3">
                        <label for="edit_service_type_select" class="form-label">
                            <i class="bi bi-briefcase"></i> Tipo de Servicio: <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="edit_service_type_select" name="service_type" required>
                            <option value="cowork">Cowork</option>
                            <option value="meeting_room">Sala de Reuniones</option>
                        </select>
                    </div>

                    {{-- PREVISUALIZACIÓN DE DURACIÓN --}}
                    <div id="edit_duration_preview" class="alert alert-success d-none mb-3">
                        <strong><i class="bi bi-clock-history"></i> Duración:</strong>
                        <span id="duration_text"></span>
                    </div>

                    <hr>

                    {{-- PLAN VINCULADO --}}
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-link-45deg"></i> Plan Vinculado
                        </label>

                        <div class="card">
                            <div class="card-body">
                                <div id="edit_current_plan_display" class="mb-3">
                                    <small class="text-muted">Plan actual:</small>
                                    <div id="edit_current_plan_info"></div>
                                </div>

                                <div class="form-check mb-2">
                                    <input class="form-check-input"
                                           type="radio"
                                           name="plan_option"
                                           id="plan_option_keep"
                                           value="keep"
                                           checked>
                                    <label class="form-check-label" for="plan_option_keep">
                                        <i class="bi bi-check-circle"></i> Mantener plan actual
                                    </label>
                                </div>

                                <div class="form-check mb-2">
                                    <input class="form-check-input"
                                           type="radio"
                                           name="plan_option"
                                           id="plan_option_link"
                                           value="link">
                                    <label class="form-check-label" for="plan_option_link">
                                        <i class="bi bi-person-plus"></i> Vincular a plan de otro cliente
                                    </label>
                                </div>

                                <div class="form-check mb-3">
                                    <input class="form-check-input"
                                           type="radio"
                                           name="plan_option"
                                           id="plan_option_remove"
                                           value="remove">
                                    <label class="form-check-label" for="plan_option_remove">
                                        <i class="bi bi-x-circle"></i> Quitar vinculación (registro ocasional)
                                    </label>
                                </div>

                                <div class="form-check mb-3">
                                    <input class="form-check-input"
                                           type="radio"
                                           name="plan_option"
                                           id="plan_option_move"
                                           value="move">
                                    <label class="form-check-label" for="plan_option_move">
                                        <i class="bi bi-arrow-left-right"></i> Mover a otro plan del cliente
                                    </label>
                                </div>

                                <div id="edit_move_plan_container" class="d-none">
                                    <label for="edit_move_plan_id" class="form-label">
                                        Selecciona el plan disponible:
                                    </label>
                                    <select class="form-select mb-2" id="edit_move_plan_id" name="move_subscription_id">
                                        <option value="">Seleccione un plan...</option>
                                        @foreach($clientSubscriptions as $clientSubscription)
                                            @php
                                                $planName = $clientSubscription->plan->name ?? 'Plan';
                                                $planLabel = $planName
                                                    . ($clientSubscription->plan && $clientSubscription->plan->is_pilot ? ' (Piloto)' : '');
                                                $startLabel = $clientSubscription->start_date->format('d/m/Y');
                                                $endLabel = $clientSubscription->end_date
                                                    ? $clientSubscription->end_date->format('d/m/Y')
                                                    : 'Sin vencimiento';
                                            @endphp
                                            <option value="{{ $clientSubscription->id }}"
                                                    data-start="{{ $clientSubscription->start_date->format('Y-m-d') }}"
                                                    data-end="{{ $clientSubscription->end_date ? $clientSubscription->end_date->format('Y-m-d') : '' }}">
                                                {{ $planLabel }} ({{ $startLabel }} - {{ $endLabel }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <small id="edit_move_plan_help" class="text-muted"></small>
                                </div>

                                {{-- BUSCADOR DE CLIENTE MASTER --}}
                                <div id="edit_master_search_container" class="d-none">
                                    <label for="edit_master_search" class="form-label">
                                        Buscar cliente con plan:
                                    </label>
                                    <input type="text"
                                           class="form-control mb-2"
                                           id="edit_master_search"
                                           placeholder="Escribe nombre o documento...">
                                    <div id="edit_master_search_results" class="list-group mb-2"></div>
                                    <input type="hidden" id="edit_new_subscription_id" name="new_subscription_id">

                                    <div id="edit_selected_master" class="alert alert-success d-none">
                                        <strong>Cliente seleccionado:</strong><br>
                                        <span id="edit_selected_master_name"></span><br>
                                        <small id="edit_selected_master_plan"></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning mb-0">
                        <i class="bi bi-info-circle"></i> <strong>Nota importante:</strong><br>
                        Al guardar los cambios, se recalculará la duración y se actualizarán las horas consumidas del plan automáticamente.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-warning" id="edit_record_submit">
                        <i class="bi bi-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Abrir modal de edición
    document.querySelectorAll('.edit-record-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const recordId = this.dataset.recordId;
            const clientName = this.dataset.clientName;
            const serviceType = this.dataset.serviceType;
            const checkIn = this.dataset.checkIn;
            const checkOut = this.dataset.checkOut;
            const subscriptionId = this.dataset.subscriptionId;

            // Establecer la acción del formulario
            const formAction = `/admin/clientes/registro/${recordId}`;
            document.getElementById('editRecordForm').action = formAction;
            console.log('Action establecida:', formAction);

            // Llenar el modal con los datos
            document.getElementById('edit_client_name').textContent = clientName;

            // Formatear el tipo de servicio
            const serviceTypeLabel = serviceType === 'cowork' ? 'Cowork' : 'Sala de Reuniones';
            document.getElementById('edit_service_type').textContent = serviceTypeLabel;

            // Separar fecha y hora de entrada
            if (checkIn) {
            const [date, time] = checkIn.split('T');
            document.getElementById('edit_check_in_date').value = date;
            document.getElementById('edit_check_in_time').value = time;
            filterMovePlans();
            }

            // Separar fecha y hora de salida
            if (checkOut) {
                const [date, time] = checkOut.split('T');
                document.getElementById('edit_check_out_date').value = date;
                document.getElementById('edit_check_out_time').value = time;
            } else {
                document.getElementById('edit_check_out_date').value = '';
                document.getElementById('edit_check_out_time').value = '';
            }

            // Establecer el tipo de servicio
            document.getElementById('edit_service_type_select').value = serviceType;

            // Mostrar información del plan actual
            if (subscriptionId) {
                document.getElementById('edit_current_plan_info').innerHTML =
                    '<span class="badge bg-success">Plan Activo</span>';
            } else {
                document.getElementById('edit_current_plan_info').innerHTML =
                    '<span class="badge bg-secondary">Sin plan vinculado</span>';
            }

            // Resetear opciones de plan
            document.getElementById('plan_option_keep').checked = true;
            document.getElementById('edit_master_search_container').classList.add('d-none');
            document.getElementById('edit_move_plan_container').classList.add('d-none');
            document.getElementById('edit_move_plan_id').value = '';

            // Resetear preview de duración
            document.getElementById('edit_duration_preview').classList.add('d-none');

            // Mostrar el modal
            const modal = new bootstrap.Modal(document.getElementById('editRecordModal'));
            modal.show();

            // Calcular duración después de mostrar el modal
            setTimeout(calculateDuration, 100);
        });
    });

    // Calcular duración cuando cambian las fechas y horas
    const checkInDateInput = document.getElementById('edit_check_in_date');
    const checkInTimeInput = document.getElementById('edit_check_in_time');
    const checkOutDateInput = document.getElementById('edit_check_out_date');
    const checkOutTimeInput = document.getElementById('edit_check_out_time');
    const durationPreview = document.getElementById('edit_duration_preview');
    const durationText = document.getElementById('duration_text');
    const submitButton = document.getElementById('edit_record_submit');

    function parseTimeValue(timeValue) {
        if (!timeValue) {
            return null;
        }

        const match = timeValue.trim().match(/^(\d{1,2}):(\d{2})(?:\s*([ap])\.?m\.?)?$/i);
        if (!match) {
            return null;
        }

        let hour = Number(match[1]);
        const minute = Number(match[2]);
        const meridiem = match[3];

        if (Number.isNaN(hour) || Number.isNaN(minute)) {
            return null;
        }

        if (meridiem) {
            const lower = meridiem.toLowerCase();
            if (lower === 'p' && hour < 12) {
                hour += 12;
            }
            if (lower === 'a' && hour === 12) {
                hour = 0;
            }
        }

        return { hour, minute };
    }

    function buildDateTime(dateValue, timeInput) {
        if (!dateValue) {
            return null;
        }

        const [year, month, day] = dateValue.split('-').map(Number);
        if (!year || !month || !day) {
            return null;
        }

        let hour;
        let minute;

        if (Number.isFinite(timeInput.valueAsNumber)) {
            const totalMinutes = Math.floor(timeInput.valueAsNumber / 60000);
            hour = Math.floor(totalMinutes / 60);
            minute = totalMinutes % 60;
        } else {
            const parsed = parseTimeValue(timeInput.value);
            if (!parsed) {
                return null;
            }
            ({ hour, minute } = parsed);
        }

        const dateTime = new Date(year, month - 1, day, hour, minute, 0);
        return Number.isNaN(dateTime.getTime()) ? null : dateTime;
    }

    function calculateDuration() {
        const checkInDate = checkInDateInput.value;
        const checkOutDate = checkOutDateInput.value;

        console.log('Calculando duración:', {
            checkInDate,
            checkInTime: checkInTimeInput.value,
            checkOutDate,
            checkOutTime: checkOutTimeInput.value
        });

        const checkInDateTime = buildDateTime(checkInDate, checkInTimeInput);
        const hasCheckOut = Boolean(checkOutDate || checkOutTimeInput.value);
        const checkOutDateTime = buildDateTime(checkOutDate, checkOutTimeInput);

        if (!checkInDateTime || (hasCheckOut && !checkOutDateTime)) {
            durationPreview.classList.add('d-none');
            if (submitButton) {
                submitButton.disabled = false;
            }
            return;
        }

        if (!hasCheckOut) {
            durationPreview.classList.add('d-none');
            if (submitButton) {
                submitButton.disabled = false;
            }
            return;
        }

        console.log('Fechas creadas:', { checkInDateTime, checkOutDateTime });

        const durationMs = checkOutDateTime.getTime() - checkInDateTime.getTime();
        console.log('Duración en ms:', durationMs);

        if (durationMs <= 0) {
            durationText.textContent = 'La salida debe ser después de la entrada';
            durationPreview.classList.remove('d-none');
            durationPreview.classList.remove('alert-success');
            durationPreview.classList.add('alert-danger');
            if (submitButton) {
                submitButton.disabled = true;
            }
            return;
        }

        const durationMinutes = Math.floor(durationMs / 60000);
        const days = Math.floor(durationMinutes / 1440);
        const hours = Math.floor((durationMinutes % 1440) / 60);
        const minutes = durationMinutes % 60;

        let durationStr = '';
        if (days > 0) {
            durationStr += `${days}d `;
        }
        durationStr += `${hours}h ${minutes}m`;

        console.log('Duración calculada:', durationStr);

        durationText.textContent = durationStr;
        durationPreview.classList.remove('d-none');
        durationPreview.classList.remove('alert-danger');
        durationPreview.classList.add('alert-success');
        if (submitButton) {
            submitButton.disabled = false;
        }
    }

    checkInDateInput.addEventListener('change', calculateDuration);
    checkInTimeInput.addEventListener('change', calculateDuration);
    checkOutDateInput.addEventListener('change', calculateDuration);
    checkOutTimeInput.addEventListener('change', calculateDuration);

    // Manejar cambios de opciones de plan
    const movePlanContainer = document.getElementById('edit_move_plan_container');
    const movePlanSelect = document.getElementById('edit_move_plan_id');
    const movePlanHelp = document.getElementById('edit_move_plan_help');

    function filterMovePlans() {
        const checkInDate = document.getElementById('edit_check_in_date').value;
        if (!checkInDate || !movePlanSelect) {
            if (movePlanHelp) {
                movePlanHelp.textContent = '';
            }
            return;
        }

        const options = Array.from(movePlanSelect.options);
        let visibleCount = 0;
        options.forEach(option => {
            if (!option.value) {
                return;
            }
            const start = option.dataset.start;
            const end = option.dataset.end;
            const withinStart = !start || start <= checkInDate;
            const withinEnd = !end || end >= checkInDate;
            const visible = withinStart && withinEnd;
            option.hidden = !visible;
            if (visible) {
                visibleCount += 1;
            }
        });

        if (movePlanHelp) {
            movePlanHelp.textContent = visibleCount === 0
                ? 'No hay planes vigentes para la fecha seleccionada.'
                : '';
        }
    }

    document.querySelectorAll('input[name="plan_option"]').forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'link') {
                document.getElementById('edit_master_search_container').classList.remove('d-none');
                movePlanContainer.classList.add('d-none');
            } else if (this.value === 'move') {
                document.getElementById('edit_master_search_container').classList.add('d-none');
                movePlanContainer.classList.remove('d-none');
                filterMovePlans();
            } else {
                document.getElementById('edit_master_search_container').classList.add('d-none');
                movePlanContainer.classList.add('d-none');
            }
        });
    });

    document.getElementById('edit_check_in_date').addEventListener('change', filterMovePlans);

    // Búsqueda de clientes con plan
    let searchTimeout;
    document.getElementById('edit_master_search').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value;

        if (query.length < 2) {
            document.getElementById('edit_master_search_results').innerHTML = '';
            return;
        }

        searchTimeout = setTimeout(() => {
            fetch(`/admin/registro/buscar-cliente?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    const resultsDiv = document.getElementById('edit_master_search_results');
                    resultsDiv.innerHTML = '';

                    if (data.length === 0) {
                        resultsDiv.innerHTML = '<div class="list-group-item text-muted">No se encontraron clientes</div>';
                        return;
                    }

                    data.forEach(client => {
                        const item = document.createElement('a');
                        item.href = '#';
                        item.className = 'list-group-item list-group-item-action';
                        item.innerHTML = `
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong>${client.full_name}</strong><br>
                                    <small class="text-muted">${client.document_number}</small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-success">${client.plan_name}${client.plan_is_pilot ? ' (Piloto)' : ''}</span>
                                </div>
                            </div>
                        `;

                        item.addEventListener('click', function(e) {
                            e.preventDefault();
                            document.getElementById('edit_new_subscription_id').value = client.subscription_id;
                            document.getElementById('edit_selected_master_name').textContent = client.full_name;
                            document.getElementById('edit_selected_master_plan').textContent = `Plan: ${client.plan_name}${client.plan_is_pilot ? ' (Piloto)' : ''}`;
                            document.getElementById('edit_selected_master').classList.remove('d-none');
                            resultsDiv.innerHTML = '';
                            document.getElementById('edit_master_search').value = '';
                        });

                        resultsDiv.appendChild(item);
                    });
                });
        }, 300);
    });
});
</script>
@endpush

@endsection
