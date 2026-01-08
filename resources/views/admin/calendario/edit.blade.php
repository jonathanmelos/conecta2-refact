@extends('layouts.conecta')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-warning text-dark py-3">
                <div class="d-flex align-items-center">
                    <i class="bi bi-calendar-check fs-3 me-3"></i>
                    <div>
                        <h4 class="mb-0 fw-bold">Editar Evento</h4>
                        <small class="opacity-75">{{ $evento->title }}</small>
                    </div>
                </div>
            </div>

            <div class="card-body p-4">
                <form action="{{ route('admin.calendario.update', $evento) }}" method="POST">
                    @csrf
                    @method('PUT')

                    {{-- Titulo --}}
                    <div class="mb-4">
                        <label for="title" class="form-label fw-semibold">
                            <i class="bi bi-card-heading me-1"></i> Titulo del Evento *
                        </label>
                        <input type="text" class="form-control form-control-lg @error('title') is-invalid @enderror"
                               id="title" name="title" value="{{ old('title', $evento->title) }}"
                               placeholder="Ej: Reunion de networking" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Descripcion --}}
                    <div class="mb-4">
                        <label for="description" class="form-label fw-semibold">
                            <i class="bi bi-text-paragraph me-1"></i> Descripcion
                        </label>
                        <textarea class="form-control @error('description') is-invalid @enderror"
                                  id="description" name="description" rows="3"
                                  placeholder="Descripcion detallada del evento...">{{ old('description', $evento->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        {{-- Fecha inicio --}}
                        <div class="col-md-6 mb-4">
                            <label for="start_date" class="form-label fw-semibold">
                                <i class="bi bi-calendar-event me-1"></i> Fecha de Inicio *
                            </label>
                            <input type="date" class="form-control @error('start_date') is-invalid @enderror"
                                   id="start_date" name="start_date"
                                   value="{{ old('start_date', $evento->start_date->format('Y-m-d')) }}" required>
                            @error('start_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Fecha fin --}}
                        <div class="col-md-6 mb-4">
                            <label for="end_date" class="form-label fw-semibold">
                                <i class="bi bi-calendar-event me-1"></i> Fecha de Fin
                            </label>
                            <input type="date" class="form-control @error('end_date') is-invalid @enderror"
                                   id="end_date" name="end_date"
                                   value="{{ old('end_date', $evento->end_date ? $evento->end_date->format('Y-m-d') : '') }}">
                            <small class="text-muted">Dejar vacio si es el mismo dia</small>
                            @error('end_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Todo el dia --}}
                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="all_day" name="all_day"
                                   {{ old('all_day', $evento->all_day) ? 'checked' : '' }} onchange="toggleTimeFields()">
                            <label class="form-check-label fw-semibold" for="all_day">
                                <i class="bi bi-sun me-1"></i> Todo el dia
                            </label>
                        </div>
                    </div>

                    <div class="row" id="timeFields">
                        {{-- Hora inicio --}}
                        <div class="col-md-6 mb-4">
                            <label for="start_time" class="form-label fw-semibold">
                                <i class="bi bi-clock me-1"></i> Hora de Inicio
                            </label>
                            <input type="time" class="form-control @error('start_time') is-invalid @enderror"
                                   id="start_time" name="start_time"
                                   value="{{ old('start_time', $evento->start_time) }}">
                            @error('start_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Hora fin --}}
                        <div class="col-md-6 mb-4">
                            <label for="end_time" class="form-label fw-semibold">
                                <i class="bi bi-clock me-1"></i> Hora de Fin
                            </label>
                            <input type="time" class="form-control @error('end_time') is-invalid @enderror"
                                   id="end_time" name="end_time"
                                   value="{{ old('end_time', $evento->end_time) }}">
                            @error('end_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        {{-- Ubicacion --}}
                        <div class="col-md-6 mb-4">
                            <label for="location" class="form-label fw-semibold">
                                <i class="bi bi-geo-alt me-1"></i> Ubicacion
                            </label>
                            <input type="text" class="form-control @error('location') is-invalid @enderror"
                                   id="location" name="location"
                                   value="{{ old('location', $evento->location) }}"
                                   placeholder="Ej: Sala de reuniones A">
                            @error('location')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Tipo/Color --}}
                        <div class="col-md-6 mb-4">
                            <label for="color" class="form-label fw-semibold">
                                <i class="bi bi-palette me-1"></i> Tipo de Evento
                            </label>
                            <select class="form-select @error('color') is-invalid @enderror" id="color" name="color">
                                <option value="#3788d8" {{ old('color', $evento->color) == '#3788d8' ? 'selected' : '' }}>Reunion</option>
                                <option value="#28a745" {{ old('color', $evento->color) == '#28a745' ? 'selected' : '' }}>Evento Coworking</option>
                                <option value="#ffc107" {{ old('color', $evento->color) == '#ffc107' ? 'selected' : '' }}>Capacitacion</option>
                                <option value="#dc3545" {{ old('color', $evento->color) == '#dc3545' ? 'selected' : '' }}>Importante</option>
                                <option value="#6f42c1" {{ old('color', $evento->color) == '#6f42c1' ? 'selected' : '' }}>Networking</option>
                                <option value="#17a2b8" {{ old('color', $evento->color) == '#17a2b8' ? 'selected' : '' }}>Otro</option>
                            </select>
                            @error('color')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Estado --}}
                    <div class="mb-4">
                        <label for="status" class="form-label fw-semibold">
                            <i class="bi bi-flag me-1"></i> Estado
                        </label>
                        <select class="form-select @error('status') is-invalid @enderror" id="status" name="status">
                            <option value="scheduled" {{ old('status', $evento->status) == 'scheduled' ? 'selected' : '' }}>Programado</option>
                            <option value="in_progress" {{ old('status', $evento->status) == 'in_progress' ? 'selected' : '' }}>En Progreso</option>
                            <option value="completed" {{ old('status', $evento->status) == 'completed' ? 'selected' : '' }}>Completado</option>
                            <option value="cancelled" {{ old('status', $evento->status) == 'cancelled' ? 'selected' : '' }}>Cancelado</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Cliente asociado --}}
                    <div class="mb-4">
                        <label for="client_id" class="form-label fw-semibold">
                            <i class="bi bi-person me-1"></i> Cliente Asociado (Opcional)
                        </label>
                        <select class="form-select @error('client_id') is-invalid @enderror" id="client_id" name="client_id">
                            <option value="">-- Sin cliente asociado --</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" {{ old('client_id', $evento->client_id) == $client->id ? 'selected' : '' }}>
                                    {{ $client->full_name }} - {{ $client->document_number }}
                                </option>
                            @endforeach
                        </select>
                        @error('client_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Notas --}}
                    <div class="mb-4">
                        <label for="notes" class="form-label fw-semibold">
                            <i class="bi bi-sticky me-1"></i> Notas Adicionales
                        </label>
                        <textarea class="form-control @error('notes') is-invalid @enderror"
                                  id="notes" name="notes" rows="2"
                                  placeholder="Notas internas...">{{ old('notes', $evento->notes) }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Botones --}}
                    <div class="d-flex justify-content-between pt-3 border-top">
                        <a href="{{ route('admin.calendario.show', $evento) }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-check-circle me-1"></i> Actualizar Evento
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function toggleTimeFields() {
        const allDay = document.getElementById('all_day').checked;
        const timeFields = document.getElementById('timeFields');
        timeFields.style.display = allDay ? 'none' : 'flex';
    }

    // Ejecutar al cargar
    document.addEventListener('DOMContentLoaded', toggleTimeFields);
</script>
@endpush
