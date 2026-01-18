@extends('layouts.conecta')

@section('content')
<div class="row">
    {{-- Columna principal: Calendario --}}
    <div class="col-lg-9">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-primary text-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-calendar3 fs-3 me-3"></i>
                        <div>
                            <h4 class="mb-0 fw-bold">Calendario de Eventos</h4>
                            <small class="opacity-75">Gestiona los eventos del coworking</small>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-light" data-bs-toggle="modal" data-bs-target="#modalWhatsappRegistro">
                            <i class="bi bi-whatsapp me-1"></i> Enviar WhatsApp
                        </button>
                        <a href="{{ route('admin.calendario.create') }}" class="btn btn-light">
                            <i class="bi bi-plus-circle me-1"></i> Nuevo Evento
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                {{-- Navegacion del mes --}}
                <div class="d-flex justify-content-between align-items-center p-3 bg-light border-bottom">
                    @php
                        $prevMonth = $currentDate->copy()->subMonth();
                        $nextMonth = $currentDate->copy()->addMonth();
                    @endphp

                    <a href="{{ route('admin.calendario.index', ['year' => $prevMonth->year, 'month' => $prevMonth->month]) }}"
                       class="btn btn-outline-primary">
                        <i class="bi bi-chevron-left"></i> {{ $prevMonth->translatedFormat('F') }}
                    </a>

                    <div class="text-center">
                        <h3 class="mb-0 fw-bold text-primary">
                            {{ $currentDate->translatedFormat('F Y') }}
                        </h3>
                        @if(!$currentDate->isSameMonth(now()))
                            <a href="{{ route('admin.calendario.index') }}" class="btn btn-sm btn-link">
                                Ir a hoy
                            </a>
                        @endif
                    </div>

                    <a href="{{ route('admin.calendario.index', ['year' => $nextMonth->year, 'month' => $nextMonth->month]) }}"
                       class="btn btn-outline-primary">
                        {{ $nextMonth->translatedFormat('F') }} <i class="bi bi-chevron-right"></i>
                    </a>
                </div>

                {{-- Cabecera dias de la semana --}}
                <div class="row g-0 border-bottom bg-secondary text-white">
                    @foreach(['Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab', 'Dom'] as $dayName)
                        <div class="col text-center py-2 fw-bold" style="min-width: 14.28%;">
                            {{ $dayName }}
                        </div>
                    @endforeach
                </div>

                {{-- Grid del calendario --}}
                <div class="calendar-grid">
                    @foreach(array_chunk($calendarDays, 7) as $week)
                        <div class="row g-0">
                            @foreach($week as $day)
                                @php
                                    $dayKey = $day['date']->format('Y-m-d');
                                    $dayEvents = $eventsByDay->get($dayKey, collect());
                                    $isWeekend = $day['date']->isWeekend();
                                @endphp
                                <div class="col calendar-day border {{ !$day['isCurrentMonth'] ? 'bg-light text-muted' : '' }} {{ $day['isToday'] ? 'bg-warning-subtle border-warning' : '' }} {{ $isWeekend && $day['isCurrentMonth'] ? 'bg-light' : '' }}"
                                     style="min-height: 100px; min-width: 14.28%;">

                                    {{-- Numero del dia --}}
                                    <div class="d-flex justify-content-between align-items-start p-1">
                                        <span class="fw-bold {{ $day['isToday'] ? 'badge bg-primary rounded-circle' : '' }}"
                                              style="{{ $day['isToday'] ? 'width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;' : '' }}">
                                            {{ $day['date']->day }}
                                        </span>

                                        @if($day['isCurrentMonth'])
                                            <a href="{{ route('admin.calendario.create', ['date' => $dayKey]) }}"
                                               class="btn btn-sm btn-link p-0 text-muted add-event-btn"
                                               title="Agregar evento">
                                                <i class="bi bi-plus"></i>
                                            </a>
                                        @endif
                                    </div>

                                    {{-- Eventos del dia --}}
                                    <div class="events-container px-1 pb-1">
                                        @foreach($dayEvents->take(3) as $event)
                                            @php
                                                $eventTitle = $event->title;
                                                if ($event->type === 'reservation' && $event->client) {
                                                    $eventTitle = $event->client->full_name;
                                                }
                                            @endphp
                                            <a href="{{ route('admin.calendario.show', $event) }}"
                                               class="d-block text-decoration-none mb-1 event-item rounded px-1"
                                               style="background-color: {{ $event->color ?? '#3788d8' }}; color: white; font-size: 0.75rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                                               title="{{ $eventTitle }}">
                                                @if(!$event->all_day && $event->start_time)
                                                    <small>{{ \Carbon\Carbon::parse($event->start_time)->format('H:i') }}</small>
                                                @endif
                                                {{ $eventTitle }}
                                            </a>
                                        @endforeach

                                        @if($dayEvents->count() > 3)
                                            <a href="#" class="text-primary small"
                                               data-bs-toggle="modal"
                                               data-bs-target="#dayEventsModal"
                                               data-date="{{ $dayKey }}"
                                               data-events='@json($dayEvents)'>
                                                +{{ $dayEvents->count() - 3 }} mas
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Columna lateral: Proximos eventos --}}
    <div class="col-lg-3">
        {{-- Proximos eventos --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="bi bi-clock me-2"></i> Proximos Eventos
                </h5>
            </div>
            <div class="card-body p-0">
                @forelse($upcomingEvents as $event)
                    <a href="{{ route('admin.calendario.show', $event) }}"
                       class="d-block p-3 border-bottom text-decoration-none hover-bg-light">
                        <div class="d-flex align-items-start">
                            <div class="me-3 text-center" style="min-width: 45px;">
                                <div class="fw-bold text-primary" style="font-size: 1.5rem; line-height: 1;">
                                    {{ $event->start_date->format('d') }}
                                </div>
                                <small class="text-muted text-uppercase">
                                    {{ $event->start_date->translatedFormat('M') }}
                                </small>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold text-dark">{{ $event->title }}</div>
                                <small class="text-muted">
                                    <i class="bi bi-clock me-1"></i>
                                    {{ $event->formatted_time }}
                                </small>
                                @if($event->location)
                                    <br>
                                    <small class="text-muted">
                                        <i class="bi bi-geo-alt me-1"></i>
                                        {{ $event->location }}
                                    </small>
                                @endif
                            </div>
                            <div class="ms-2">
                                <span class="badge rounded-pill" style="background-color: {{ $event->color ?? '#3788d8' }};">
                                    &nbsp;
                                </span>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
                        No hay eventos proximos
                    </div>
                @endforelse
            </div>
            @if($upcomingEvents->count() > 0)
                <div class="card-footer bg-transparent text-center">
                    <a href="{{ route('admin.calendario.create') }}" class="btn btn-success btn-sm w-100">
                        <i class="bi bi-plus-circle me-1"></i> Agregar Evento
                    </a>
                </div>
            @endif
        </div>

        {{-- Leyenda de colores --}}
        <div class="card shadow-sm border-0">
            <div class="card-header bg-secondary text-white">
                <h6 class="mb-0">
                    <i class="bi bi-palette me-2"></i> Tipos de Evento
                </h6>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <span class="badge me-2" style="background-color: #3788d8; width: 20px;">&nbsp;</span>
                    <small>Reunion</small>
                </div>
                <div class="d-flex align-items-center mb-2">
                    <span class="badge me-2" style="background-color: #28a745; width: 20px;">&nbsp;</span>
                    <small>Evento Coworking</small>
                </div>
                <div class="d-flex align-items-center mb-2">
                    <span class="badge me-2" style="background-color: #ffc107; width: 20px;">&nbsp;</span>
                    <small>Capacitacion</small>
                </div>
                <div class="d-flex align-items-center mb-2">
                    <span class="badge me-2" style="background-color: #dc3545; width: 20px;">&nbsp;</span>
                    <small>Importante</small>
                </div>
                <div class="d-flex align-items-center">
                    <span class="badge me-2" style="background-color: #6f42c1; width: 20px;">&nbsp;</span>
                    <small>Networking</small>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal para ver eventos del dia --}}
<div class="modal fade" id="dayEventsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Eventos del dia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="dayEventsContent">
                <!-- Se llena con JavaScript -->
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalWhatsappRegistro" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Invitar por WhatsApp</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="whatsapp_client_search" class="form-label">Cliente</label>
                    <input type="text"
                           class="form-control"
                           id="whatsapp_client_search"
                           placeholder="Buscar por nombre, apellido o cédula"
                           autocomplete="off">
                    <input type="hidden" id="whatsapp_client_id" value="">
                    <div id="whatsapp_client_results" class="list-group mt-2 d-none"></div>
                </div>
                <div class="mb-3">
                    <label for="whatsapp_phone" class="form-label">Número de WhatsApp</label>
                    <input type="text"
                           class="form-control"
                           id="whatsapp_phone"
                           placeholder="Ej: 593999999999"
                           autocomplete="off">
                    <small class="text-muted">Usa formato internacional sin símbolos ni espacios.</small>
                </div>
                <div class="mb-3">
                    <label for="whatsapp_message" class="form-label">Mensaje</label>
                    <textarea class="form-control" id="whatsapp_message" rows="4">Te comparto este link para que reserves tu espacio en Conecta Coworking, elige el día que prefieras: {{ url('/autoservicio') }}</textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="whatsapp_send_btn">
                    <i class="bi bi-whatsapp me-1"></i> Enviar WhatsApp
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
    .calendar-day {
        transition: background-color 0.2s;
    }

    .calendar-day:hover {
        background-color: #f8f9fa !important;
    }

    .calendar-day .add-event-btn {
        opacity: 0;
        transition: opacity 0.2s;
    }

    .calendar-day:hover .add-event-btn {
        opacity: 1;
    }

    .event-item {
        transition: transform 0.1s, box-shadow 0.1s;
    }

    .event-item:hover {
        transform: scale(1.02);
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    .hover-bg-light:hover {
        background-color: #f8f9fa;
    }

    @media (max-width: 768px) {
        .calendar-day {
            min-height: 60px !important;
            font-size: 0.8rem;
        }

        .events-container {
            display: none;
        }

        .calendar-day .add-event-btn {
            opacity: 1;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    // Modal para eventos del dia
    document.getElementById('dayEventsModal')?.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const date = button.getAttribute('data-date');
        const events = JSON.parse(button.getAttribute('data-events') || '[]');

        const content = document.getElementById('dayEventsContent');
        let html = '<div class="list-group list-group-flush">';

        events.forEach(event => {
            const color = event.color || '#3788d8';
            const title = event.type === 'reservation' && event.client
                ? `${event.client.first_name ?? ''} ${event.client.last_name ?? ''}`.trim()
                : event.title;
            html += `
                <a href="/admin/calendario/${event.id}" class="list-group-item list-group-item-action">
                    <div class="d-flex align-items-center">
                        <span class="badge me-2" style="background-color: ${color}; width: 12px; height: 12px; border-radius: 50%;"></span>
                        <div>
                            <div class="fw-semibold">${title || event.title}</div>
                            <small class="text-muted">${event.start_time || 'Todo el día'}</small>
                        </div>
                    </div>
                </a>
            `;
        });

        html += '</div>';
        content.innerHTML = html;

        document.querySelector('#dayEventsModal .modal-title').textContent =
            'Eventos del ' + new Date(date).toLocaleDateString('es-ES', { day: 'numeric', month: 'long', year: 'numeric' });
    });

    const searchInput = document.getElementById('whatsapp_client_search');
    const resultsBox = document.getElementById('whatsapp_client_results');
    const phoneInput = document.getElementById('whatsapp_phone');
    const clientIdInput = document.getElementById('whatsapp_client_id');
    const messageInput = document.getElementById('whatsapp_message');

    let searchTimer = null;

    function clearResults() {
        resultsBox.innerHTML = '';
        resultsBox.classList.add('d-none');
    }

    function renderResults(items) {
        resultsBox.innerHTML = '';
        if (!items.length) {
            clearResults();
            return;
        }

        items.forEach(item => {
            const option = document.createElement('button');
            option.type = 'button';
            option.className = 'list-group-item list-group-item-action';
            option.dataset.clientId = item.id;
            option.dataset.phone = item.phone || '';
            option.textContent = `${item.full_name} (${item.document_number})`;
            option.addEventListener('click', () => {
                searchInput.value = option.textContent;
                clientIdInput.value = item.id;
                if (item.phone) {
                    phoneInput.value = item.phone;
                }
                clearResults();
            });
            resultsBox.appendChild(option);
        });

        resultsBox.classList.remove('d-none');
    }

    searchInput?.addEventListener('input', () => {
        clientIdInput.value = '';
        if (searchTimer) {
            clearTimeout(searchTimer);
        }

        const term = searchInput.value.trim();
        if (term.length < 2) {
            clearResults();
            return;
        }

        searchTimer = setTimeout(() => {
            fetch(`{{ route('admin.calendario.buscarClientes') }}?term=${encodeURIComponent(term)}`)
                .then(response => response.json())
                .then(renderResults)
                .catch(() => clearResults());
        }, 250);
    });

    document.addEventListener('click', (event) => {
        if (!resultsBox.contains(event.target) && event.target !== searchInput) {
            clearResults();
        }
    });

    document.getElementById('whatsapp_send_btn')?.addEventListener('click', function() {
        const rawPhone = phoneInput.value || '';
        const phone = rawPhone.replace(/\D/g, '');
        const name = searchInput.value.trim();
        const message = messageInput.value || '';

        if (!name) {
            alert('Ingresa el nombre del cliente.');
            searchInput.focus();
            return;
        }

        if (!phone) {
            alert('Ingresa un número de WhatsApp válido.');
            phoneInput.focus();
            return;
        }

        const modalEl = document.getElementById('modalWhatsappRegistro');
        const modalInstance = modalEl ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;

        fetch(`{{ route('admin.calendario.guardarWhatsappContacto') }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify({
                name: name,
                phone: phone,
                client_id: clientIdInput.value || null,
            }),
        }).then(() => {
            const url = `https://wa.me/${phone}?text=${encodeURIComponent(message)}`;
            window.open(url, '_blank', 'noopener');
            if (modalInstance) {
                modalInstance.hide();
            }
            setTimeout(() => window.location.reload(), 300);
        }).catch(() => {
            const url = `https://wa.me/${phone}?text=${encodeURIComponent(message)}`;
            window.open(url, '_blank', 'noopener');
            if (modalInstance) {
                modalInstance.hide();
            }
            setTimeout(() => window.location.reload(), 300);
        });
    });
</script>
@endpush
