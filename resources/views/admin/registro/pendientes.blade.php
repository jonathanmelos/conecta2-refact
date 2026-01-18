@extends('layouts.conecta')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">Registros Pendientes</h2>
                <p class="text-muted mb-0">Lista de registros activos ordenados por fecha de inicio.</p>
            </div>
            <div class="text-end">
                <span class="badge bg-danger">
                    Total activos: {{ collect($groups)->sum(fn($group) => $group['records']->count()) }}
                </span>
            </div>
        </div>

        @foreach($groups as $groupKey => $group)
            <div class="card mb-4">
                <div class="card-header bg-light d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <h5 class="mb-0">{{ $group['label'] }}</h5>
                        <span class="badge bg-secondary">{{ $group['records']->count() }}</span>
                        <small class="text-muted">{{ $group['hint'] }}</small>
                    </div>
                    @if($group['records']->isNotEmpty())
                        <form method="POST"
                              action="{{ route('admin.registro.pendientes.delete') }}"
                              onsubmit="return confirm('Confirma eliminar todos los registros de esta seccion?')">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="group" value="{{ $groupKey }}">
                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                <i class="bi bi-trash"></i> Eliminar todos
                            </button>
                        </form>
                    @endif
                </div>
                <div class="card-body p-0">
                    @if($group['records']->isEmpty())
                        <div class="text-center text-muted py-4">
                            No hay registros en esta categoria.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha de inicio</th>
                                        <th>Cliente</th>
                                        <th>Servicio</th>
                                        <th class="text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($group['records'] as $record)
                                        @php
                                            $clientName = $record->client ? $record->client->full_name : 'Sin cliente';
                                            $serviceLabel = $record->service_type === 'cowork'
                                                ? 'Cowork'
                                                : ($record->service_type === 'meeting_room' ? 'Sala de Reuniones' : 'Impresion');
                                            $planLabel = $record->subscription && $record->subscription->plan
                                                ? $record->subscription->plan->name . ($record->subscription->plan->is_pilot ? ' (Piloto)' : '')
                                                : 'Sin plan';
                                            $daysOpen = $record->check_in->diffInDays(now());
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $record->check_in->format('d/m/Y') }}</div>
                                                <small class="text-muted">{{ $record->check_in->format('H:i') }} ({{ $daysOpen }} dias)</small>
                                            </td>
                                            <td>
                                                <div class="fw-semibold">
                                                    {{ $clientName }}
                                                    @include('components.badges.invitado', ['client' => $record->client])
                                                </div>
                                                <small class="text-muted">{{ $planLabel }}</small>
                                            </td>
                                            <td>{{ $serviceLabel }}</td>
                                            <td class="text-end">
                                                <button type="button"
                                                        class="btn btn-outline-primary btn-sm me-1"
                                                        onclick='openEditModal({{ $record->id }}, "{{ $record->service_type }}", "{{ addslashes($clientName) }}", "{{ $record->check_in->format("Y-m-d\\TH:i") }}", "{{ $record->check_out ? $record->check_out->format("Y-m-d\\TH:i") : "" }}", {{ $record->subscription_id ?? "null" }}, "{{ addslashes($planLabel) }}")'>
                                                    <i class="bi bi-pencil"></i> Modificar
                                                </button>
                                                <form method="POST"
                                                      action="{{ route('admin.clientes.eliminarRegistro', $record) }}"
                                                      class="d-inline"
                                                      onsubmit="return confirm('Confirma eliminar este registro?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                                        <i class="bi bi-trash"></i> Eliminar
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>

@include('admin.registro.modals.edit-record')
@endsection

@push('scripts')
@include('admin.registro.scripts.modals')
@endpush
