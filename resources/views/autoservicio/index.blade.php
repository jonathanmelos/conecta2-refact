@extends('layouts.conecta-public')

@section('content')
@if(session('success') && request('redirect_home'))
<div class="alert alert-success">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
</div>
<script>
    setTimeout(function () {
        window.location.href = 'https://conectacowork.com';
    }, 2500);
</script>
@endif
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h4 class="mb-2"><i class="bi bi-person-badge me-2"></i>Consulta de plan</h4>
                <p class="text-muted mb-3">Ingresa tu numero de documento para revisar tu plan y horas disponibles.</p>
                <form method="GET" action="{{ route('autoservicio.index') }}">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" name="doc" value="{{ $doc }}" placeholder="Documento o cedula" required>
                        <button class="btn btn-primary" type="submit">Consultar</button>
                    </div>
                </form>
            </div>
        </div>

        @if($clientNotFound)
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle me-2"></i>Cliente no encontrado. Verifica el documento.
            </div>
        @endif

        @if($client)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start">
                        <div>
                            <h5 class="mb-1">{{ $client->full_name }}</h5>
                            <div class="text-muted small">Documento: {{ $client->document_number }}</div>
                        </div>
                        <div class="mt-3 mt-md-0">
                            @if($planActive)
                                <span class="badge bg-success">Plan activo</span>
                            @else
                                <span class="badge bg-danger">Sin plan activo</span>
                            @endif
                        </div>
                    </div>
                    <hr>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 border rounded bg-light">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong>Cowork</strong>
                                    <span class="badge bg-primary">{{ number_format($hoursAvailable['cowork'], 2) }} hrs</span>
                                </div>
                                <div class="small text-muted mt-1">Horas disponibles para cowork</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 border rounded bg-light">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong>Sala de reuniones</strong>
                                    <span class="badge bg-info">{{ number_format($hoursAvailable['meeting_room'], 2) }} hrs</span>
                                </div>
                                <div class="small text-muted mt-1">Horas disponibles para sala</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="mb-3"><i class="bi bi-grid-3x3-gap me-2"></i>Selecciona un espacio</h5>
                    <div class="mb-2 text-muted small">Disponibles segun horas de tu plan.</div>

                    @php
                        $canCowork = $planActive && $hoursAvailable['cowork'] > 0;
                        $canSala = $planActive && $hoursAvailable['meeting_room'] > 0;
                    @endphp

                    <div class="mb-3">
                        <div class="fw-semibold mb-2">Cowork</div>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($spaces as $space)
                                @if($space['service_type'] === 'cowork')
                                    <a href="{{ $canCowork ? route('autoservicio.index', ['doc' => $doc, 'space' => $space['key']]) : '#' }}"
                                       class="btn btn-outline-primary {{ $selectedSpace && $selectedSpace['key'] === $space['key'] ? 'active' : '' }} {{ $canCowork ? '' : 'disabled' }}">
                                        {{ $space['label'] }}
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <div class="fw-semibold mb-2">Sala de reuniones</div>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($spaces as $space)
                                @if($space['service_type'] === 'meeting_room')
                                    <a href="{{ $canSala ? route('autoservicio.index', ['doc' => $doc, 'space' => $space['key']]) : '#' }}"
                                       class="btn btn-outline-info {{ $selectedSpace && $selectedSpace['key'] === $space['key'] ? 'active' : '' }} {{ $canSala ? '' : 'disabled' }}">
                                        {{ $space['label'] }}
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            @if($selectedSpace)
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="mb-0"><i class="bi bi-calendar3 me-2"></i>Calendario de disponibilidad</h5>
                                <div class="small text-muted">Espacio: {{ $selectedSpace['label'] }}</div>
                            </div>
                            @php
                                $currentMonth = \Carbon\Carbon::createFromDate($year, $month, 1);
                                $prevMonth = $currentMonth->copy()->subMonth();
                                $nextMonth = $currentMonth->copy()->addMonth();
                            @endphp
                            <div class="btn-group">
                                <a class="btn btn-outline-secondary btn-sm"
                                   href="{{ route('autoservicio.index', ['doc' => $doc, 'space' => $selectedSpace['key'], 'year' => $prevMonth->year, 'month' => $prevMonth->month]) }}">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                                <a class="btn btn-outline-secondary btn-sm"
                                   href="{{ route('autoservicio.index', ['doc' => $doc, 'space' => $selectedSpace['key'], 'year' => $nextMonth->year, 'month' => $nextMonth->month]) }}">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </div>
                        </div>

                        <div class="text-center fw-semibold mb-3">
                            {{ $currentMonth->translatedFormat('F Y') }}
                        </div>

                        <div class="calendar-grid mb-3">
                            <div class="calendar-header">Lun</div>
                            <div class="calendar-header">Mar</div>
                            <div class="calendar-header">Mie</div>
                            <div class="calendar-header">Jue</div>
                            <div class="calendar-header">Vie</div>
                            <div class="calendar-header">Sab</div>
                            <div class="calendar-header">Dom</div>

                            @foreach($calendarDays as $day)
                                @php
                                    $dateKey = $day['date']->format('Y-m-d');
                                    $isPast = $day['date']->lt(today());
                                    $isDisabled = !$day['isCurrentMonth'] || $isPast;
                                @endphp
                                <div class="calendar-cell {{ $day['isCurrentMonth'] ? '' : 'text-muted' }} {{ $day['isToday'] ? 'today' : '' }} {{ $selectedDate === $dateKey ? 'selected' : '' }}">
                                    <div class="small mb-1">{{ $day['date']->day }}</div>
                                    @if($day['isCurrentMonth'])
                                        <button type="button"
                                                class="btn btn-sm w-100 btn-outline-success {{ $isDisabled ? 'disabled' : '' }}"
                                                data-date="{{ $dateKey }}"
                                                data-action="open-reserva-modal"
                                                {{ $isDisabled ? 'disabled' : '' }}>
                                            {{ $isPast ? 'No disponible' : 'Seleccionar' }}
                                        </button>
                                        @if(($reservedByDay[$dateKey] ?? 0) > 0)
                                            <div class="small text-muted mt-1">{{ $reservedByDay[$dateKey] }} reservas</div>
                                        @endif
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <div class="d-flex gap-3 mt-3 small text-muted">
                            <div><span class="badge bg-success">Disponible</span> Selecciona para reservar</div>
                            <div><span class="badge bg-secondary">No disponible</span> Fecha pasada</div>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>

<style>
    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 8px;
    }
    .calendar-header {
        text-align: center;
        font-weight: 600;
        color: #6c757d;
    }
    .calendar-cell {
        padding: 8px;
        border: 1px solid #e6e8ef;
        border-radius: 8px;
        min-height: 86px;
        background: #fff;
        text-align: center;
    }
    .calendar-cell.today {
        border-color: #3498db;
        box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.15);
    }
    .calendar-cell.selected {
        border-color: #2c3e50;
        box-shadow: 0 0 0 2px rgba(44, 62, 80, 0.15);
    }
</style>

@if($selectedSpace)
<div class="modal fade" id="modalReservaHora" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('autoservicio.reserve') }}" id="formReservaHora">
                @csrf
                <input type="hidden" name="doc" value="{{ $doc }}">
                <input type="hidden" name="space" value="{{ $selectedSpace['key'] }}">
                <input type="hidden" name="date" id="reservaFecha" value="{{ $selectedDate }}">

                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-clock me-2"></i>Reserva por horas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Fecha seleccionada</label>
                        <input type="text" class="form-control" id="reservaFechaTexto" readonly>
                        <div class="form-text">Selecciona un rango mayor a 1 hora.</div>
                    </div>

                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label">Hora inicio</label>
                            <input type="time" class="form-control" name="start_time" id="reservaInicio" required step="300" min="08:00" max="20:00">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Hora fin</label>
                            <input type="time" class="form-control" name="end_time" id="reservaFin" required step="300" min="09:00" max="21:00">
                        </div>
                    </div>

                    <div class="alert alert-warning mt-3 py-2 d-none" id="reservaError">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        La reserva debe ser de al menos 1 hora.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Salir</button>
                    <button type="submit" class="btn btn-primary">Guardar reserva</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<script>
document.querySelectorAll('[data-action="open-reserva-modal"]').forEach(button => {
    button.addEventListener('click', () => {
        const date = button.getAttribute('data-date');
        const modalEl = document.getElementById('modalReservaHora');
        if (!modalEl) return;
        const modal = new bootstrap.Modal(modalEl);
        document.getElementById('reservaFecha').value = date;
        document.getElementById('reservaFechaTexto').value = date.split('-').reverse().join('/');
        document.getElementById('reservaInicio').value = '';
        document.getElementById('reservaFin').value = '';
        document.getElementById('reservaError').classList.add('d-none');
        modal.show();
    });
});

const inicio = document.getElementById('reservaInicio');
const fin = document.getElementById('reservaFin');
const errorEl = document.getElementById('reservaError');
if (inicio && fin) {
    const validar = () => {
        if (!inicio.value || !fin.value) return;
        const [h1, m1] = inicio.value.split(':').map(Number);
        const [h2, m2] = fin.value.split(':').map(Number);
        const diff = (h2 * 60 + m2) - (h1 * 60 + m1);
        if (diff < 60) {
            errorEl.classList.remove('d-none');
        } else {
            errorEl.classList.add('d-none');
        }
    };
    inicio.addEventListener('change', validar);
    fin.addEventListener('change', validar);
    document.getElementById('formReservaHora')?.addEventListener('submit', (event) => {
        if (!inicio.value || !fin.value) return;
        const [h1, m1] = inicio.value.split(':').map(Number);
        const [h2, m2] = fin.value.split(':').map(Number);
        const diff = (h2 * 60 + m2) - (h1 * 60 + m1);
        if (diff < 60) {
            event.preventDefault();
            errorEl.classList.remove('d-none');
        }
    });
}
</script>
@endsection
