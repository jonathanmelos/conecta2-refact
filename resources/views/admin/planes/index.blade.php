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
                                    <td>
                                        {{ $plan->name }}
                                        @if($plan->is_pilot)
                                            <span class="badge bg-info text-dark ms-1">Piloto</span>
                                        @endif
                                        @if($plan->is_ultra_custom)
                                            <span class="badge bg-dark ms-1">Ultra personalizado</span>
                                            <button type="button"
                                                    class="btn btn-link btn-sm p-0 ms-2 align-baseline"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#ultraChildrenModal{{ $plan->id }}">
                                                ({{ $plan->ultra_children_count }} personalizados)
                                            </button>
                                        @endif
                                    </td>
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

@foreach($plans as $plan)
    @if($plan->is_ultra_custom)
        <div class="modal fade" id="ultraChildrenModal{{ $plan->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header bg-dark text-white">
                        <h5 class="modal-title">
                            Planes Personalizados Hijos - {{ $plan->name }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3">
                            Total de personalizados: <strong>{{ $plan->ultra_children_count }}</strong>
                        </p>

                        @if($plan->subscriptions->isEmpty())
                            <div class="alert alert-secondary mb-0">
                                No hay planes personalizados hijos para este plan.
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm table-striped align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Cliente</th>
                                            <th>Precio</th>
                                            <th>Cowork</th>
                                            <th>Sala</th>
                                            <th>Impresiones</th>
                                            <th>Eventos</th>
                                            <th>Inicio</th>
                                            <th>Fin</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($plan->subscriptions as $subscription)
                                            <tr>
                                                <td>{{ $subscription->client->full_name ?? 'N/A' }}</td>
                                                <td>S/. {{ number_format($subscription->monthly_price, 2) }}</td>
                                                <td>{{ $subscription->custom_cowork_hours ?? $plan->cowork_hours }}</td>
                                                <td>{{ $subscription->custom_meeting_room_hours ?? $plan->meeting_room_hours }}</td>
                                                <td>{{ $subscription->custom_prints_included ?? $plan->prints_included }}</td>
                                                <td>{{ $subscription->custom_events_included ?? $plan->events_included }}</td>
                                                <td>{{ optional($subscription->start_date)->format('d/m/Y') }}</td>
                                                <td>{{ optional($subscription->end_date)->format('d/m/Y') }}</td>
                                                <td>
                                                    @if($subscription->status === 'active')
                                                        <span class="badge bg-success">Activa</span>
                                                    @elseif($subscription->status === 'inactive')
                                                        <span class="badge bg-secondary">Inactiva</span>
                                                    @else
                                                        <span class="badge bg-warning text-dark">{{ ucfirst($subscription->status) }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endforeach
@endsection
