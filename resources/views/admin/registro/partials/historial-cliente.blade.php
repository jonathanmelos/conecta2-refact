{{-- Historial de registros del cliente hoy --}}
@if($registros->count() > 0)
    <div class="mt-2" style="max-width: 100%;">
        <button class="btn btn-sm btn-outline-info w-100" 
                type="button"
                onclick="toggleHistorial({{ $cliente->id }})"
                aria-expanded="false">
            <i class="bi bi-clock-history"></i> Ver historial del día ({{ $registros->count() }} registros)
        </button>
        
        <div class="collapse mt-2" id="historial-{{ $cliente->id }}">
            <div class="card shadow-sm" style="max-width: 100%; width: 100%;">
                <div class="card-header bg-gradient text-white py-2" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <small>
                        <strong><i class="bi bi-calendar-day"></i> Registros de hoy - {{ $cliente->full_name }}</strong>
                        @include('components.badges.invitado', ['client' => $cliente])
                    </small>
                </div>
                <div class="card-body p-0" style="overflow-x: auto; max-height: 400px;">
                    <table class="table table-sm table-hover mb-0" style="font-size: 0.75rem; min-width: 650px;">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="text-center" style="width: 40px;">ID</th>
                                <th style="width: 80px;">Servicio</th>
                                <th style="width: 60px;">Entrada</th>
                                <th style="width: 60px;">Salida</th>
                                <th style="width: 90px;">Duración / Cantidad</th>
                                <th style="width: 70px;">Estado</th>
                                <th style="width: 180px;">Plan Usado</th>
                                <th class="text-center" style="width: 90px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($registros->sortByDesc('id') as $index => $reg)
                                @php
                                    $esRegistroAntiguo = $reg->status === 'in_progress' && $reg->check_in->format('Y-m-d') < date('Y-m-d');
                                    
                                    // ⭐ Preparar datos del plan de forma segura
                                    $planName = '';
                                    if ($reg->subscription && $reg->subscription->plan) {
                                        $planName = $reg->subscription->plan->name
                                            . ($reg->subscription->plan->is_pilot ? ' (Piloto)' : '');
                                    }
                                @endphp
                                <tr class="{{ $reg->status === 'in_progress' ? ($esRegistroAntiguo ? 'table-danger' : 'table-warning') : '' }}" 
                                    style="font-size: 0.75rem; {{ $esRegistroAntiguo ? 'border-left: 4px solid #dc3545;' : '' }}">
                                    <td class="text-center">
                                        <span class="badge {{ $esRegistroAntiguo ? 'bg-danger' : 'bg-secondary' }}" style="font-size: 0.65rem;">#{{ $reg->id }}</span>
                                    </td>
                                    <td>
                                        @if($reg->service_type === 'cowork')
                                            <span class="badge bg-primary" style="font-size: 0.65rem;">
                                                <i class="bi bi-laptop"></i> Cowork
                                            </span>
                                        @elseif($reg->service_type === 'meeting_room')
                                            <span class="badge bg-info" style="font-size: 0.65rem;">
                                                <i class="bi bi-door-open"></i> Sala
                                            </span>
                                        @else
                                            <span class="badge bg-secondary" style="font-size: 0.65rem;">
                                                <i class="bi bi-printer"></i> Impresión
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($esRegistroAntiguo)
                                            <small class="text-danger">
                                                <i class="bi bi-exclamation-triangle-fill"></i>
                                                <strong>{{ $reg->check_in->format('d/m') }}</strong><br>
                                                {{ $reg->check_in->format('H:i') }}
                                            </small>
                                        @else
                                            <small><i class="bi bi-box-arrow-in-right text-success"></i> {{ $reg->check_in->format('H:i') }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($reg->check_out)
                                            <small><i class="bi bi-box-arrow-left text-danger"></i> {{ $reg->check_out->format('H:i') }}</small>
                                        @else
                                            <span class="badge {{ $esRegistroAntiguo ? 'bg-danger pulse-danger' : 'bg-success' }}" style="font-size: 0.6rem;">
                                                @if($esRegistroAntiguo)
                                                    <i class="bi bi-exclamation-triangle"></i>
                                                @endif
                                                En uso
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($reg->service_type === 'print')
                                            <span class="badge bg-primary" style="font-size: 0.85rem;">
                                                <i class="bi bi-file-earmark-text"></i> 
                                                {{ $reg->quantity ?? 0 }}
                                            </span>
                                        @else
                                            @if($reg->duration_in_hours)
                                                <span class="badge bg-dark" style="font-size: 0.85rem;">
                                                    <i class="bi bi-hourglass-split"></i> 
                                                    {{ number_format($reg->duration_in_hours, 2) }}h
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td>
                                        @if($reg->status === 'in_progress')
                                            <span class="badge {{ $esRegistroAntiguo ? 'bg-danger' : 'bg-warning text-dark' }}" style="font-size: 0.65rem;">
                                                @if($esRegistroAntiguo)
                                                    <i class="bi bi-exclamation-triangle"></i> Sin cerrar
                                                @else
                                                    Activo
                                                @endif
                                            </span>
                                        @else
                                            <span class="badge bg-secondary" style="font-size: 0.65rem;">OK</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($reg->subscription_id)
                                            @php
                                                $subscription = $reg->subscription;
                                            @endphp
                                            @if($subscription && $subscription->client_id !== $cliente->id)
                                                <small style="font-size: 0.7rem;">
                                                    <i class="bi bi-link-45deg text-success"></i>
                                                    {{ Str::limit($subscription->client->full_name ?? 'N/A', 20) }}
                                                </small>
                                            @else
                                                <small style="font-size: 0.7rem;">
                                                    <i class="bi bi-person-check-fill text-primary"></i> Plan propio
                                                </small>
                                            @endif
                                        @else
                                            <span class="badge bg-warning text-dark" style="font-size: 0.65rem;">Ocasional</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($reg->status === 'in_progress')
                                            @if($esRegistroAntiguo)
                                                <div class="d-grid gap-1">
                                                    <button type="button" class="btn btn-sm btn-danger py-1 px-2" style="font-size: 0.75rem;"
                                                            onclick='openCloseOldRecordModal({{ $reg->id }}, "{{ addslashes($cliente->full_name) }}", "{{ $reg->service_type }}", "{{ $reg->check_in->format("Y-m-d\TH:i") }}")'
                                                            title="Cerrar registro antiguo">
                                                        <i class="bi bi-exclamation-triangle-fill"></i> Cerrar con Fecha
                                                    </button>
                                                    <small class="text-danger" style="font-size: 0.65rem;">
                                                        <i class="bi bi-info-circle"></i> Ingresa fecha de salida
                                                    </small>
                                                </div>
                                            @else
                                                <div class="d-grid gap-1">
                                                    <form method="POST" action="{{ route('admin.registro.finalizar', $reg->id) }}" style="display: inline;">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-warning py-1 px-2 w-100" style="font-size: 0.75rem;" title="Finalizar">
                                                            <i class="bi bi-stop-circle"></i> Finalizar
                                                        </button>
                                                    </form>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" style="font-size: 0.7rem;"
                                                            onclick='openEditModal({{ $reg->id }}, "{{ $reg->service_type }}", "{{ addslashes($cliente->full_name) }}", "{{ $reg->check_in->format("Y-m-d\TH:i") }}", "", {{ $reg->subscription_id ?? "null" }}, "{{ addslashes($planName) }}")'
                                                            title="Editar entrada">
                                                        <i class="bi bi-pencil"></i> Editar
                                                    </button>
                                                </div>
                                            @endif
                                        @else
                                            <button type="button" class="btn btn-sm btn-outline-info py-1 px-2" style="font-size: 0.75rem;"
                                                    onclick='openEditModal({{ $reg->id }}, "{{ $reg->service_type }}", "{{ addslashes($cliente->full_name) }}", "{{ $reg->check_in->format("Y-m-d\TH:i") }}", "{{ $reg->check_out ? $reg->check_out->format("Y-m-d\TH:i") : "" }}", {{ $reg->subscription_id ?? "null" }}, "{{ addslashes($planName) }}")'
                                                    title="Editar horarios">
                                                <i class="bi bi-pencil"></i> Editar
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="card-footer bg-light py-2">
                    <div class="row text-center g-1">
                        <div class="col-4">
                            <small style="font-size: 0.7rem;"><strong>Total:</strong></small><br>
                            <span class="badge bg-primary" style="font-size: 0.7rem;">{{ $registros->count() }}</span>
                        </div>
                        <div class="col-4">
                            <small style="font-size: 0.7rem;"><strong>Horas:</strong></small><br>
                            <span class="badge bg-success" style="font-size: 0.7rem;">{{ number_format($registros->sum('duration_in_hours'), 2) }}h</span>
                        </div>
                        <div class="col-4">
                            <small style="font-size: 0.7rem;"><strong>Activos:</strong></small><br>
                            <span class="badge bg-warning text-dark" style="font-size: 0.7rem;">{{ $registros->where('status', 'in_progress')->count() }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
