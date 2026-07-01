@extends('layouts.conecta')

@section('content')
<div class="row">
    <div class="col-12 col-lg-10 mx-auto">
        <h2 class="mb-4">Modificar Plan</h2>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.planes.update', $plan) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Plan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name', $plan->name) }}">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3" id="price_col">
                            <label for="price" class="form-label">Precio <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control @error('price') is-invalid @enderror"
                                   id="price" name="price" value="{{ old('price', $plan->price) }}">
                            @error('price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   id="is_pilot" name="is_pilot" value="1"
                                   {{ old('is_pilot', $plan->is_pilot) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="is_pilot">
                                Plan piloto (sin limite de horas)
                            </label>
                        </div>
                        <small class="text-muted d-block">
                            Registra horas usadas en cowork y sala sin bloquear por horas contratadas.
                        </small>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   id="is_ultra_custom" name="is_ultra_custom" value="1"
                                   {{ old('is_ultra_custom', $plan->is_ultra_custom) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="is_ultra_custom">
                                Plan ultra personalizado
                            </label>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb-3" id="cowork_hours_col">
                            <label for="cowork_hours" class="form-label">Horas Cowork <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('cowork_hours') is-invalid @enderror"
                                   id="cowork_hours" name="cowork_hours" value="{{ old('cowork_hours', $plan->cowork_hours) }}">
                            @error('cowork_hours')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3 mb-3" id="meeting_room_hours_col">
                            <label for="meeting_room_hours" class="form-label">Horas Sala Reuniones <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('meeting_room_hours') is-invalid @enderror"
                                   id="meeting_room_hours" name="meeting_room_hours" value="{{ old('meeting_room_hours', $plan->meeting_room_hours) }}">
                            @error('meeting_room_hours')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3 mb-3" id="prints_included_col">
                            <label for="prints_included" class="form-label">Impresiones <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('prints_included') is-invalid @enderror"
                                   id="prints_included" name="prints_included" value="{{ old('prints_included', $plan->prints_included) }}">
                            @error('prints_included')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3 mb-3" id="events_included_col">
                            <label for="events_included" class="form-label">Evento <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('events_included') is-invalid @enderror"
                                   id="events_included" name="events_included" value="{{ old('events_included', $plan->events_included) }}">
                            @error('events_included')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="setup_fee" class="form-label">Cuota de Inscripcion</label>
                            <input type="number" step="0.01" class="form-control @error('setup_fee') is-invalid @enderror"
                                   id="setup_fee" name="setup_fee" value="{{ old('setup_fee', $plan->setup_fee) }}">
                            @error('setup_fee')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="deposit_required" class="form-label">Deposito</label>
                            <input type="number" step="0.01" class="form-control @error('deposit_required') is-invalid @enderror"
                                   id="deposit_required" name="deposit_required" value="{{ old('deposit_required', $plan->deposit_required) }}">
                            @error('deposit_required')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="operational_cost" class="form-label">Costo Operativo</label>
                            <input type="number" step="0.01" class="form-control @error('operational_cost') is-invalid @enderror"
                                   id="operational_cost" name="operational_cost" value="{{ old('operational_cost', $plan->operational_cost) }}">
                            @error('operational_cost')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Descripcion</label>
                        <textarea class="form-control @error('description') is-invalid @enderror"
                                  id="description" name="description" rows="3">{{ old('description', $plan->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.planes.index') }}" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const pilotToggle = document.getElementById('is_pilot');
    const ultraCustomToggle = document.getElementById('is_ultra_custom');
    const coworkInput = document.getElementById('cowork_hours');
    const salaInput = document.getElementById('meeting_room_hours');
    const coworkCol = document.getElementById('cowork_hours_col');
    const salaCol = document.getElementById('meeting_room_hours_col');
    const printsCol = document.getElementById('prints_included_col');
    const eventsCol = document.getElementById('events_included_col');
    const priceCol = document.getElementById('price_col');

    function syncPilotState() {
        const isPilot = pilotToggle.checked;
        if (isPilot) {
            coworkInput.value = 0;
            salaInput.value = 0;
        }
        coworkInput.readOnly = isPilot;
        salaInput.readOnly = isPilot;
        coworkInput.classList.toggle('bg-light', isPilot);
        salaInput.classList.toggle('bg-light', isPilot);
    }

    function syncUltraCustomState() {
        const isUltraCustom = ultraCustomToggle.checked;
        coworkCol.classList.toggle('d-none', isUltraCustom);
        salaCol.classList.toggle('d-none', isUltraCustom);
        printsCol.classList.toggle('d-none', isUltraCustom);
        eventsCol.classList.toggle('d-none', isUltraCustom);
        priceCol.classList.toggle('d-none', isUltraCustom);
    }

    pilotToggle.addEventListener('change', syncPilotState);
    ultraCustomToggle.addEventListener('change', syncUltraCustomState);
    syncPilotState();
    syncUltraCustomState();
});
</script>
@endsection
