@extends('layouts.conecta')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header py-3" style="background-color: {{ $evento->color ?? '#3788d8' }}; color: white;">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-calendar-event fs-3 me-3"></i>
                        <div>
                            <h4 class="mb-0 fw-bold">{{ $evento->title }}</h4>
                            <small class="opacity-75">{{ $evento->formatted_date }}</small>
                        </div>
                    </div>
                    <div>
                        <a href="{{ route('admin.calendario.edit', $evento) }}" class="btn btn-light btn-sm me-1">
                            <i class="bi bi-pencil"></i> Editar
                        </a>
                        <form action="{{ route('admin.calendario.destroy', $evento) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('¿Esta seguro de eliminar este evento?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="card-body p-4">
                {{-- Informacion principal --}}
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="text-muted small">FECHA</label>
                            <div class="fw-semibold fs-5">
                                <i class="bi bi-calendar3 me-2 text-primary"></i>
                                {{ $evento->start_date->format('d/m/Y') }}
                                @if($evento->end_date && !$evento->start_date->eq($evento->end_date))
                                    - {{ $evento->end_date->format('d/m/Y') }}
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="text-muted small">HORA</label>
                            <div class="fw-semibold fs-5">
                                <i class="bi bi-clock me-2 text-primary"></i>
                                @if($evento->all_day)
                                    Todo el dia
                                @else
                                    {{ $evento->start_time }}
                                    @if($evento->end_time)
                                        - {{ $evento->end_time }}
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                @if($evento->location)
                    <div class="mb-4">
                        <label class="text-muted small">UBICACION</label>
                        <div class="fw-semibold fs-5">
                            <i class="bi bi-geo-alt me-2 text-danger"></i>
                            {{ $evento->location }}
                        </div>
                    </div>
                @endif

                @if($evento->description)
                    <div class="mb-4">
                        <label class="text-muted small">DESCRIPCION</label>
                        <div class="bg-light rounded p-3 mt-1">
                            {{ $evento->description }}
                        </div>
                    </div>
                @endif

                @if($evento->client)
                    <div class="mb-4">
                        <label class="text-muted small">CLIENTE ASOCIADO</label>
                        <div class="d-flex align-items-center mt-1">
                            <i class="bi bi-person-circle fs-3 me-2 text-primary"></i>
                            <div>
                                <div class="fw-semibold">{{ $evento->client->full_name }}</div>
                                <small class="text-muted">{{ $evento->client->document_number }}</small>
                            </div>
                        </div>
                    </div>
                @endif

                @if($evento->notes)
                    <div class="mb-4">
                        <label class="text-muted small">NOTAS</label>
                        <div class="border-start border-warning border-3 ps-3 mt-1 text-muted">
                            {{ $evento->notes }}
                        </div>
                    </div>
                @endif

                {{-- Metadatos --}}
                <div class="border-top pt-3 mt-4">
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted">
                                <i class="bi bi-person me-1"></i>
                                Creado por: {{ $evento->user->name ?? 'Sistema' }}
                            </small>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <small class="text-muted">
                                <i class="bi bi-clock-history me-1"></i>
                                {{ $evento->created_at->format('d/m/Y H:i') }}
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer bg-transparent">
                <a href="{{ route('admin.calendario.index', ['year' => $evento->start_date->year, 'month' => $evento->start_date->month]) }}"
                   class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Volver al Calendario
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
