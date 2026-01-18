<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Event;
use App\Models\HoursTracking;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SelfServiceController extends Controller
{
    public function index(Request $request)
    {
        $doc = preg_replace('/\D+/', '', (string) $request->get('doc', ''));
        $spaceKey = $request->get('space');
        $year = (int) $request->get('year', date('Y'));
        $month = (int) $request->get('month', date('m'));

        $client = null;
        $clientNotFound = false;
        $planActive = false;
        $hoursAvailable = [
            'cowork' => 0,
            'meeting_room' => 0,
        ];
        $isPilotPlan = false;
        $spaces = $this->spaces();
        $selectedSpace = null;
        $calendarDays = [];
        $reservedByDay = [];
        $reservedTimesByDay = [];
        $selectedDate = $request->get('date');
        $clientReservations = collect();

        if ($doc !== '') {
            $client = $this->resolveClientByDocument($doc);

            if (!$client) {
                $clientNotFound = true;
            } else {
                $subscription = $this->resolveActiveSubscription($client);
                $planActive = (bool) $subscription;

                $clientReservations = Event::where('type', 'reservation')
                    ->where('client_id', $client->id)
                    ->where('status', '!=', 'cancelled')
                    ->orderBy('start_date', 'desc')
                    ->orderBy('start_time', 'desc')
                    ->take(5)
                    ->get();

                if ($planActive) {
                    $isPilotPlan = $subscription->plan && $subscription->plan->is_pilot;
                    $coworkTracking = HoursTracking::where('subscription_id', $subscription->id)
                        ->where('service_type', 'cowork')
                        ->first();
                    $salaTracking = HoursTracking::where('subscription_id', $subscription->id)
                        ->where('service_type', 'meeting_room')
                        ->first();

                    if ($isPilotPlan) {
                        $hoursAvailable['cowork'] = null;
                        $hoursAvailable['meeting_room'] = null;
                    } else {
                        $hoursAvailable['cowork'] = $coworkTracking
                            ? max(0, (float) $coworkTracking->total_hours_available - (float) $coworkTracking->hours_used)
                            : 0;
                        $hoursAvailable['meeting_room'] = $salaTracking
                            ? max(0, (float) $salaTracking->total_hours_available - (float) $salaTracking->hours_used)
                            : 0;
                    }
                }

                if ($spaceKey && isset($spaces[$spaceKey])) {
                    $space = $spaces[$spaceKey];
                    $canReserveCowork = $planActive && ($isPilotPlan || $hoursAvailable['cowork'] > 0);
                    $canReserveSala = $planActive && ($isPilotPlan || $hoursAvailable['meeting_room'] > 0);

                    if (($space['service_type'] === 'cowork' && $canReserveCowork)
                        || ($space['service_type'] === 'meeting_room' && $canReserveSala)
                    ) {
                        $selectedSpace = $space;

                        $currentDate = Carbon::createFromDate($year, $month, 1);
                        $calendarDays = $this->generateCalendarDays($currentDate);

                        $monthStart = $currentDate->copy()->startOfMonth()->toDateString();
                        $monthEnd = $currentDate->copy()->endOfMonth()->toDateString();

                        $reservas = Event::where('type', 'reservation')
                            ->where('location', $spaceKey)
                            ->whereBetween('start_date', [$monthStart, $monthEnd])
                            ->where('status', '!=', 'cancelled')
                            ->get(['start_date', 'start_time', 'end_time']);

                        foreach ($reservas as $reserva) {
                            $dateKey = Carbon::parse($reserva->start_date)->format('Y-m-d');
                            $reservedByDay[$dateKey] = ($reservedByDay[$dateKey] ?? 0) + 1;
                            if (!isset($reservedTimesByDay[$dateKey])) {
                                $reservedTimesByDay[$dateKey] = [];
                            }
                            if ($reserva->start_time) {
                                $range = $reserva->start_time;
                                if ($reserva->end_time) {
                                    $range .= ' - ' . $reserva->end_time;
                                }
                                $reservedTimesByDay[$dateKey][] = $range;
                            }
                        }
                    }
                }
            }
        }

        return view('autoservicio.index', [
            'doc' => $doc,
            'client' => $client,
            'clientNotFound' => $clientNotFound,
            'planActive' => $planActive,
            'hoursAvailable' => $hoursAvailable,
            'spaces' => $spaces,
            'selectedSpace' => $selectedSpace,
            'calendarDays' => $calendarDays,
            'reservedByDay' => $reservedByDay,
            'reservedTimesByDay' => $reservedTimesByDay,
            'clientReservations' => $clientReservations,
            'selectedDate' => $selectedDate,
            'year' => $year,
            'month' => $month,
        ]);
    }

    public function reserve(Request $request)
    {
        $validated = $request->validate([
            'doc' => 'required|string',
            'space' => 'required|string',
            'date' => 'required|date',
            'start_time' => 'required|string',
            'end_time' => 'required|string',
            'email' => 'nullable|email|max:255',
        ]);

        $doc = preg_replace('/\D+/', '', $validated['doc']);
        $spaceKey = $validated['space'];
        $date = Carbon::parse($validated['date'])->startOfDay();
        $startTime = $validated['start_time'];
        $endTime = $validated['end_time'];

        $spaces = $this->spaces();
        if (!isset($spaces[$spaceKey])) {
            return back()->with('error', 'Espacio no valido.');
        }

        $client = $this->resolveClientByDocument($doc);
        if (!$client) {
            return back()->with('error', 'Cliente no encontrado.');
        }

        if (!empty($validated['email']) && $client->email !== $validated['email']) {
            $client->update(['email' => $validated['email']]);
        }

        $subscription = $this->resolveActiveSubscription($client);
        $planActive = (bool) $subscription;

        if (!$planActive) {
            return back()->with('error', 'El cliente no tiene un plan activo.');
        }

        $space = $spaces[$spaceKey];
        $tracking = HoursTracking::where('subscription_id', $subscription->id)
            ->where('service_type', $space['service_type'])
            ->first();
        $isPilotPlan = $subscription->plan && $subscription->plan->is_pilot;
        $availableHours = $tracking
            ? max(0, (float) $tracking->total_hours_available - (float) $tracking->hours_used)
            : 0;

        if (!$isPilotPlan && $availableHours <= 0) {
            return back()->with('error', 'No hay horas disponibles para este servicio.');
        }

        if ($date->lt(today())) {
            return back()->with('error', 'No se puede reservar fechas pasadas.');
        }

        try {
            $start = Carbon::createFromFormat('H:i', $startTime);
            $end = Carbon::createFromFormat('H:i', $endTime);
        } catch (\Exception $e) {
            return back()->with('error', 'Hora no valida.');
        }

        if ($end->lessThanOrEqualTo($start)) {
            return back()->with('error', 'La hora final debe ser mayor que la hora inicial.');
        }

        if ($start->diffInMinutes($end) < 60) {
            return back()->with('error', 'La reserva debe ser de al menos 1 hora.');
        }

        $exists = Event::where('type', 'reservation')
            ->where('location', $spaceKey)
            ->whereDate('start_date', $date->toDateString())
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
                });
            })
            ->exists();

        if ($exists) {
            return back()->with('error', 'El rango de horas seleccionado ya esta reservado.');
        }

        Event::create([
            'title' => 'Reserva Auto - ' . $space['label'],
            'description' => 'Reserva creada por autoservicio',
            'start_date' => $date->toDateString(),
            'end_date' => $date->toDateString(),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'all_day' => false,
            'color' => '#2ecc71',
            'location' => $spaceKey,
            'client_id' => $client->id,
            'status' => 'scheduled',
            'type' => 'reservation',
            'notes' => 'Autoservicio sin login',
        ]);

        return redirect()->route('autoservicio.index', [
            'redirect_home' => 1,
        ])->with('success', 'Reserva exitosa. Redirigiendo al inicio.');
    }

    private function spaces(): array
    {
        return [
            'oficina-1' => ['key' => 'oficina-1', 'label' => 'Oficina 1', 'service_type' => 'cowork'],
            'oficina-2' => ['key' => 'oficina-2', 'label' => 'Oficina 2', 'service_type' => 'cowork'],
            'oficina-3' => ['key' => 'oficina-3', 'label' => 'Oficina 3', 'service_type' => 'cowork'],
            'oficina-4' => ['key' => 'oficina-4', 'label' => 'Oficina 4', 'service_type' => 'cowork'],
            'oficina-5' => ['key' => 'oficina-5', 'label' => 'Oficina 5', 'service_type' => 'cowork'],
            'sala-reuniones-1' => ['key' => 'sala-reuniones-1', 'label' => 'Sala de reuniones 1', 'service_type' => 'meeting_room'],
            'sala-reuniones-2' => ['key' => 'sala-reuniones-2', 'label' => 'Sala de reuniones 2', 'service_type' => 'meeting_room'],
        ];
    }

    private function resolveActiveSubscription(Client $client)
    {
        $subscription = $client->currentSubscription;

        if ($subscription
            && $subscription->start_date->lte(today())
            && ($subscription->end_date ? $subscription->end_date->gte(today()) : true)
        ) {
            return $subscription->loadMissing('plan');
        }

        return $client->subscriptions()
            ->with('plan')
            ->whereDate('start_date', '<=', today())
            ->where(function ($query) {
                $query->whereDate('end_date', '>=', today())
                    ->orWhereNull('end_date');
            })
            ->orderByRaw("status = 'active' desc")
            ->orderBy('start_date', 'desc')
            ->first();
    }

    private function resolveClientByDocument(string $doc): ?Client
    {
        if ($doc === '') {
            return null;
        }

        $candidates = Client::with(['currentSubscription.plan', 'subscriptions.plan'])
            ->where('document_number', $doc)
            ->orWhere('document_number', 'like', $doc . '%')
            ->limit(10)
            ->get();

        if ($candidates->isEmpty()) {
            return null;
        }

        $withActivePlan = $candidates->first(function ($client) {
            return (bool) $this->resolveActiveSubscription($client);
        });
        if ($withActivePlan) {
            return $withActivePlan;
        }

        $exact = $candidates->firstWhere('document_number', $doc);
        if ($exact) {
            return $exact;
        }

        return $candidates->sortByDesc(function ($client) {
            return strlen($client->document_number ?? '');
        })->first();
    }

    private function generateCalendarDays(Carbon $date): array
    {
        $days = [];
        $firstDay = $date->copy()->startOfMonth();
        $lastDay = $date->copy()->endOfMonth();
        $startDayOfWeek = $firstDay->dayOfWeekIso;

        for ($i = 1; $i < $startDayOfWeek; $i++) {
            $prevDay = $firstDay->copy()->subDays($startDayOfWeek - $i);
            $days[] = [
                'date' => $prevDay,
                'isCurrentMonth' => false,
                'isToday' => $prevDay->isToday(),
            ];
        }

        for ($day = 1; $day <= $lastDay->day; $day++) {
            $currentDay = Carbon::createFromDate($date->year, $date->month, $day);
            $days[] = [
                'date' => $currentDay,
                'isCurrentMonth' => true,
                'isToday' => $currentDay->isToday(),
            ];
        }

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

}
