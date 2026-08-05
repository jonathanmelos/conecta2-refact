<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\UsageRecord;
use App\Models\HoursTracking;
use App\Models\Subscription;
use App\Services\UsagePlanResolver;
use Illuminate\Http\Request;
use Carbon\Carbon;

class RegistroController extends Controller
{
    public function __construct(private UsagePlanResolver $usagePlanResolver)
    {
    }

    public function index(Request $request)
    {
        $doc = $request->get('doc', '0');
        $selectedClient = null;
        $hoursTracking = null;
        $selectedClientData = null;
        
        // Si hay un documento seleccionado, buscar el cliente
        if ($doc !== '0' && $doc !== '') {
            $selectedClient = Client::with(['currentSubscription.plan', 'subscriptions.plan', 'invitedBy.currentSubscription.plan', 'invitedBy.subscriptions.plan', 'hoursTracking', 'guests'])
                ->where('document_number', $doc)
                ->first();

            if ($selectedClient) {
                $activeCowork = UsageRecord::where('client_id', $selectedClient->id)
                    ->where('service_type', 'cowork')
                    ->where('status', 'in_progress')
                    ->whereDate('check_in', today())
                    ->exists();

                $activeSala = UsageRecord::where('client_id', $selectedClient->id)
                    ->where('service_type', 'meeting_room')
                    ->where('status', 'in_progress')
                    ->whereDate('check_in', today())
                    ->exists();

                $servicePlans = $this->usagePlanResolver->payloadForClient($selectedClient);
                $defaultSubscriptionData = $servicePlans['cowork']['plan']
                    ? $servicePlans['cowork']
                    : ($servicePlans['meeting_room']['plan'] ? $servicePlans['meeting_room'] : null);

                $selectedClientData = [
                    'id' => $selectedClient->id,
                    'document_number' => $selectedClient->document_number,
                    'first_name' => $selectedClient->first_name,
                    'last_name' => $selectedClient->last_name,
                    'email' => $selectedClient->email,
                    'phone' => $selectedClient->phone,
                    'active_cowork_today' => $activeCowork,
                    'active_sala_today' => $activeSala,
                    'current_subscription' => $defaultSubscriptionData,
                    'service_plans' => $servicePlans,
                    'subscription_dates' => $defaultSubscriptionData['dates_label'] ?? null,
                    'cowork_hours_used' => (float) $servicePlans['cowork']['hours_used'],
                    'sala_hours_used' => (float) $servicePlans['meeting_room']['hours_used'],
                ];
            }
        }
        
        // ✅ Registros del día de hoy + registros no cerrados de días anteriores
        $hoy = date('Y-m-d');
        $registrosHoy = UsageRecord::with([
            'client.invitedBy.currentSubscription.plan',
            'client.currentSubscription.plan',
            'subscription.plan',
            'subscription.client'
        ])
        ->where(function($query) use ($hoy) {
            $query->whereDate('check_in', $hoy)
                ->orWhere(function($q) use ($hoy) {
                    $q->where('status', 'in_progress')
                      ->whereDate('check_in', '<', $hoy);
                });
        })
        ->orderBy('check_in', 'desc')
        ->limit(200)
        ->get();
        
        // Agrupar por cliente
        $clientesAgrupados = $registrosHoy->groupBy('client_id');
        
        return view('admin.registro.index', compact(
            'selectedClient',
            'hoursTracking',
            'selectedClientData',
            'clientesAgrupados',
            'registrosHoy'
        ));
    }

    public function pendientes()
    {
        $records = UsageRecord::with(['client', 'subscription.plan'])
            ->where('status', 'in_progress')
            ->orderBy('check_in')
            ->get();

        $groups = [
            'today' => [
                'label' => 'Hoy',
                'hint' => 'Registros del dia actual.',
                'records' => collect(),
            ],
            'recent' => [
                'label' => 'Ultima semana',
                'hint' => 'Registros entre 1 y 7 dias.',
                'records' => collect(),
            ],
            'over_week' => [
                'label' => 'Mas de una semana',
                'hint' => 'Registros entre 8 y 30 dias.',
                'records' => collect(),
            ],
            'over_month' => [
                'label' => 'Mas de un mes',
                'hint' => 'Registros entre 31 y 90 dias.',
                'records' => collect(),
            ],
            'over_three_months' => [
                'label' => 'Mas de tres meses',
                'hint' => 'Registros entre 91 y 180 dias.',
                'records' => collect(),
            ],
            'very_old' => [
                'label' => 'Muy antiguos',
                'hint' => 'Registros con mas de 180 dias.',
                'records' => collect(),
            ],
        ];

        $now = now();
        foreach ($records as $record) {
            $days = $record->check_in->diffInDays($now);
            if ($record->check_in->isToday()) {
                $key = 'today';
            } elseif ($days <= 7) {
                $key = 'recent';
            } elseif ($days <= 30) {
                $key = 'over_week';
            } elseif ($days <= 90) {
                $key = 'over_month';
            } elseif ($days <= 180) {
                $key = 'over_three_months';
            } else {
                $key = 'very_old';
            }

            $groups[$key]['records']->push($record);
        }

        return view('admin.registro.pendientes', compact('groups'));
    }

    public function eliminarPendientesGrupo(Request $request)
    {
        $validated = $request->validate([
            'group' => 'required|string',
        ]);

        $groupKey = $validated['group'];
        $allowed = [
            'today',
            'recent',
            'over_week',
            'over_month',
            'over_three_months',
            'very_old',
        ];

        if (!in_array($groupKey, $allowed, true)) {
            return redirect()->back()->with('error', 'Grupo de pendientes no valido.');
        }

        $todayStart = now()->startOfDay();
        $weekStart = now()->subDays(7)->startOfDay();
        $overWeekStart = now()->subDays(30)->startOfDay();
        $monthStart = now()->subDays(90)->startOfDay();
        $threeMonthsStart = now()->subDays(180)->startOfDay();

        $query = UsageRecord::where('status', 'in_progress');

        switch ($groupKey) {
            case 'today':
                $query->whereDate('check_in', $todayStart);
                break;
            case 'recent':
                $query->where('check_in', '<', $todayStart)
                    ->where('check_in', '>=', $weekStart);
                break;
            case 'over_week':
                $query->where('check_in', '<', $weekStart)
                    ->where('check_in', '>=', $overWeekStart);
                break;
            case 'over_month':
                $query->where('check_in', '<', $overWeekStart)
                    ->where('check_in', '>=', $monthStart);
                break;
            case 'over_three_months':
                $query->where('check_in', '<', $monthStart)
                    ->where('check_in', '>=', $threeMonthsStart);
                break;
            case 'very_old':
                $query->where('check_in', '<', $threeMonthsStart);
                break;
        }

        $deleted = $query->delete();

        return redirect()->back()->with('success', 'Registros eliminados: ' . $deleted);
    }
    
    public function buscar(Request $request)
    {
        $search = trim($request->get('search', ''));

        if ($search === '') {
            return response()->json([]);
        }
        
        $clients = Client::with(['currentSubscription.plan', 'subscriptions.plan', 'invitedBy.currentSubscription.plan', 'invitedBy.subscriptions.plan', 'hoursTracking'])
            ->where('client_status', 'active')
            ->where(function($query) use ($search) {
                $query->where('document_number', 'LIKE', "%{$search}%")
                      ->orWhere('first_name', 'LIKE', "%{$search}%")
                      ->orWhere('last_name', 'LIKE', "%{$search}%");
            })
            ->limit(10)
            ->get();
        
        $clientsData = $clients->map(function($client) {
            $activeCowork = UsageRecord::where('client_id', $client->id)
                ->where('service_type', 'cowork')
                ->where('status', 'in_progress')
                ->whereDate('check_in', today())
                ->exists();
            
            $activeSala = UsageRecord::where('client_id', $client->id)
                ->where('service_type', 'meeting_room')
                ->where('status', 'in_progress')
                ->whereDate('check_in', today())
                ->exists();
            
            $servicePlans = $this->usagePlanResolver->payloadForClient($client);
            $defaultSubscriptionData = $servicePlans['cowork']['plan']
                ? $servicePlans['cowork']
                : ($servicePlans['meeting_room']['plan'] ? $servicePlans['meeting_room'] : null);
            
            return [
                'id' => $client->id,
                'document_number' => $client->document_number,
                'first_name' => $client->first_name,
                'last_name' => $client->last_name,
                'email' => $client->email,
                'phone' => $client->phone,
                'active_cowork_today' => $activeCowork,
                'active_sala_today' => $activeSala,
                'current_subscription' => $defaultSubscriptionData,
                'service_plans' => $servicePlans,
                'subscription_dates' => $defaultSubscriptionData['dates_label'] ?? null,
                'cowork_hours_used' => (float) $servicePlans['cowork']['hours_used'],
                'sala_hours_used' => (float) $servicePlans['meeting_room']['hours_used'],
            ];
        });
        
        return response()->json($clientsData);
    }
    
    public function cowork(Request $request)
    {
        $client = Client::with(['currentSubscription.plan', 'invitedBy.currentSubscription.plan'])
            ->findOrFail($request->client_id);
        
        $existingService = UsageRecord::where('client_id', $client->id)
            ->where('status', 'in_progress')
            ->whereDate('check_in', today())
            ->first();
        
        if ($existingService) {
            $serviceName = $existingService->service_type === 'cowork' ? 'cowork' : 'sala de reuniones';
            return redirect()->route('admin.registro.index')
                ->with('error', $client->full_name . ' ya está usando ' . $serviceName . '. Debe finalizar ese servicio primero.');
        }
        
        if ($request->master_id) {
            $master = Client::with('currentSubscription.plan')->findOrFail($request->master_id);
            
            if (!$master->currentSubscription || !$master->currentSubscription->plan) {
                return redirect()->route('admin.registro.index')
                    ->with('error', 'El cliente master debe tener un plan activo.');
            }
            
            $client->update([
                'invited_by_client_id' => $master->id,
                'current_subscription_id' => $master->current_subscription_id,
                'subscription_status' => 'active',
            ]);
            
            $client->refresh();
            $client->load(['currentSubscription.plan', 'invitedBy.currentSubscription.plan']);
        }
        
        $resolvedPlan = $this->usagePlanResolver->resolve($client, 'cowork');
        if ($resolvedPlan['status'] === 'ambiguous') {
            return redirect()->route('admin.registro.index')->with('error', $resolvedPlan['message']);
        }
        $subscriptionId = $resolvedPlan['subscription_id'];
        $isBillable = $resolvedPlan['is_billable'];
        $subscriptionToCheck = $resolvedPlan['subscription'];
        $planToCheck = $resolvedPlan['plan'];

        if ($planToCheck) {
            $effectiveCoworkHours = $subscriptionToCheck ? $subscriptionToCheck->effective_cowork_hours : 0;
            if (!$resolvedPlan['is_pilot'] && $effectiveCoworkHours <= 0) {
                return redirect()->route('admin.registro.index')
                    ->with('error', 'El plan ' . $planToCheck->name . ' no incluye horas de cowork.');
            }
        }
        
        UsageRecord::create([
            'client_id' => $client->id,
            'subscription_id' => $subscriptionId,
            'area_id' => 1,
            'service_type' => 'cowork',
            'check_in' => now(),
            'status' => 'in_progress',
            'registration_method' => 'manual',
            'is_billable' => $isBillable,
            'quantity' => 0,
        ]);
        
        $message = '✓ ' . $client->full_name . ' ingresó a cowork';
        
        if ($isBillable) {
            $message .= ' (Cliente ocasional - se facturará por separado)';
        } elseif ($request->master_id) {
            $master = Client::find($request->master_id);
            $message .= ' (Vinculado como invitado de ' . $master->full_name . ')';
        } elseif ($resolvedPlan['source'] === 'member_assignment') {
            $message .= ' (Plan asignado #' . $subscriptionId . ')';
        }
        
        return redirect()->route('admin.registro.index')
            ->with('success', $message);
    }

    public function sala(Request $request)
    {
        $client = Client::with(['currentSubscription.plan', 'invitedBy.currentSubscription.plan'])
            ->findOrFail($request->client_id);
        
        $existingService = UsageRecord::where('client_id', $client->id)
            ->where('status', 'in_progress')
            ->whereDate('check_in', today())
            ->first();
        
        if ($existingService) {
            $serviceName = $existingService->service_type === 'cowork' ? 'cowork' : 'sala de reuniones';
            return redirect()->route('admin.registro.index')
                ->with('error', $client->full_name . ' ya está usando ' . $serviceName . '. Debe finalizar ese servicio primero.');
        }
        
        if ($request->master_id) {
            $master = Client::with('currentSubscription.plan')->findOrFail($request->master_id);
            
            if (!$master->currentSubscription || !$master->currentSubscription->plan) {
                return redirect()->route('admin.registro.index')
                    ->with('error', 'El cliente master debe tener un plan activo.');
            }
            
            $client->update([
                'invited_by_client_id' => $master->id,
                'current_subscription_id' => $master->current_subscription_id,
                'subscription_status' => 'active',
            ]);
            
            $client->refresh();
            $client->load(['currentSubscription.plan', 'invitedBy.currentSubscription.plan']);
        }
        
        $resolvedPlan = $this->usagePlanResolver->resolve($client, 'meeting_room');
        if ($resolvedPlan['status'] === 'ambiguous') {
            return redirect()->route('admin.registro.index')->with('error', $resolvedPlan['message']);
        }
        $subscriptionId = $resolvedPlan['subscription_id'];
        $isBillable = $resolvedPlan['is_billable'];
        $subscriptionToCheck = $resolvedPlan['subscription'];
        $planToCheck = $resolvedPlan['plan'];

        if ($planToCheck) {
            $effectiveMeetingRoomHours = $subscriptionToCheck ? $subscriptionToCheck->effective_meeting_room_hours : 0;
            if (!$resolvedPlan['is_pilot'] && $effectiveMeetingRoomHours <= 0) {
                return redirect()->route('admin.registro.index')
                    ->with('error', 'El plan ' . $planToCheck->name . ' no incluye horas de sala de reuniones.');
            }
        }
        
        UsageRecord::create([
            'client_id' => $client->id,
            'subscription_id' => $subscriptionId,
            'area_id' => 3,
            'service_type' => 'meeting_room',
            'check_in' => now(),
            'status' => 'in_progress',
            'registration_method' => 'manual',
            'is_billable' => $isBillable,
            'quantity' => 0,
        ]);
        
        $message = '✓ ' . $client->full_name . ' ingresó a sala de reuniones';
        
        if ($isBillable) {
            $message .= ' (Cliente ocasional - se facturará por separado)';
        } elseif ($request->master_id) {
            $master = Client::find($request->master_id);
            $message .= ' (Vinculado como invitado de ' . $master->full_name . ')';
        } elseif ($resolvedPlan['source'] === 'member_assignment') {
            $message .= ' (Plan asignado #' . $subscriptionId . ')';
        }
        
        return redirect()->route('admin.registro.index')
            ->with('success', $message);
    }
    
    public function finalizar($id)
    {
        $registro = UsageRecord::findOrFail($id);
        
        if ($registro->status !== 'in_progress') {
            return redirect()->route('admin.registro.index')
                ->with('warning', 'Este registro ya fue finalizado.');
        }
        
        $registro->update([
            'check_out' => now(),
            'status' => 'completed',
        ]);
        
        $registro->refresh();
        $duracion = $registro->duration_in_hours ?? 0;
        
        $this->actualizarHoras($registro);
        
        return redirect()->route('admin.registro.index')
            ->with('success', '✓ Registro finalizado. Duración: ' . number_format($duracion, 2) . ' horas');
    }

    public function finalizarConFecha(Request $request, $id)
    {
        $request->validate([
            'check_out' => 'required|date|after:2000-01-01'
        ]);
        
        $registro = UsageRecord::findOrFail($id);
        
        $checkOut = Carbon::parse($request->check_out);
        if ($checkOut <= $registro->check_in) {
            return redirect()->back()->with('error', 'La fecha de salida debe ser posterior a la fecha de entrada');
        }
        
        if ($checkOut > now()) {
            return redirect()->back()->with('error', 'La fecha de salida no puede ser en el futuro');
        }
        
        $registro->check_out = $checkOut;
        $registro->status = 'completed';
        $registro->save();
        
        $duracionEnHoras = $registro->check_in->diffInMinutes($registro->check_out) / 60;
        
        if ($registro->subscription_id) {
            $hoursTracking = HoursTracking::where('subscription_id', $registro->subscription_id)
                ->where('service_type', $registro->service_type)
                ->first();
            
            if ($hoursTracking) {
                $hoursTracking->hours_used += $duracionEnHoras;
                $hoursTracking->save();
            }
        }
        
        return redirect()->back()->with('success', "Registro cerrado correctamente. Duración: " . number_format($duracionEnHoras, 2) . " horas");
    }
    
    /**
     * ⭐ NUEVO: Actualizar registro con cambio de plan
     */
public function actualizarRegistro(Request $request)
{
    // ⭐ DEBUG
    \Log::info('=== ACTUALIZAR REGISTRO ===');
    \Log::info('Datos recibidos:', $request->all());
    
    $request->validate([
        'record_id' => 'required|exists:usage_records,id',
        'check_in' => 'required|date',
        'check_out' => 'required|date|after_or_equal:check_in',
        'plan_option' => 'required|in:keep,link,remove',
        'new_subscription_id' => 'nullable|exists:subscriptions,id',
    ]);
    
    $record = UsageRecord::with('subscription.plan')->findOrFail($request->record_id);
    
    \Log::info('Registro actual:', [
        'id' => $record->id,
        'old_subscription_id' => $record->subscription_id,
        'service_type' => $record->service_type,
    ]);
    
    // Guardar valores anteriores
    $oldSubscriptionId = $record->subscription_id;
    $oldDuration = $record->duration_in_hours ?? 0;
    
    // Actualizar fechas
    $record->check_in = $request->check_in;
    $record->check_out = $request->check_out;
    $record->save();
    $record->refresh();
    
    $newDuration = $record->duration_in_hours ?? 0;
    
    \Log::info('Plan option:', ['value' => $request->plan_option]);
    
    // ⭐ GESTIONAR CAMBIO DE PLAN
    switch ($request->plan_option) {
        case 'keep':
            \Log::info('KEEP - Mantener plan actual');
            if ($oldSubscriptionId && $record->service_type !== 'print') {
                $tracking = HoursTracking::where('subscription_id', $oldSubscriptionId)
                    ->where('service_type', $record->service_type)
                    ->first();
                
                if ($tracking) {
                    $tracking->hours_used = max(0, $tracking->hours_used - $oldDuration + $newDuration);
                    $tracking->save();
                    \Log::info('Hours tracking actualizado', ['new_hours' => $tracking->hours_used]);
                }
            }
            break;
            
        case 'link':
            \Log::info('LINK - Vincular a nuevo plan', ['new_subscription_id' => $request->new_subscription_id]);
            
            if ($request->new_subscription_id) {
                // Restar del plan anterior
                if ($oldSubscriptionId && $record->service_type !== 'print') {
                    $oldTracking = HoursTracking::where('subscription_id', $oldSubscriptionId)
                        ->where('service_type', $record->service_type)
                        ->first();
                    
                    if ($oldTracking) {
                        $oldTracking->decrement('hours_used', $oldDuration);
                        \Log::info('Restado del plan anterior', ['hours' => $oldDuration]);
                    }
                }
                
                // Actualizar subscription_id
                $record->subscription_id = $request->new_subscription_id;
                $record->save();
                \Log::info('Subscription ID actualizado', ['new_id' => $record->subscription_id]);
                
                // Sumar al plan nuevo
                if ($record->service_type !== 'print') {
                    $tracking = HoursTracking::firstOrCreate(
                        [
                            'subscription_id' => $request->new_subscription_id,
                            'service_type' => $record->service_type,
                        ],
                        [
                            'hours_used' => 0,
                            'total_hours_available' => 0,
                        ]
                    );
                    
                    if ($tracking->total_hours_available == 0) {
                        $subscription = Subscription::with('plan')->find($request->new_subscription_id);
                        if ($subscription && $subscription->plan) {
                            $tracking->total_hours_available = $record->service_type === 'cowork' 
                                ? $subscription->effective_cowork_hours
                                : $subscription->effective_meeting_room_hours;
                        }
                    }
                    
                    $tracking->increment('hours_used', $newDuration);
                    \Log::info('Sumado al nuevo plan', ['hours' => $newDuration]);
                }
            } else {
                \Log::warning('LINK seleccionado pero no hay new_subscription_id');
            }
            break;
            
        case 'remove':
            \Log::info('REMOVE - Quitar vinculación');
            
            // Restar del plan anterior
            if ($oldSubscriptionId && $record->service_type !== 'print') {
                $oldTracking = HoursTracking::where('subscription_id', $oldSubscriptionId)
                    ->where('service_type', $record->service_type)
                    ->first();
                
                if ($oldTracking) {
                    $oldTracking->decrement('hours_used', $oldDuration);
                    \Log::info('Restado del plan anterior (remove)', ['hours' => $oldDuration]);
                }
            }
            
            // Quitar subscription_id
            $record->subscription_id = null;
            $record->save();
            \Log::info('Subscription ID eliminado (null)');
            break;
    }
    
    \Log::info('=== FIN ACTUALIZAR REGISTRO ===');
    
    return redirect()->back()->with('success', 'Registro actualizado exitosamente. Duración: ' . number_format($newDuration, 2) . ' horas');
}

    /**
     * Método antiguo mantener por compatibilidad
     */
    public function updateRecord(Request $request)
    {
        return $this->actualizarRegistro($request);
    }

    public function impresion(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'cantidad' => 'required|integer|min:1',
            'fecha_impresion' => 'nullable|date|after:2000-01-01|before_or_equal:now',
        ]);
        
        $client = Client::with([
            'currentSubscription.plan',
            'invitedBy.currentSubscription.plan',
            'invitedBy.subscriptions.plan',
            'subscriptions.plan',
        ])->findOrFail($request->client_id);

        $resolvedPlan = $this->usagePlanResolver->resolve($client, 'print');
        if ($resolvedPlan['status'] === 'ambiguous') {
            return redirect()->route('admin.registro.index')->with('error', $resolvedPlan['message']);
        }

        $subscriptionId = $resolvedPlan['subscription_id'];
        $isBillable = $resolvedPlan['is_billable'];
        $subscriptionToCheck = $resolvedPlan['subscription'];
        $planToCheck = $resolvedPlan['plan'];

        if ($planToCheck && $subscriptionToCheck && !$resolvedPlan['is_pilot'] && $subscriptionToCheck->effective_prints_included <= 0) {
            return redirect()->route('admin.registro.index')
                ->with('error', 'El plan ' . $planToCheck->name . ' no incluye impresiones.');
        }

        $fechaImpresion = $request->filled('fecha_impresion')
            ? Carbon::parse($request->fecha_impresion)
            : now();
        
        UsageRecord::create([
            'client_id' => $client->id,
            'subscription_id' => $subscriptionId,
            'service_type' => 'print',
            'check_in' => $fechaImpresion,
            'check_out' => $fechaImpresion,
            'quantity' => $request->cantidad,
            'status' => 'completed',
            'registration_method' => 'manual',
            'is_billable' => $isBillable,
        ]);

        $message = 'Se registraron ' . $request->cantidad . ' impresiones para ' . $client->full_name;

        if ($isBillable) {
            $message .= ' (Cliente ocasional - se facturará por separado)';
        } elseif ($resolvedPlan['source'] === 'member_assignment') {
            $message .= ' (Plan asignado #' . $subscriptionId . ')';
        } elseif ($resolvedPlan['source'] === 'invited_by_current') {
            $message .= ' (Plan del cliente invitante #' . $subscriptionId . ')';
        }
        
        return redirect()->route('admin.registro.index')
            ->with('success', $message);
    }
    
    private function actualizarHoras(UsageRecord $registro)
    {
        if (!$registro->duration_in_hours || !$registro->subscription_id) {
            return;
        }
        
        $client = Client::with('invitedBy')->find($registro->client_id);
        
        if ($client && $client->invitedBy) {
            $trackingClientId = $client->invited_by_client_id;
        } else {
            $trackingClientId = $registro->client_id;
        }
        
        $tracking = HoursTracking::where('client_id', $trackingClientId)
            ->where('subscription_id', $registro->subscription_id)
            ->where('service_type', $registro->service_type)
            ->first();
        
        if ($tracking) {
            $tracking->increment('hours_used', $registro->duration_in_hours);
            $tracking->update(['last_updated' => now()]);
        } else {
            HoursTracking::create([
                'client_id' => $trackingClientId,
                'subscription_id' => $registro->subscription_id,
                'service_type' => $registro->service_type,
                'hours_used' => $registro->duration_in_hours,
                'total_hours_available' => $this->getTotalHorasDisponibles($registro),
                'last_updated' => now(),
            ]);
        }
    }
    
    private function getTotalHorasDisponibles($registro)
    {
        if (!$registro->subscription || !$registro->subscription->plan) {
            return 0;
        }

        $subscription = $registro->subscription;

        if ($registro->service_type === 'cowork') {
            return $subscription->effective_cowork_hours;
        } elseif ($registro->service_type === 'meeting_room') {
            return $subscription->effective_meeting_room_hours;
        }

        return 0;
    }
}
