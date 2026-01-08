@extends('layouts.conecta')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Planes</h2>
            <a href="{{ route('admin.planes.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Crear nuevo plan
            </a>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Plan</th>
                                <th>Horas Cowork</th>
                                <th>Horas Sala Reuniones</th>
                                <th>Impresiones</th>
                                <th>Evento</th>
                                <th>Precio</th>
                                <th>Estado</th>
                                <th class="text-center" style="min-width: 200px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($plans as $plan)
                                <tr>
                                    <td>{{ $plan->name }}</td>
                                    <td>{{ $plan->cowork_hours }}</td>
                                    <td>{{ $plan->meeting_room_hours }}</td>
                                    <td>{{ $plan->prints_included }}</td>
                                    <td>{{ $plan->events_included }}</td>
                                    <td>S/. {{ number_format($plan->price, 2) }}</td>
                                    <td>
                                        @if($plan->is_active)
                                            <span class="badge bg-success">Activo</span>
                                        @else
                                            <span class="badge bg-secondary">Inactivo</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('admin.planes.edit', $plan) }}" class="btn btn-warning btn-sm">
                                            Modificar
                                        </a>
                                        @if($plan->is_active)
                                            <form action="{{ route('admin.planes.destroy', $plan) }}" method="POST" class="d-inline" onsubmit="return confirm('Confirma desactivar este plan?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    Eliminar
                                                </button>
                                            </form>
                                        @else
                                            <button type="button" class="btn btn-outline-secondary btn-sm" disabled>
                                                Eliminado
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        No hay planes registrados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
