@extends('layouts.conecta')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Modificar Cliente</h2>
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

        <form action="{{ route('admin.clientes.update', $client) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="card">
                <div class="card-header bg-warning">
                    <h5 class="mb-0">Datos del Cliente</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        {{-- Documento (NO editable) --}}
                        <div class="col-md-4 mb-3">
                            <label for="document_number" class="form-label">Documento</label>
                            <input type="text" class="form-control"
                                   id="document_number"
                                   value="{{ $client->document_number }}"
                                   disabled>
                            <small class="text-muted">El documento no puede ser modificado.</small>
                        </div>

                        {{-- Nombre --}}
                        <div class="col-md-4 mb-3">
                            <label for="first_name" class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('first_name') is-invalid @enderror"
                                   id="first_name" name="first_name"
                                   value="{{ old('first_name', $client->first_name) }}" required>
                            @error('first_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Apellido --}}
                        <div class="col-md-4 mb-3">
                            <label for="last_name" class="form-label">Apellido</label>
                            <input type="text" class="form-control @error('last_name') is-invalid @enderror"
                                   id="last_name" name="last_name"
                                   value="{{ old('last_name', $client->last_name) }}">
                            @error('last_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        {{-- Correo --}}
                        <div class="col-md-4 mb-3">
                            <label for="email" class="form-label">Correo Electrónico</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                   id="email" name="email"
                                   value="{{ old('email', $client->email) }}">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Teléfono --}}
                        <div class="col-md-4 mb-3">
                            <label for="phone" class="form-label">Teléfono</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                   id="phone" name="phone"
                                   value="{{ old('phone', $client->phone) }}">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Dirección --}}
                        <div class="col-md-4 mb-3">
                            <label for="address" class="form-label">Dirección</label>
                            <input type="text" class="form-control @error('address') is-invalid @enderror"
                                   id="address" name="address"
                                   value="{{ old('address', $client->address) }}">
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Info del estado actual --}}
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="alert alert-secondary">
                                <div class="row">
                                    <div class="col-md-4">
                                        <strong>Estado:</strong>
                                        @if($client->client_status === 'active')
                                            <span class="badge bg-success">Activo</span>
                                        @else
                                            <span class="badge bg-danger">{{ ucfirst($client->client_status) }}</span>
                                        @endif
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Suscripción:</strong>
                                        @if($client->subscription_status === 'active')
                                            <span class="badge bg-success">Activa</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Sin Plan</span>
                                        @endif
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Registrado:</strong>
                                        {{ $client->created_at->format('d/m/Y') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Botones --}}
            <div class="d-flex justify-content-between mt-4">
                <div>
                    @if($client->subscription_status === 'active' && $client->currentSubscription)
                        <a href="{{ route('admin.clientes.plan', $client) }}" class="btn btn-success">
                            Ver Plan del Cliente
                        </a>
                    @else
                        <a href="{{ route('admin.clientes.suscribirForm', $client) }}" class="btn btn-primary">
                            Suscribir a un Plan
                        </a>
                    @endif
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.clientes.index') }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-warning">
                        Guardar Cambios
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
