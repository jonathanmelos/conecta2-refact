@extends('layouts.conecta')

@section('content')
<div class="row">
    {{-- Columna principal --}}
    <div class="col-md-9">
        {{-- Buscador de clientes --}}
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Busca un cliente</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <label for="searchCliente" class="form-label">Buscar por Documento, Nombre o Apellido:</label>
                        <input type="text" class="form-control form-control-lg" id="searchCliente" 
                            placeholder="Escribe documento, nombre o apellido del cliente..." 
                            autofocus>
                        <small class="text-muted">Puedes buscar por cualquiera de los tres métodos</small>
                    </div>
                </div>
                
                <div id="searchResults" class="mt-3"></div>
            </div>
        </div>

        {{-- Registros del día --}}
        <h4 class="mb-3">CLIENTES COWORK DIARIO</h4>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Nombre y Apellido</th>
                        <th>Cowork</th>
                        <th>Sala Reuniones</th>
                        <th>Impresiones</th>
                        <th>Horas usadas</th>
                        <th>Servicio</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $clientesAgrupados = $registrosHoy->groupBy('client_id');
                    @endphp
                    
                    @forelse($clientesAgrupados as $clientId => $registros)
                        @php
                            $cliente = $registros->first()->client;
                            $registroCowork = $registros->where('service_type', 'cowork')->where('status', 'in_progress')->first();
                            if (!$registroCowork) {
                                $registroCowork = $registros->where('service_type', 'cowork')->sortByDesc('check_in')->first();
                            }
                            
                            $registroSala = $registros->where('service_type', 'meeting_room')->where('status', 'in_progress')->first();
                            if (!$registroSala) {
                                $registroSala = $registros->where('service_type', 'meeting_room')->sortByDesc('check_in')->first();
                            }
                            
                            $registroPrincipal = $registros->sortByDesc('check_in')->first();
                        @endphp
                        
                        <tr>
                           <td>
                                <a href="{{ route('admin.registro.index', ['doc' => $cliente->document_number]) }}" class="text-decoration-none">
                                    <strong>{{ $cliente->full_name }}</strong>
                                </a>
                                
                                {{-- ✅ MOSTRAR ESTADO ACTUAL DEL CLIENTE --}}
                                @if($cliente->invitedBy && $cliente->invitedBy->currentSubscription)
                                    {{-- ES INVITADO Y SIGUE VINCULADO --}}
                                    <br><small class="text-success">
                                        <i class="bi bi-link-45deg"></i> Vinculado al plan <strong>{{ $cliente->invitedBy->currentSubscription->plan->name ?? 'N/A' }}</strong> de <strong>{{ $cliente->invitedBy->full_name }}</strong>
                                    </small>
                                @elseif($cliente->currentSubscription && $cliente->currentSubscription->plan && !$cliente->invitedBy)
                                    {{-- TIENE SU PROPIO PLAN --}}
                                    <br><small class="badge bg-success">{{ $cliente->currentSubscription->plan->name }}</small>
                                @else
                                    {{-- CLIENTE OCASIONAL --}}
                                    <br><small class="badge bg-warning text-dark">Cliente Ocasional</small>
                                @endif
                            </td>
                            
                            <td>
                                @if($registroCowork && $registroCowork->status === 'in_progress')
                                    <form method="POST" action="{{ route('admin.registro.finalizar', $registroCowork->id) }}" style="display: inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-warning">Usando Cowork</button>
                                    </form>
                                @elseif($registroCowork && $registroCowork->status === 'completed')
                                    <button class="btn btn-sm btn-outline-secondary" disabled>Completado</button>
                                @else
                                    <form method="POST" action="{{ route('admin.registro.cowork') }}" style="display: inline;">
                                        @csrf
                                        <input type="hidden" name="client_id" value="{{ $cliente->id }}">
                                        <button type="submit" class="btn btn-sm btn-info">Registrar Horas</button>
                                    </form>
                                @endif
                            </td>
                            
                            <td>
                                @if($registroSala && $registroSala->status === 'in_progress')
                                    <form method="POST" action="{{ route('admin.registro.finalizar', $registroSala->id) }}" style="display: inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-warning">Usando Sala</button>
                                    </form>
                                @elseif($registroSala && $registroSala->status === 'completed')
                                    <button class="btn btn-sm btn-outline-secondary" disabled>Completado</button>
                                @else
                                    <form method="POST" action="{{ route('admin.registro.sala') }}" style="display: inline;">
                                        @csrf
                                        <input type="hidden" name="client_id" value="{{ $cliente->id }}">
                                        <button type="submit" class="btn btn-sm btn-info">Registrar Horas Sala</button>
                                    </form>
                                @endif
                            </td>
                            
                            <td>
                                <button class="btn btn-sm btn-info" 
                                        onclick="openImpresionesModal({{ $cliente->id }}, '{{ $cliente->full_name }}', {{ $registroPrincipal->id }})">
                                    Registrar Impresión {{ $registros->where('service_type', 'print')->sum('quantity') }}
                                </button>
                            </td>
                            
                            <td>
                                @php
                                    $registroActivo = $registros->where('status', 'in_progress')->first() ?? $registros->sortByDesc('check_in')->first();
                                @endphp
                                
                                @if($registroActivo)
                                    <img src="{{ asset('images/entrada.png') }}" width="20" height="20">
                                    {{ $registroActivo->check_in->format('H:i:s') }}
                                    
                                    @if($registroActivo->check_out)
                                        <br>
                                        <img src="{{ asset('images/salida.png') }}" width="20" height="20">
                                        {{ $registroActivo->check_out->format('H:i:s') }}
                                        <br>
                                        <img src="{{ asset('images/total_horas.png') }}" width="20" height="20">
                                        {{ number_format($registroActivo->duration_in_hours ?? 0, 2) }}
                                    @else
                                        <br>
                                        <span class="badge bg-success" id="timer-{{ $registroActivo->id }}">
                                            <img src="{{ asset('images/total_horas.png') }}" width="16" height="16">
                                            00:00:00
                                        </span>
                                    @endif
                                @endif
                            </td>
                            
                            <td>
                                <span class="badge {{ $registroPrincipal->service_type === 'cowork' ? 'bg-primary' : ($registroPrincipal->service_type === 'meeting_room' ? 'bg-info' : 'bg-secondary') }}">
                                    {{ $registroPrincipal->service_type === 'cowork' ? 'cowork' : ($registroPrincipal->service_type === 'meeting_room' ? 'sala' : 'impresión') }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
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
                    <h5>{{ $selectedClient->full_name }}</h5>
                    
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
                        <p class="mb-2">
                            <strong>Plan:</strong><br>
                            {{ $selectedClient->currentSubscription->plan->name }}
                        </p>
                        
                        <p class="mb-2">
                            <strong>Vigencia:</strong><br>
                            {{ $selectedClient->currentSubscription->start_date->format('d/m/Y') }} -
                            {{ $selectedClient->currentSubscription->end_date->format('d/m/Y') }}
                        </p>
                        
                        <p class="mb-2">
                            <strong>Días restantes:</strong>
                            <span class="badge {{ $selectedClient->currentSubscription->days_remaining < 5 ? 'bg-danger' : 'bg-success' }}">
                                {{ $selectedClient->currentSubscription->days_remaining }} días
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
    {{-- ✅ CLIENTE SIN PLAN - SOLO MOSTRAR INFO Y BOTÓN DE VINCULAR --}}
    <div class="alert alert-warning mb-3">
        Este cliente no tiene un plan activo.
    </div>
    
    <p class="mb-3"><small class="text-muted">Cliente ocasional - puede registrar uso de servicios con pago por uso.</small></p>
    
    <hr class="my-3">
    
    {{-- ✅ BOTÓN PARA VINCULAR COMO INVITADO --}}
    <button class="btn btn-success w-100 mb-3" onclick="openInviteModalFromPanel({{ $selectedClient->id }}, '{{ $selectedClient->full_name }}')">
        <i class="bi bi-person-plus-fill"></i> Vincular a Plan de Otro Cliente
    </button>
    
    <p class="mb-2">
        <small class="text-muted">
            <i class="bi bi-info-circle"></i> Al vincular este cliente, podrá usar las horas del plan del cliente master.
        </small>
    </p>
@endif
                </div>
            </div>
        @endif
        
        <div id="sidebarDefault" style="{{ $selectedClient ? 'display:none;' : '' }}">
            <div class="alert alert-info">
                <strong>Busca un cliente</strong> para ver su información y registrar uso.
            </div>
            
            <div id="createClientButton" class="d-none">
                <button class="btn btn-primary w-100" onclick="openCreateClientModal()">
                    <i class="bi bi-plus-circle"></i> Crear nuevo cliente
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
@endsection

@push('scripts')
{{-- ✅ INCLUIR SCRIPTS --}}
@include('admin.registro.scripts.search')
@include('admin.registro.scripts.select-service')
@include('admin.registro.scripts.modals')
@include('admin.registro.scripts.timers')
@endpush