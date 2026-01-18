<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Client;
use App\Models\WhatsappContact;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CalendarioController extends Controller
{
    /**
     * Vista principal del calendario
     */
    public function index(Request $request)
    {
        // Obtener mes y año de la URL o usar el actual
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('m'));

        // Crear fecha del primer día del mes
        $currentDate = Carbon::createFromDate($year, $month, 1);

        // Obtener eventos del mes
        $events = Event::byMonth($year, $month)
            ->with('client')
            ->orderBy('start_date')
            ->orderBy('start_time')
            ->get();

        // Agrupar eventos por día
        $eventsByDay = $events->groupBy(function ($event) {
            return $event->start_date->format('Y-m-d');
        });

        // Calcular días del calendario
        $calendarDays = $this->generateCalendarDays($currentDate);

        // Próximos eventos (para sidebar)
        $upcomingEvents = Event::upcoming()
            ->take(5)
            ->with('client')
            ->get();

        return view('admin.calendario.index', compact(
            'currentDate',
            'events',
            'eventsByDay',
            'calendarDays',
            'upcomingEvents',
            'year',
            'month'
        ));
    }

    /**
     * Generar días del calendario para un mes
     */
    private function generateCalendarDays(Carbon $date): array
    {
        $days = [];

        // Primer día del mes
        $firstDay = $date->copy()->startOfMonth();

        // Último día del mes
        $lastDay = $date->copy()->endOfMonth();

        // Día de la semana del primer día (0=Domingo, 1=Lunes, etc.)
        // Ajustamos para que empiece en Lunes
        $startDayOfWeek = $firstDay->dayOfWeekIso; // 1=Lunes, 7=Domingo

        // Agregar días del mes anterior para completar la primera semana
        for ($i = 1; $i < $startDayOfWeek; $i++) {
            $prevDay = $firstDay->copy()->subDays($startDayOfWeek - $i);
            $days[] = [
                'date' => $prevDay,
                'isCurrentMonth' => false,
                'isToday' => $prevDay->isToday(),
            ];
        }

        // Agregar todos los días del mes actual
        for ($day = 1; $day <= $lastDay->day; $day++) {
            $currentDay = Carbon::createFromDate($date->year, $date->month, $day);
            $days[] = [
                'date' => $currentDay,
                'isCurrentMonth' => true,
                'isToday' => $currentDay->isToday(),
            ];
        }

        // Agregar días del mes siguiente para completar la última semana
        $remainingDays = 7 - (count($days) % 7);
        if ($remainingDays < 7) {
            for ($i = 1; $i <= $remainingDays; $i++) {
                $nextDay = $lastDay->copy()->addDays($i);
                $days[] = [
                    'date' => $nextDay,
                    'isCurrentMonth' => false,
                    'isToday' => $nextDay->isToday(),
                ];
            }
        }

        return $days;
    }

    /**
     * Formulario para crear evento
     */
    public function create(Request $request)
    {
        $date = $request->get('date', date('Y-m-d'));
        $clients = Client::active()->orderBy('first_name')->get();

        return view('admin.calendario.create', compact('date', 'clients'));
    }

    /**
     * Guardar nuevo evento
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'start_time' => 'nullable|string',
            'end_time' => 'nullable|string',
            'all_day' => 'boolean',
            'color' => 'nullable|string|max:20',
            'location' => 'nullable|string|max:255',
            'client_id' => 'nullable|exists:clients,id',
            'type' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        $validated['user_id'] = auth()->id();
        $validated['status'] = 'scheduled';
        $validated['all_day'] = $request->has('all_day');

        if (!$validated['end_date']) {
            $validated['end_date'] = $validated['start_date'];
        }

        Event::create($validated);

        $date = Carbon::parse($validated['start_date']);

        return redirect()
            ->route('admin.calendario.index', ['year' => $date->year, 'month' => $date->month])
            ->with('success', 'Evento creado exitosamente.');
    }

    /**
     * Buscar clientes por nombre, apellido o documento.
     */
    public function buscarClientes(Request $request)
    {
        $term = trim($request->get('term', ''));

        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }

        $clients = Client::query()
            ->where('first_name', 'like', '%' . $term . '%')
            ->orWhere('last_name', 'like', '%' . $term . '%')
            ->orWhere('document_number', 'like', '%' . $term . '%')
            ->orderBy('first_name')
            ->limit(8)
            ->get(['id', 'first_name', 'last_name', 'document_number', 'phone']);

        $payload = $clients->map(function ($client) {
            return [
                'id' => $client->id,
                'full_name' => $client->full_name,
                'document_number' => $client->document_number,
                'phone' => $client->phone,
            ];
        });

        return response()->json($payload);
    }

    /**
     * Guardar contacto y actualizar teléfono si aplica.
     */
    public function guardarWhatsappContacto(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'phone' => 'required|string|max:30',
            'client_id' => 'nullable|exists:clients,id',
        ]);

        $name = trim($validated['name']);
        $phone = preg_replace('/\D+/', '', $validated['phone']);

        if ($phone === '') {
            return response()->json(['ok' => false, 'message' => 'Numero invalido.'], 422);
        }

        DB::beginTransaction();
        try {
            if (!empty($validated['client_id'])) {
                $client = Client::find($validated['client_id']);
                if ($client && (!$client->phone || $client->phone !== $phone)) {
                    $client->update(['phone' => $phone]);
                }
            } else {
                WhatsappContact::updateOrCreate(
                    ['phone' => $phone],
                    [
                        'name' => $name,
                        'source' => 'calendario',
                    ]
                );
            }

            if (!empty($validated['client_id'])) {
                WhatsappContact::updateOrCreate(
                    ['phone' => $phone],
                    [
                        'client_id' => $validated['client_id'],
                        'name' => $name,
                        'source' => 'calendario',
                    ]
                );
            }

            DB::commit();
            return response()->json(['ok' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Ver detalle de un evento
     */
    public function show(Event $evento)
    {
        $evento->load('client', 'user');
        return view('admin.calendario.show', compact('evento'));
    }

    /**
     * Formulario para editar evento
     */
    public function edit(Event $evento)
    {
        $clients = Client::active()->orderBy('first_name')->get();
        return view('admin.calendario.edit', compact('evento', 'clients'));
    }

    /**
     * Actualizar evento
     */
    public function update(Request $request, Event $evento)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'start_time' => 'nullable|string',
            'end_time' => 'nullable|string',
            'all_day' => 'boolean',
            'color' => 'nullable|string|max:20',
            'location' => 'nullable|string|max:255',
            'client_id' => 'nullable|exists:clients,id',
            'type' => 'nullable|string|max:50',
            'status' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
        ]);

        $validated['all_day'] = $request->has('all_day');

        if (!$validated['end_date']) {
            $validated['end_date'] = $validated['start_date'];
        }

        $evento->update($validated);

        $date = Carbon::parse($validated['start_date']);

        return redirect()
            ->route('admin.calendario.index', ['year' => $date->year, 'month' => $date->month])
            ->with('success', 'Evento actualizado exitosamente.');
    }

    /**
     * Eliminar evento
     */
    public function destroy(Event $evento)
    {
        $date = $evento->start_date;
        $evento->delete();

        return redirect()
            ->route('admin.calendario.index', ['year' => $date->year, 'month' => $date->month])
            ->with('success', 'Evento eliminado exitosamente.');
    }

    /**
     * API: Obtener eventos para FullCalendar (JSON)
     */
    public function apiEvents(Request $request)
    {
        $start = $request->get('start');
        $end = $request->get('end');

        $events = Event::whereBetween('start_date', [$start, $end])
            ->with('client')
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'start' => $event->start_date->format('Y-m-d') . ($event->start_time ? 'T' . $event->start_time : ''),
                    'end' => $event->end_date->format('Y-m-d') . ($event->end_time ? 'T' . $event->end_time : ''),
                    'allDay' => $event->all_day,
                    'color' => $event->color ?? '#3788d8',
                    'extendedProps' => [
                        'description' => $event->description,
                        'location' => $event->location,
                        'client' => $event->client ? $event->client->full_name : null,
                        'status' => $event->status,
                    ],
                ];
            });

        return response()->json($events);
    }
}
