@extends('layouts.conecta')

@section('content')
<div class="row">
    {{-- Columna principal --}}
    <div class="col-md-9">
        {{-- Seccion Principal: Registro de Ingreso --}}
        <div class="card mb-4 shadow-sm border-0">
            <div class="card-header bg-primary text-white py-3">
                <div class="d-flex align-items-center">
                    <i class="bi bi-door-open-fill fs-3 me-3"></i>
                    <div>
                        <h4 class="mb-0 fw-bold">Registro de Ingreso</h4>
                        <small class="opacity-75">Sistema de control de acceso al coworking</small>
                    </div>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="row">
                    <div class="col-12">
                        <div class="mb-3">
                            <p class="text-muted mb-2">
                                <i class="bi bi-info-circle me-1"></i>
                                Ingresa los datos del cliente para registrar su entrada a los servicios de <strong>Cowork</strong> o <strong>Sala de Reuniones</strong>.
                            </p>
                        </div>

                        <label for="searchCliente" class="form-label fw-semibold fs-5">
                            <i class="bi bi-search me-1"></i> Buscar por Documento, Nombre o Apellido:
                        </label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="bi bi-person-badge text-primary"></i>
                            </span>
                            <input type="text" class="form-control border-start-0 ps-0" id="searchCliente"
                                placeholder="Escribe documento, nombre o apellido del cliente..."
                                autofocus
                                style="font-size: 1.1rem;">
                        </div>
                        <div class="mt-2 d-flex align-items-center">
                            <small class="text-muted">
                                <i class="bi bi-lightbulb me-1 text-warning"></i>
                                Puedes buscar por <strong>cedula/RUC</strong>, <strong>nombre</strong> o <strong>apellido</strong>
                            </small>
                        </div>
                    </div>
                </div>

                <div id="searchResults" class="mt-3"></div>
            </div>
        </div>

        {{-- Registros del día --}}
        <h4 class="mb-3">CLIENTES COWORK DIARIO</h4>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="min-width: 250px;">Nombre y Apellido</th>
                        <th style="min-width: 200px;">Servicio</th>
                        <th style="min-width: 120px;">Horas usadas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clientesAgrupados as $clientId => $registros)
                        @php
                            $cliente = $registros->first()->client;
                            
                            // Obtener TODOS los registros de cowork del día
                            $registrosCowork = $registros->where('service_type', 'cowork')->sortByDesc('id');
                            $registroCowork = $registrosCowork->first();
                            
                            // Obtener TODOS los registros de sala del día
                            $registrosSala = $registros->where('service_type', 'meeting_room')->sortByDesc('id');
                            $registroSala = $registrosSala->first();
                            
                            // Registro activo si existe
                            $registroActivo = $registros->where('status', 'in_progress')->sortByDesc('id')->first();
                            $registroCompletado = $registros->where('status', 'completed')->sortByDesc('id')->first();
                        @endphp
                        
                        <tr>
                            {{-- COLUMNA NOMBRE Y APELLIDO --}}
                            <td style="min-width: 250px;">
                                <a href="{{ route('admin.registro.index', ['doc' => $cliente->document_number]) }}" class="text-decoration-none">
                                    <strong>{{ $cliente->full_name }}</strong>
                                    @include('components.badges.invitado', ['client' => $cliente])
                                </a>
                                
                                {{-- Estado actual del cliente --}}
                                @if($cliente->invitedBy && $cliente->invitedBy->currentSubscription)
                                    <br><small class="text-success">
                                        <i class="bi bi-link-45deg"></i> Vinculado al plan <strong>{{ $cliente->invitedBy->currentSubscription->plan->name ?? 'N/A' }}{{ $cliente->invitedBy->currentSubscription->plan && $cliente->invitedBy->currentSubscription->plan->is_pilot ? ' (Piloto)' : '' }}</strong> de <strong>{{ $cliente->invitedBy->full_name }}</strong>
                                    </small>
                                @elseif($cliente->currentSubscription && $cliente->currentSubscription->plan && !$cliente->invitedBy)
                                    <br><small class="badge bg-success">
                                        {{ $cliente->currentSubscription->plan->name }}
                                        {{ $cliente->currentSubscription->plan->is_pilot ? ' (Piloto)' : '' }}
                                    </small>
                                @else
                                    <br><small class="badge bg-warning text-dark">Cliente Ocasional</small>
                                @endif
                                
                                {{-- ✅ HISTORIAL DEL DÍA - Botón --}}
                                @include('admin.registro.partials.historial-cliente', ['registros' => $registros])
                            </td>
                            
                            {{-- COLUMNA SERVICIO --}}
                            <td>
                                @php
                                    $esRegistroAntiguoServicio = $registroActivo && $registroActivo->check_in->format('Y-m-d') < date('Y-m-d');
                                    $etiquetaServicio = $registroActivo && $registroActivo->service_type === 'meeting_room' ? 'Sala' : 'Cowork';
                                @endphp

                                @if($registroActivo)
                                    @if($esRegistroAntiguoServicio)
                                        <button class="btn btn-sm btn-danger w-100" disabled title="Registro antiguo - Ve al historial para cerrarlo">
                                            <i class="bi bi-lock-fill"></i> Bloqueado
                                        </button>
                                        <div class="small text-danger mt-1" style="font-size: 0.7rem;">
                                            <i class="bi bi-exclamation-triangle"></i> Desde {{ $registroActivo->check_in->format('d/m') }}
                                        </div>
                                        <div class="small text-muted mt-1">Revisa el historial para cerrar</div>
                                    @else
                                        <form method="POST" action="{{ route('admin.registro.finalizar', $registroActivo->id) }}" style="display: inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-warning w-100">
                                                <i class="bi bi-stop-circle-fill me-1"></i>Usando {{ $etiquetaServicio }}
                                            </button>
                                        </form>
                                        <div class="small text-muted mt-1">Click para finalizar</div>
                                    @endif
                                @elseif($registroCompletado)
                                    <button type="button" class="btn btn-sm btn-outline-secondary w-100" disabled>
                                        <i class="bi bi-check-circle-fill me-1"></i>Registro finalizado
                                    </button>
                                    <div class="small text-muted mt-1">Sin acciones pendientes</div>
                                @else
                                    <div class="d-flex flex-column gap-1">
                                        <form method="POST" action="{{ route('admin.registro.cowork') }}" style="display: inline;">
                                            @csrf
                                            <input type="hidden" name="client_id" value="{{ $cliente->id }}">
                                            <button type="submit" class="btn btn-sm btn-info w-100">
                                                <i class="bi bi-play-circle-fill me-1"></i>Iniciar Cowork
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.registro.sala') }}" style="display: inline;">
                                            @csrf
                                            <input type="hidden" name="client_id" value="{{ $cliente->id }}">
                                            <button type="submit" class="btn btn-sm btn-info w-100">
                                                <i class="bi bi-play-circle-fill me-1"></i>Iniciar Sala
                                            </button>
                                        </form>
                                    </div>
                                    <div class="small text-muted mt-1">Elige el servicio a iniciar</div>
                                @endif
                            </td>
                            
                            {{-- COLUMNA HORAS USADAS --}}
                            <td>
                                @php
                                    $registroActivoHoras = $registroActivo ?? $registros->sortByDesc('id')->first();
                                    $esRegistroAntiguo = $registroActivoHoras && $registroActivoHoras->status === 'in_progress' && $registroActivoHoras->check_in->format('Y-m-d') < date('Y-m-d');
                                @endphp
                                
                                @if($registroActivoHoras)
                                    {{-- ⚠️ ALERTA: Registro sin cerrar de días anteriores --}}
                                    @if($esRegistroAntiguo)
                                        <div class="alert alert-warning py-1 px-2 mb-1" style="font-size: 0.75rem; border-left: 4px solid #ff6b6b;">
                                            <i class="bi bi-exclamation-triangle-fill"></i> 
                                            <strong>Desde: {{ $registroActivoHoras->check_in->format('d/m/Y') }}</strong>
                                        </div>
                                    @endif
                                    
                                    <img src="{{ asset('images/entrada.png') }}" width="20" height="20">
                                    @if($esRegistroAntiguo)
                                        <strong class="text-danger">{{ $registroActivoHoras->check_in->format('d/m H:i') }}</strong>
                                    @else
                                        {{ $registroActivoHoras->check_in->format('H:i:s') }}
                                    @endif
                                    
                                    @if($registroActivoHoras->check_out)
                                        <br>
                                        <img src="{{ asset('images/salida.png') }}" width="20" height="20">
                                        {{ $registroActivoHoras->check_out->format('H:i:s') }}
                                        <br>
                                        <img src="{{ asset('images/total_horas.png') }}" width="20" height="20">
                                        {{ number_format($registroActivoHoras->duration_in_hours ?? 0, 2) }}
                                    @else
                                        <br>
                                        <span class="badge {{ $esRegistroAntiguo ? 'bg-danger pulse-danger' : 'bg-success' }}" id="timer-{{ $registroActivoHoras->id }}">
                                            <img src="{{ asset('images/total_horas.png') }}" width="16" height="16">
                                            00:00:00
                                        </span>
                                    @endif
                                @endif
                            </td>
                            
                            @if(false)
                            {{-- COLUMNA SERVICIO --}}
                            <td>
                                <span class="badge {{ $registroPrincipal->service_type === 'cowork' ? 'bg-primary' : ($registroPrincipal->service_type === 'meeting_room' ? 'bg-info' : 'bg-secondary') }}">
                                    {{ $registroPrincipal->service_type === 'cowork' ? 'cowork' : ($registroPrincipal->service_type === 'meeting_room' ? 'sala' : 'impresión') }}
                                </span>
                            </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">
                                No hay registros para hoy
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Columna lateral --}}
    <div class="col-md-3">
        @if($selectedClient)
            <div class="card" id="clientInfoCard">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">Estado del Plan</h6>
                </div>
                <div class="card-body">
                    <h5>{{ $selectedClient->full_name }} @include('components.badges.invitado', ['client' => $selectedClient])</h5>
                    
                    @if($selectedClient->invitedBy)
                        <div class="card border-warning mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <small class="text-muted"><strong>Invitado de:</strong></small>
                                        <h6 class="mb-0">{{ $selectedClient->invitedBy->full_name }}</h6>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            onclick="confirmUnlinkSelf({{ $selectedClient->id }}, '{{ $selectedClient->full_name }}', '{{ $selectedClient->invitedBy->full_name }}')"
                                            title="Desvincular">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    @if($selectedClient->currentSubscription)
                        {{-- CLIENTE CON PLAN --}}
                        <p class="mb-2">
                            <strong>Plan:</strong><br>
                            {{ $selectedClient->currentSubscription->plan->name }}
                            {{ $selectedClient->currentSubscription->plan->is_pilot ? ' (Piloto)' : '' }}
                        </p>
                        
                        <p class="mb-2">
                            <strong>Vigencia:</strong><br>
                            {{ $selectedClient->currentSubscription->start_date->format('d/m/Y') }} -
                            {{ $selectedClient->currentSubscription->end_date ? $selectedClient->currentSubscription->end_date->format('d/m/Y') : 'Sin vencimiento' }}
                        </p>
                        
                        <p class="mb-2">
                            <strong>Días restantes:</strong>
                            <span class="badge {{ $selectedClient->currentSubscription->days_remaining !== null && $selectedClient->currentSubscription->days_remaining < 5 ? 'bg-danger' : 'bg-success' }}">
                                {{ $selectedClient->currentSubscription->days_remaining !== null ? $selectedClient->currentSubscription->days_remaining . ' días' : 'Sin vencimiento' }}
                            </span>
                        </p>
                        
                        <hr>
                        
                        @if($hoursTracking && isset($hoursTracking['cowork']))
                            <div class="mb-3">
                                <strong>Horas Cowork:</strong>
                                <div class="progress mt-1">
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: {{ min($hoursTracking['cowork']->usage_percentage, 100) }}%">
                                        {{ round($hoursTracking['cowork']->usage_percentage, 1) }}%
                                    </div>
                                </div>
                                <small class="text-muted">
                                    {{ $hoursTracking['cowork']->hours_used }} / {{ $hoursTracking['cowork']->total_hours_available }} horas
                                </small>
                            </div>
                        @endif
                        
                        @if($hoursTracking && isset($hoursTracking['meeting_room']))
                            <div class="mb-3">
                                <strong>Horas Sala:</strong>
                                <div class="progress mt-1">
                                    <div class="progress-bar bg-info" role="progressbar" 
                                         style="width: {{ min($hoursTracking['meeting_room']->usage_percentage, 100) }}%">
                                        {{ round($hoursTracking['meeting_room']->usage_percentage, 1) }}%
                                    </div>
                                </div>
                                <small class="text-muted">
                                    {{ $hoursTracking['meeting_room']->hours_used }} / {{ $hoursTracking['meeting_room']->total_hours_available }} horas
                                </small>
                            </div>
                        @endif
                        
                        {{-- ⭐ BOTÓN DE IMPRESIONES PARA CLIENTES CON PLAN --}}
                        <hr>
                        <div class="mb-3">
                            <button class="btn btn-info w-100" 
                                    onclick="openImpresionesModal({{ $selectedClient->id }}, '{{ $selectedClient->full_name }}', {{ $selectedClient->id }})">
                                <i class="bi bi-printer-fill"></i> Registrar Impresión
                            </button>
                            @php
                                $impresionesHoy = $registrosHoy->where('client_id', $selectedClient->id)
                                                               ->where('service_type', 'print')
                                                               ->sum('quantity');
                            @endphp
                            @if($impresionesHoy > 0)
                                <small class="text-muted d-block mt-2 text-center">
                                    <i class="bi bi-printer"></i> Hoy: {{ $impresionesHoy }} impresiones
                                </small>
                            @endif
                        </div>
                        
                        @if($selectedClient->guests && $selectedClient->guests->count() > 0)
                            <hr>
                            <strong>Invitados vinculados:</strong>
                            <ul class="list-unstyled mt-2">
                                @foreach($selectedClient->guests as $guest)
                                    <li class="mb-2 d-flex justify-content-between align-items-center">
                                        <small>
                                            <i class="bi bi-person-fill text-success"></i> {{ $guest->full_name }}
                                        </small>
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                onclick="confirmUnlink({{ $guest->id }}, '{{ $guest->full_name }}', {{ $selectedClient->id }})"
                                                title="Desvincular">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    @else
                        {{-- ✅ CLIENTE SIN PLAN (OCASIONAL) --}}
                        <div class="alert alert-warning mb-3">
                            Este cliente no tiene un plan activo.
                        </div>
                        
                        <p class="mb-3"><small class="text-muted">Cliente ocasional - puede registrar uso de servicios con pago por uso.</small></p>
                        
                        <hr class="my-3">
                        
                        {{-- ⭐ BOTÓN DE IMPRESIONES PARA CLIENTES OCASIONALES --}}
                        <div class="mb-3">
                            <button class="btn btn-info w-100" 
                                    onclick="openImpresionesModal({{ $selectedClient->id }}, '{{ $selectedClient->full_name }}', {{ $selectedClient->id }})">
                                <i class="bi bi-printer-fill"></i> Registrar Impresión
                            </button>
                            @php
                                $impresionesHoy = $registrosHoy->where('client_id', $selectedClient->id)
                                                               ->where('service_type', 'print')
                                                               ->sum('quantity');
                            @endphp
                            @if($impresionesHoy > 0)
                                <small class="text-muted d-block mt-2 text-center">
                                    <i class="bi bi-printer"></i> Hoy: {{ $impresionesHoy }} impresiones
                                </small>
                            @endif
                        </div>
                        
                        <hr class="my-3">
                        
                        {{-- ⭐ BOTÓN VINCULAR: Solo si NO tiene registros completados --}}
                        @php
                            $tieneRegistrosCompletados = $registrosHoy->where('client_id', $selectedClient->id)
                                                                      ->where('status', 'completed')
                                                                      ->count() > 0;
                        @endphp
                        
                        @if(!$tieneRegistrosCompletados)
                            {{-- BOTÓN PARA VINCULAR COMO INVITADO --}}
                            <button class="btn btn-success w-100 mb-3" 
                                    onclick="openInviteModalFromPanel({{ $selectedClient->id }}, '{{ $selectedClient->full_name }}')">
                                <i class="bi bi-person-plus-fill"></i> Vincular a Plan de Otro Cliente
                            </button>
                            
                            <p class="mb-2">
                                <small class="text-muted">
                                    <i class="bi bi-info-circle"></i> Al vincular este cliente, podrá usar las horas del plan del cliente master.
                                </small>
                            </p>
                        @else
                            {{-- MENSAJE INFORMATIVO SI TIENE REGISTROS COMPLETADOS --}}
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-info-circle"></i> 
                                <small>
                                    <strong>No se puede vincular.</strong><br>
                                    Este cliente tiene registros completados hoy. Solo se puede vincular a clientes sin registros cerrados.
                                </small>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        @endif
        
        <div id="sidebarDefault" style="{{ $selectedClient ? 'display:none;' : '' }}">
            <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);">
                <div class="card-body text-center py-4">
                    <i class="bi bi-search fs-1 text-primary mb-3 d-block"></i>
                    <h5 class="text-primary fw-bold">Busca un cliente</h5>
                    <p class="text-muted mb-0">
                        para ver su informacion y registrar uso.
                    </p>
                </div>
            </div>

            <div id="createClientButton" class="d-none mt-3">
                <button class="btn btn-primary w-100 py-2" onclick="openCreateClientModal()">
                    <i class="bi bi-plus-circle me-2"></i> Crear nuevo cliente
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ✅ INCLUIR MODALES --}}
@include('admin.registro.modals.select-service')
@include('admin.registro.modals.impresiones')
@include('admin.registro.modals.create-client')
@include('admin.registro.modals.link-guest')
@include('admin.registro.modals.unlink-guest')
@include('admin.registro.modals.edit-record')
@include('admin.registro.modals.close-old-record')

@endsection

@push('scripts')
{{-- ✅ INCLUIR SCRIPTS --}}
@include('admin.registro.scripts.search')
@include('admin.registro.scripts.select-service')
@if($selectedClient && request('open_service') && $selectedClientData)
<script>
document.addEventListener('DOMContentLoaded', function() {
    const clientData = @json($selectedClientData);
    if (clientData && typeof openServiceModal === 'function') {
        const clientName = (clientData.first_name || '') + ' ' + (clientData.last_name || '');
        openServiceModal(clientData.id, clientName.trim(), clientData.document_number, clientData);
    }
});
</script>
@endif
@include('admin.registro.scripts.modals')
@include('admin.registro.scripts.timers')
@include('admin.registro.scripts.historial')
@include('admin.registro.scripts.close-old-record')
@endpush
