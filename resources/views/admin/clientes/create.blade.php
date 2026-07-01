@extends('layouts.conecta')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Crear Nuevo Cliente</h2>
            <a href="{{ route('admin.clientes.index') }}" class="btn btn-secondary">
                Volver al listado
            </a>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.clientes.store') }}" method="POST" id="createClientForm">
            @csrf

            {{-- Tipo de cliente --}}
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Tipo de Cliente</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipo_cliente"
                                       id="tipoConPlan" value="con_plan"
                                       {{ old('tipo_cliente', 'con_plan') === 'con_plan' ? 'checked' : '' }}>
                                <label class="form-check-label" for="tipoConPlan">
                                    <strong>Cliente con Plan</strong>
                                    <br><small class="text-muted">Cliente que adquiere un plan/suscripción mensual</small>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipo_cliente"
                                       id="tipoInvitado" value="invitado"
                                       {{ old('tipo_cliente') === 'invitado' ? 'checked' : '' }}>
                                <label class="form-check-label" for="tipoInvitado">
                                    <strong>Cliente por Horas (Invitado)</strong>
                                    <br><small class="text-muted">Cliente ocasional vinculado al plan de otro cliente</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Datos del cliente --}}
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Datos del Cliente</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="document_number" class="form-label">Documento <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('document_number') is-invalid @enderror"
                                   id="document_number" name="document_number"
                                   value="{{ old('document_number') }}" required>
                            @error('document_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="first_name" class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('first_name') is-invalid @enderror"
                                   id="first_name" name="first_name"
                                   value="{{ old('first_name') }}" required>
                            @error('first_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="last_name" class="form-label">Apellido</label>
                            <input type="text" class="form-control @error('last_name') is-invalid @enderror"
                                   id="last_name" name="last_name"
                                   value="{{ old('last_name') }}">
                            @error('last_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="email" class="form-label">Correo Electrónico</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                   id="email" name="email"
                                   value="{{ old('email') }}">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="phone" class="form-label">Teléfono</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                   id="phone" name="phone"
                                   value="{{ old('phone') }}">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="address" class="form-label">Dirección</label>
                            <input type="text" class="form-control @error('address') is-invalid @enderror"
                                   id="address" name="address"
                                   value="{{ old('address') }}">
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sección para cliente con plan --}}
            <div id="seccionConPlan" class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Datos del Plan</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="plan_id" class="form-label">Plan <span class="text-danger">*</span></label>
                            @php
                                $selectedPlan = $plans->firstWhere('id', old('plan_id'));
                                $selectedPlanLabel = $selectedPlan
                                    ? ($selectedPlan->name . ' - $' . number_format($selectedPlan->price, 2))
                                    : '';
                            @endphp
                            <input type="text"
                                   class="form-control @error('plan_id') is-invalid @enderror"
                                   id="plan_search"
                                   list="plan_list"
                                   placeholder="Buscar plan..."
                                   value="{{ $selectedPlanLabel }}">
                            <datalist id="plan_list">
                                @foreach($plans as $plan)
                                    <option value="{{ $plan->name }} - ${{ number_format($plan->price, 2) }}"
                                            data-id="{{ $plan->id }}"
                                            data-cowork="{{ $plan->cowork_hours }}"
                                            data-sala="{{ $plan->meeting_room_hours }}"
                                            data-impresiones="{{ $plan->prints_included }}"
                                            data-eventos="{{ $plan->events_included }}"
                                            data-precio="{{ $plan->price }}"
                                            data-ultra="{{ $plan->is_ultra_custom ? 1 : 0 }}">
                                    </option>
                                @endforeach
                            </datalist>
                            <input type="hidden" id="plan_id" name="plan_id" value="{{ old('plan_id') }}">
                            @error('plan_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="start_date" class="form-label">Fecha de Inicio <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('start_date') is-invalid @enderror"
                                   id="start_date" name="start_date"
                                   value="{{ old('start_date', date('Y-m-d')) }}">
                            @error('start_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Info del plan seleccionado --}}
                    <div id="planInfo" class="alert alert-info d-none">
                        <div class="row">
                            <div class="col-md-3">
                                <strong>Cowork:</strong> <span id="infoCowork">0</span> horas
                            </div>
                            <div class="col-md-3">
                                <strong>Sala:</strong> <span id="infoSala">0</span> horas
                            </div>
                            <div class="col-md-3">
                                <strong>Impresiones:</strong> <span id="infoImpresiones">0</span>
                            </div>
                            <div class="col-md-3">
                                <strong>Eventos:</strong> <span id="infoEventos">0</span>
                            </div>
                        </div>
                    </div>

                    <div id="ultraCustomFields" class="card border-dark bg-light mt-3 d-none">
                        <div class="card-body">
                            <h6 class="mb-3 text-dark">Cupos y precio personalizados (Plan Ultra)</h6>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="custom_cowork_hours" class="form-label">Horas Cowork</label>
                                    <input type="number" min="0" step="0.01"
                                           class="form-control @error('custom_cowork_hours') is-invalid @enderror"
                                           id="custom_cowork_hours" name="custom_cowork_hours"
                                           value="{{ old('custom_cowork_hours') }}">
                                    @error('custom_cowork_hours')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="custom_meeting_room_hours" class="form-label">Horas Sala Reuniones</label>
                                    <input type="number" min="0" step="0.01"
                                           class="form-control @error('custom_meeting_room_hours') is-invalid @enderror"
                                           id="custom_meeting_room_hours" name="custom_meeting_room_hours"
                                           value="{{ old('custom_meeting_room_hours') }}">
                                    @error('custom_meeting_room_hours')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="custom_monthly_price" class="form-label">Precio Personalizado</label>
                                    <input type="number" min="0" step="0.01"
                                           class="form-control @error('custom_monthly_price') is-invalid @enderror"
                                           id="custom_monthly_price" name="custom_monthly_price"
                                           value="{{ old('custom_monthly_price') }}">
                                    @error('custom_monthly_price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-1">
                                    <label for="custom_prints_included" class="form-label">Impresiones</label>
                                    <input type="number" min="0"
                                           class="form-control @error('custom_prints_included') is-invalid @enderror"
                                           id="custom_prints_included" name="custom_prints_included"
                                           value="{{ old('custom_prints_included') }}">
                                    @error('custom_prints_included')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-1">
                                    <label for="custom_events_included" class="form-label">Eventos Connecta</label>
                                    <input type="number" min="0"
                                           class="form-control @error('custom_events_included') is-invalid @enderror"
                                           id="custom_events_included" name="custom_events_included"
                                           value="{{ old('custom_events_included') }}">
                                    @error('custom_events_included')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sección para cliente invitado --}}
            <div id="seccionInvitado" class="card mb-4 d-none">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Datos del Invitado</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="service_type" class="form-label">Servicio <span class="text-danger">*</span></label>
                            <select class="form-select @error('service_type') is-invalid @enderror"
                                    id="service_type" name="service_type">
                                <option value="">Seleccione un servicio...</option>
                                <option value="cowork" {{ old('service_type') === 'cowork' ? 'selected' : '' }}>Cowork</option>
                                <option value="meeting_room" {{ old('service_type') === 'meeting_room' ? 'selected' : '' }}>Sala de Reuniones</option>
                            </select>
                            @error('service_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="master_client_id" class="form-label">Invitado por (opcional)</label>
                            @php
                                $selectedMaster = $clientesConPlan->firstWhere('id', old('master_client_id'));
                                $selectedMasterLabel = $selectedMaster
                                    ? ($selectedMaster->full_name
                                        . ' (' . $selectedMaster->document_number . ') - '
                                        . ($selectedMaster->currentSubscription->plan->name ?? 'Plan Activo'))
                                    : '';
                            @endphp
                            <input type="text"
                                   class="form-control @error('master_client_id') is-invalid @enderror"
                                   id="master_client_search"
                                   list="master_client_list"
                                   placeholder="Buscar cliente anfitrion..."
                                   value="{{ $selectedMasterLabel }}">
                            <datalist id="master_client_list">
                                @foreach($clientesConPlan as $clienteConPlan)
                                    <option value="{{ $clienteConPlan->full_name }} ({{ $clienteConPlan->document_number }}) - {{ $clienteConPlan->currentSubscription->plan->name ?? 'Plan Activo' }}"
                                            data-id="{{ $clienteConPlan->id }}">
                                    </option>
                                @endforeach
                            </datalist>
                            <input type="hidden" id="master_client_id" name="master_client_id" value="{{ old('master_client_id') }}">
                            @error('master_client_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">El cliente invitado usará las horas del plan de este cliente.</small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Botones --}}
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('admin.clientes.index') }}" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    Guardar Cliente
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tipoConPlan = document.getElementById('tipoConPlan');
    const tipoInvitado = document.getElementById('tipoInvitado');
    const seccionConPlan = document.getElementById('seccionConPlan');
    const seccionInvitado = document.getElementById('seccionInvitado');
    const planSearch = document.getElementById('plan_search');
    const planIdInput = document.getElementById('plan_id');
    const planList = document.getElementById('plan_list');
    const planInfo = document.getElementById('planInfo');
    const ultraFields = document.getElementById('ultraCustomFields');
    const customCoworkInput = document.getElementById('custom_cowork_hours');
    const customSalaInput = document.getElementById('custom_meeting_room_hours');
    const customPrintsInput = document.getElementById('custom_prints_included');
    const customEventosInput = document.getElementById('custom_events_included');
    const customPriceInput = document.getElementById('custom_monthly_price');
    const masterClientSearch = document.getElementById('master_client_search');
    const masterClientIdInput = document.getElementById('master_client_id');
    const masterClientList = document.getElementById('master_client_list');

    function toggleSecciones() {
        if (tipoConPlan.checked) {
            seccionConPlan.classList.remove('d-none');
            seccionInvitado.classList.add('d-none');
            // Hacer campos requeridos
            planSearch.required = true;
            planIdInput.required = true;
            document.getElementById('start_date').required = true;
            document.getElementById('service_type').required = false;
            masterClientSearch.required = false;
            masterClientIdInput.required = false;
            mostrarInfoPlan();
        } else {
            seccionConPlan.classList.add('d-none');
            seccionInvitado.classList.remove('d-none');
            // Hacer campos requeridos
            planSearch.required = false;
            planIdInput.required = false;
            document.getElementById('start_date').required = false;
            document.getElementById('service_type').required = true;
            masterClientSearch.required = false;
            masterClientIdInput.required = false;
            ultraFields.classList.add('d-none');
            customCoworkInput.required = false;
            customSalaInput.required = false;
            customPrintsInput.required = false;
            customEventosInput.required = false;
            customPriceInput.required = false;
        }
    }

    function mostrarInfoPlan() {
        const selected = Array.from(planList.options).find((option) => option.value === planSearch.value);
        if (selected && selected.dataset.id) {
            const isUltra = selected.dataset.ultra === '1';
            planIdInput.value = selected.dataset.id;
            ultraFields.classList.toggle('d-none', !isUltra);

            if (isUltra) {
                if (!customCoworkInput.value) customCoworkInput.value = selected.dataset.cowork || 0;
                if (!customSalaInput.value) customSalaInput.value = selected.dataset.sala || 0;
                if (!customPrintsInput.value) customPrintsInput.value = selected.dataset.impresiones || 0;
                if (!customEventosInput.value) customEventosInput.value = selected.dataset.eventos || 0;
                if (!customPriceInput.value) customPriceInput.value = parseFloat(selected.dataset.precio || 0).toFixed(2);
            }

            customCoworkInput.required = isUltra;
            customSalaInput.required = isUltra;
            customPrintsInput.required = isUltra;
            customEventosInput.required = isUltra;
            customPriceInput.required = isUltra;

            document.getElementById('infoCowork').textContent = isUltra ? (customCoworkInput.value || 0) : (selected.dataset.cowork || 0);
            document.getElementById('infoSala').textContent = isUltra ? (customSalaInput.value || 0) : (selected.dataset.sala || 0);
            document.getElementById('infoImpresiones').textContent = isUltra ? (customPrintsInput.value || 0) : (selected.dataset.impresiones || 0);
            document.getElementById('infoEventos').textContent = isUltra ? (customEventosInput.value || 0) : (selected.dataset.eventos || 0);
            planInfo.classList.remove('d-none');
        } else {
            planIdInput.value = '';
            planInfo.classList.add('d-none');
            ultraFields.classList.add('d-none');
            customCoworkInput.required = false;
            customSalaInput.required = false;
            customPrintsInput.required = false;
            customEventosInput.required = false;
            customPriceInput.required = false;
        }
    }

    function syncMasterClient() {
        const selected = Array.from(masterClientList.options).find(
            (option) => option.value === masterClientSearch.value
        );
        masterClientIdInput.value = selected && selected.dataset.id ? selected.dataset.id : '';
    }

    tipoConPlan.addEventListener('change', toggleSecciones);
    tipoInvitado.addEventListener('change', toggleSecciones);
    planSearch.addEventListener('input', mostrarInfoPlan);
    planSearch.addEventListener('change', mostrarInfoPlan);
    customCoworkInput.addEventListener('input', mostrarInfoPlan);
    customSalaInput.addEventListener('input', mostrarInfoPlan);
    customPrintsInput.addEventListener('input', mostrarInfoPlan);
    customEventosInput.addEventListener('input', mostrarInfoPlan);
    customPriceInput.addEventListener('input', mostrarInfoPlan);
    masterClientSearch.addEventListener('input', syncMasterClient);
    masterClientSearch.addEventListener('change', syncMasterClient);

    // Inicializar
    toggleSecciones();
    mostrarInfoPlan();
    syncMasterClient();
});
</script>
@endpush
