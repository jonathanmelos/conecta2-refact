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
                        <div class="col-md-6 mb-3">
                            <label for="price" class="form-label">Precio <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control @error('price') is-invalid @enderror"
                                   id="price" name="price" value="{{ old('price', $plan->price) }}">
                            @error('price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="cowork_hours" class="form-label">Horas Cowork <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('cowork_hours') is-invalid @enderror"
                                   id="cowork_hours" name="cowork_hours" value="{{ old('cowork_hours', $plan->cowork_hours) }}">
                            @error('cowork_hours')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="meeting_room_hours" class="form-label">Horas Sala Reuniones <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('meeting_room_hours') is-invalid @enderror"
                                   id="meeting_room_hours" name="meeting_room_hours" value="{{ old('meeting_room_hours', $plan->meeting_room_hours) }}">
                            @error('meeting_room_hours')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="prints_included" class="form-label">Impresiones <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('prints_included') is-invalid @enderror"
                                   id="prints_included" name="prints_included" value="{{ old('prints_included', $plan->prints_included) }}">
                            @error('prints_included')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3 mb-3">
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
@endsection
