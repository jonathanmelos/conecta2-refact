@extends('layouts.conecta')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Suscribir Cliente a un Plan</h2>
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

        {{-- Datos del cliente --}}
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">Datos del Cliente</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <strong>Documento:</strong><br>
                        {{ $client->document_number }}
                    </div>
                    <div class="col-md-4">
                        <strong>Nombre:</strong><br>
                        {{ $client->full_name }}
                    </div>
                    <div class="col-md-4">
                        <strong>Teléfono:</strong><br>
                        {{ $client->phone ?? '-' }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Formulario de suscripción --}}
        <form action="{{ route('admin.clientes.suscribir', $client) }}" method="POST">
            @csrf

            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Seleccionar Plan</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="plan_id" class="form-label">Plan <span class="text-danger">*</span></label>
                            <select class="form-select @error('plan_id') is-invalid @enderror"
                                    id="plan_id" name="plan_id" required>
                                <option value="">Seleccione un plan...</option>
                                @foreach($plans as $plan)
                                    <option value="{{ $plan->id }}"
                                            {{ old('plan_id') == $plan->id ? 'selected' : '' }}
                                            data-cowork="{{ $plan->cowork_hours }}"
                                            data-sala="{{ $plan->meeting_room_hours }}"
                                            data-impresiones="{{ $plan->prints_included }}"
                                            data-eventos="{{ $plan->events_included }}"
                                            data-precio="{{ $plan->price }}">
                                        {{ $plan->name }} - ${{ number_format($plan->price, 2) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('plan_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="start_date" class="form-label">Fecha de Inicio <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('start_date') is-invalid @enderror"
                                   id="start_date" name="start_date"
                                   value="{{ old('start_date', date('Y-m-d')) }}" required>
                            @error('start_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">El plan durará 1 mes desde esta fecha.</small>
                        </div>
                    </div>

                    {{-- Info del plan seleccionado --}}
                    <div id="planInfo" class="alert alert-info d-none">
                        <h6>Detalles del Plan:</h6>
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
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Precio:</strong> $<span id="infoPrecio">0.00</span>
                            </div>
                            <div class="col-md-6">
                                <strong>Fecha fin estimada:</strong> <span id="infoFechaFin">-</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Botones --}}
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('admin.clientes.index') }}" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    Suscribir Cliente
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const planSelect = document.getElementById('plan_id');
    const startDateInput = document.getElementById('start_date');
    const planInfo = document.getElementById('planInfo');

    function mostrarInfoPlan() {
        const selected = planSelect.options[planSelect.selectedIndex];
        if (selected.value) {
            document.getElementById('infoCowork').textContent = selected.dataset.cowork;
            document.getElementById('infoSala').textContent = selected.dataset.sala;
            document.getElementById('infoImpresiones').textContent = selected.dataset.impresiones;
            document.getElementById('infoEventos').textContent = selected.dataset.eventos;
            document.getElementById('infoPrecio').textContent = parseFloat(selected.dataset.precio).toFixed(2);
            calcularFechaFin();
            planInfo.classList.remove('d-none');
        } else {
            planInfo.classList.add('d-none');
        }
    }

    function calcularFechaFin() {
        const startDate = new Date(startDateInput.value);
        if (!isNaN(startDate.getTime())) {
            const endDate = new Date(startDate);
            endDate.setMonth(endDate.getMonth() + 1);
            document.getElementById('infoFechaFin').textContent = endDate.toLocaleDateString('es-EC', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
        }
    }

    planSelect.addEventListener('change', mostrarInfoPlan);
    startDateInput.addEventListener('change', calcularFechaFin);

    // Inicializar
    mostrarInfoPlan();
});
</script>
@endpush
