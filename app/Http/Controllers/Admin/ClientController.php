<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Subscription;
use App\Models\Plan;
use App\Models\UsageRecord;
use App\Models\HoursTracking;
use App\Models\SubscriptionMember;
use App\Exports\DetalleRegistroExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class ClientController extends Controller
{
    /**
     * Listado de clientes activos
     * Ordenados: 1) Plan vigente, 2) Plan no vigente, 3) Sin plan
     * Con búsqueda por texto
     */
    public function index(Request $request)
    {
        $today = now()->toDateString();

        // Subconsulta para determinar si tiene plan vigente
        $query = Client::with('currentSubscription.plan')
            ->leftJoin('subscriptions', function($join) use ($today) {
                $join->on('clients.current_subscription_id', '=', 'subscriptions.id')
                     ->where('subscriptions.status', '=', 'active')
                     ->whereDate('subscriptions.start_date', '<=', $today)
                     ->where(function($query) use ($today) {
                         $query->whereDate('subscriptions.end_date', '>=', $today)
                               ->orWhereNull('subscriptions.end_date');
                     });
            })
            ->select('clients.*')
            ->selectRaw("CASE
                WHEN subscriptions.id IS NOT NULL THEN 0
                WHEN clients.subscription_status = 'active' THEN 1
                ELSE 2
            END as orden_plan")
            ->where('clients.client_status', 'active');

        // Búsqueda por texto (documento, nombre, apellido)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('clients.document_number', 'like', "%{$search}%")
                  ->orWhere('clients.first_name', 'like', "%{$search}%")
                  ->orWhere('clients.last_name', 'like', "%{$search}%");
            });
        }

        $clients = $query
            ->orderBy('orden_plan')
            ->orderBy('clients.first_name')
            ->paginate(50);

        return view('admin.clientes.index', compact('clients'));
    }

    /**
     * Formulario de creación de cliente
     */
    public function create()
    {
        $plans = Plan::active()->orderBy('name')->get();
        $clientesConPlan = Client::active()
            ->withActiveSubscription()
            ->with('currentSubscription.plan')
            ->orderBy('first_name')
            ->get();

        return view('admin.clientes.create', compact('plans', 'clientesConPlan'));
    }

    /**
     * Almacenar nuevo cliente
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'document_number' => 'required|unique:clients,document_number',
            'first_name' => 'required|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'tipo_cliente' => 'required|in:con_plan,invitado',
            // Campos para cliente con plan
            'plan_id' => 'required_if:tipo_cliente,con_plan|nullable|exists:plans,id',
            'start_date' => 'required_if:tipo_cliente,con_plan|nullable|date',
            'custom_cowork_hours' => 'nullable|numeric|min:0',
            'custom_meeting_room_hours' => 'nullable|numeric|min:0',
            'custom_prints_included' => 'nullable|integer|min:0',
            'custom_events_included' => 'nullable|integer|min:0',
            'custom_monthly_price' => 'nullable|numeric|min:0',
            // Campos para cliente invitado
            'service_type' => 'required_if:tipo_cliente,invitado|nullable|in:cowork,meeting_room',
            'master_client_id' => 'nullable|exists:clients,id',
        ]);

        DB::beginTransaction();
        try {
            // Crear el cliente
            $client = Client::create([
                'document_number' => $validated['document_number'],
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'] ?? '',
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'client_status' => 'active',
                'subscription_status' => 'expired',
            ]);

            $client->update([
                'invitation_link' => 'https://conectacowork.com/bienvenida?cliente=' . urlencode($client->full_name),
            ]);

            if ($validated['tipo_cliente'] === 'con_plan') {
                // Cliente con plan propio
                $plan = Plan::findOrFail($validated['plan_id']);
                if ($plan->is_ultra_custom) {
                    $validated = array_merge($validated, $request->validate([
                        'custom_cowork_hours' => 'required|numeric|min:0',
                        'custom_meeting_room_hours' => 'required|numeric|min:0',
                        'custom_prints_included' => 'required|integer|min:0',
                        'custom_events_included' => 'required|integer|min:0',
                        'custom_monthly_price' => 'required|numeric|min:0',
                    ]));
                }
                $startDate = Carbon::parse($validated['start_date']);
                $endDate = $startDate->copy()->addMonth();
                $isUltraCustom = (bool) $plan->is_ultra_custom;

                // Crear suscripción
                $subscription = Subscription::create([
                    'client_id' => $client->id,
                    'plan_id' => $plan->id,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'status' => 'active',
                    'monthly_price' => $isUltraCustom ? $validated['custom_monthly_price'] : $plan->price,
                    'billing_cycle' => 'monthly',
                    'next_billing_date' => $endDate,
                    'auto_renew' => false,
                    'is_ultra_custom' => $isUltraCustom,
                    'custom_cowork_hours' => $isUltraCustom ? $validated['custom_cowork_hours'] : null,
                    'custom_meeting_room_hours' => $isUltraCustom ? $validated['custom_meeting_room_hours'] : null,
                    'custom_prints_included' => $isUltraCustom ? $validated['custom_prints_included'] : null,
                    'custom_events_included' => $isUltraCustom ? $validated['custom_events_included'] : null,
                ]);

                // Actualizar cliente con la suscripción
                $client->update([
                    'subscription_status' => 'active',
                    'current_subscription_id' => $subscription->id,
                ]);

                // Crear registros de horas
                $effectiveCoworkHours = $subscription->effective_cowork_hours;
                $effectiveMeetingRoomHours = $subscription->effective_meeting_room_hours;
                if ($plan->is_pilot || $effectiveCoworkHours > 0) {
                    HoursTracking::create([
                        'client_id' => $client->id,
                        'subscription_id' => $subscription->id,
                        'service_type' => 'cowork',
                        'hours_used' => 0,
                        'total_hours_available' => $effectiveCoworkHours,
                        'last_updated' => now(),
                    ]);
                }

                if ($plan->is_pilot || $effectiveMeetingRoomHours > 0) {
                    HoursTracking::create([
                        'client_id' => $client->id,
                        'subscription_id' => $subscription->id,
                        'service_type' => 'meeting_room',
                        'hours_used' => 0,
                        'total_hours_available' => $effectiveMeetingRoomHours,
                        'last_updated' => now(),
                    ]);
                }

                DB::commit();
                return redirect()->route('admin.clientes.index')
                    ->with('success', 'Cliente ' . $client->full_name . ' creado exitosamente con plan ' . $plan->name)
                    ->with('success_client_name', $client->full_name)
                    ->with('success_client_doc', $client->document_number)
                    ->with('whatsapp_open_url', $client->whatsapp_invitation_url);

            } else {
                // Cliente invitado (por horas)
                $masterClientId = $validated['master_client_id'] ?? null;

                if ($masterClientId) {
                    $master = Client::with('currentSubscription.plan')->findOrFail($masterClientId);

                    if (!$master->currentSubscription || !$master->currentSubscription->plan) {
                        throw new \Exception('El cliente anfitri?n no tiene un plan activo.');
                    }

                    // Vincular como invitado
                    $client->update([
                        'invited_by_client_id' => $master->id,
                        'current_subscription_id' => $master->current_subscription_id,
                        'subscription_status' => 'active',
                    ]);

                    // Crear registro de uso inicial
                    UsageRecord::create([
                        'client_id' => $client->id,
                        'subscription_id' => $master->current_subscription_id,
                        'service_type' => $validated['service_type'],
                        'check_in' => now(),
                        'status' => 'in_progress',
                        'is_billable' => false,
                        'registration_method' => 'manual',
                        'quantity' => 0,
                    ]);

                    DB::commit();
                    return redirect()->route('admin.registro.index', ['doc' => $client->document_number])
                        ->with('success', 'Cliente ' . $client->full_name . ' invitado creado y vinculado a ' . $master->full_name)
                        ->with('success_client_name', $client->full_name)
                        ->with('success_client_doc', $client->document_number)
                        ->with('whatsapp_open_url', $client->whatsapp_invitation_url);
                }

                UsageRecord::create([
                    'client_id' => $client->id,
                    'subscription_id' => null,
                    'service_type' => $validated['service_type'],
                    'check_in' => now(),
                    'status' => 'in_progress',
                    'is_billable' => true,
                    'registration_method' => 'manual',
                    'quantity' => 0,
                ]);

                DB::commit();
                return redirect()->route('admin.registro.index', ['doc' => $client->document_number])
                    ->with('success', 'Cliente ' . $client->full_name . ' por horas creado sin plan asociado.')
                    ->with('success_client_name', $client->full_name)
                    ->with('success_client_doc', $client->document_number)
                    ->with('whatsapp_open_url', $client->whatsapp_invitation_url);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al crear cliente: ' . $e->getMessage());
        }
    }

    /**
     * Formulario de edición de cliente
     */
    public function edit(Client $client)
    {
        return view('admin.clientes.edit', compact('client'));
    }

    /**
     * Actualizar cliente
     */
    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);

        $client->update($validated);

        return redirect()->route('admin.clientes.index')
            ->with('success', 'Cliente actualizado exitosamente.');
    }

    /**
     * Eliminar cliente (soft delete)
     */
    public function destroy(Client $client)
    {
        $client->update(['client_status' => 'deleted']);

        return redirect()->route('admin.clientes.index')
            ->with('success', 'Cliente eliminado exitosamente.');
    }

    /**
     * Ver/Gestionar plan del cliente
     */
    public function plan(Client $client)
    {
        $client->load([
            'subscriptions.plan',
            'subscriptions.members.client',
            'hoursTracking',
            'invitedBy.currentSubscription.plan',
            'guests'
        ]);

        $plans = Plan::active()->get();
        $assignableClients = collect([$client])
            ->merge($client->guests ?? collect())
            ->unique('id')
            ->sortBy('full_name')
            ->values();

        // Obtener plan vigente (fecha actual entre start_date y end_date)
        $planVigente = $client->subscriptions()
            ->where('status', 'active')
            ->whereDate('start_date', '<=', now())
            ->where(function($query) {
                $query->whereDate('end_date', '>=', now())
                      ->orWhereNull('end_date');
            })
            ->with('plan')
            ->first();

        $planInvitador = null;
        $detalleRegistroSubscription = $planVigente;
        if (!$detalleRegistroSubscription && $client->invitedBy && $client->invitedBy->currentSubscription) {
            $planInvitador = $client->invitedBy->currentSubscription;
            $detalleRegistroSubscription = $planInvitador;
        }

        // Obtener plan futuro (start_date > fecha actual)
        $planFuturo = $client->subscriptions()
            ->where('status', 'active')
            ->whereDate('start_date', '>', now())
            ->with('plan')
            ->first();

        // Historial de planes (ordenado por fecha)
        $historialPlanes = $client->subscriptions()
            ->with('plan')
            ->orderBy('start_date', 'desc')
            ->get();

        $assignableSubscriptions = $historialPlanes
            ->filter(function ($subscription) {
                return $subscription->status === 'active'
                    && (!$subscription->end_date || $subscription->end_date->gte(now()->startOfDay()));
            })
            ->values();

        // Calcular consumo si hay plan vigente
        $consumo = null;
        if ($planVigente) {
            $isPilotPlan = (bool) $planVigente->plan->is_pilot;
            $coworkContratadas = $planVigente->effective_cowork_hours;
            $salaContratadas = $planVigente->effective_meeting_room_hours;
            $impresionesContratadas = $planVigente->effective_prints_included;
            $eventosContratados = $planVigente->effective_events_included;
            $hoursCowork = HoursTracking::where('subscription_id', $planVigente->id)
                ->where('service_type', 'cowork')
                ->first();

            $hoursSala = HoursTracking::where('subscription_id', $planVigente->id)
                ->where('service_type', 'meeting_room')
                ->first();

            // Calcular impresiones usadas
            $impresionesUsadas = UsageRecord::where('subscription_id', $planVigente->id)
                ->where('service_type', 'print')
                ->sum('quantity');

            $consumo = [
                'cowork' => [
                    'contratadas' => $isPilotPlan ? null : $coworkContratadas,
                    'usadas' => $hoursCowork ? $hoursCowork->hours_used : 0,
                    'restantes' => $isPilotPlan ? null : $coworkContratadas - ($hoursCowork ? $hoursCowork->hours_used : 0),
                ],
                'sala' => [
                    'contratadas' => $isPilotPlan ? null : $salaContratadas,
                    'usadas' => $hoursSala ? $hoursSala->hours_used : 0,
                    'restantes' => $isPilotPlan ? null : $salaContratadas - ($hoursSala ? $hoursSala->hours_used : 0),
                ],
                'impresiones' => [
                    'contratadas' => $impresionesContratadas,
                    'usadas' => $impresionesUsadas,
                    'restantes' => $impresionesContratadas - $impresionesUsadas,
                ],
                'eventos' => $eventosContratados,
            ];
        }

        return view('admin.clientes.plan', compact(
            'client',
            'plans',
            'planVigente',
            'planInvitador',
            'detalleRegistroSubscription',
            'planFuturo',
            'historialPlanes',
            'consumo',
            'assignableClients',
            'assignableSubscriptions'
        ));
    }

    public function storeSubscriptionMember(Request $request, Client $client, Subscription $subscription)
    {
        if ($subscription->client_id !== $client->id) {
            abort(404);
        }

        $assignableIds = collect([$client->id])
            ->merge($client->guests()->pluck('id'))
            ->unique()
            ->values();

        $validated = $request->validate([
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'can_use_cowork' => ['nullable', 'boolean'],
            'can_use_meeting_room' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'is_default_cowork' => ['nullable', 'boolean'],
            'is_default_meeting_room' => ['nullable', 'boolean'],
        ]);

        if (!$assignableIds->contains((int) $validated['client_id'])) {
            return redirect()->back()->with('error', 'Solo puede asignar al cliente principal o a sus invitados vinculados.');
        }

        $canUseCowork = $request->boolean('can_use_cowork');
        $canUseMeetingRoom = $request->boolean('can_use_meeting_room');

        if (!$canUseCowork && !$canUseMeetingRoom) {
            return redirect()->back()->with('error', 'Seleccione al menos un servicio para esta asignacion.');
        }

        DB::transaction(function () use ($subscription, $validated, $canUseCowork, $canUseMeetingRoom, $request) {
            $isDefaultCowork = $request->boolean('is_default_cowork')
                || ($request->boolean('is_default') && $canUseCowork);
            $isDefaultMeetingRoom = $request->boolean('is_default_meeting_room')
                || ($request->boolean('is_default') && $canUseMeetingRoom);
            $isDefault = $isDefaultCowork || $isDefaultMeetingRoom;

            if ($isDefaultCowork) {
                SubscriptionMember::where('client_id', $validated['client_id'])
                    ->where('subscription_id', $subscription->id)
                    ->where('can_use_cowork', true)
                    ->update(['is_default' => false, 'is_default_cowork' => false]);
            }

            if ($isDefaultMeetingRoom) {
                SubscriptionMember::where('client_id', $validated['client_id'])
                    ->where('subscription_id', $subscription->id)
                    ->where('can_use_meeting_room', true)
                    ->update(['is_default' => false, 'is_default_meeting_room' => false]);
            }

            SubscriptionMember::updateOrCreate(
                [
                    'subscription_id' => $subscription->id,
                    'client_id' => $validated['client_id'],
                ],
                [
                    'can_use_cowork' => $canUseCowork,
                    'can_use_meeting_room' => $canUseMeetingRoom,
                    'is_default' => $isDefault,
                    'is_default_cowork' => $isDefaultCowork,
                    'is_default_meeting_room' => $isDefaultMeetingRoom,
                ]
            );
        });

        return redirect()->back()->with('success', 'Persona asignada al plan correctamente.');
    }

    public function destroySubscriptionMember(Client $client, Subscription $subscription, SubscriptionMember $member)
    {
        if ($subscription->client_id !== $client->id || $member->subscription_id !== $subscription->id) {
            abort(404);
        }

        $member->delete();

        return redirect()->back()->with('success', 'Asignacion eliminada del plan.');
    }

    /**
     * Formulario para suscribir cliente a un plan
     */
    public function suscribirForm(Client $client)
    {
        $plans = Plan::active()->orderBy('name')->get();
        return view('admin.clientes.suscribir', compact('client', 'plans'));
    }

    /**
     * Suscribir cliente a un plan
     */
    public function suscribir(Request $request, Client $client)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'start_date' => 'required|date',
            'custom_cowork_hours' => 'nullable|numeric|min:0',
            'custom_meeting_room_hours' => 'nullable|numeric|min:0',
            'custom_prints_included' => 'nullable|integer|min:0',
            'custom_events_included' => 'nullable|integer|min:0',
            'custom_monthly_price' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $plan = Plan::findOrFail($validated['plan_id']);
            if ($plan->is_ultra_custom) {
                $validated = array_merge($validated, $request->validate([
                    'custom_cowork_hours' => 'required|numeric|min:0',
                    'custom_meeting_room_hours' => 'required|numeric|min:0',
                    'custom_prints_included' => 'required|integer|min:0',
                    'custom_events_included' => 'required|integer|min:0',
                    'custom_monthly_price' => 'required|numeric|min:0',
                ]));
            }
            $currentSub = $client->currentSubscription;
            $pilotToDefinitive = $currentSub && $currentSub->plan && $currentSub->plan->is_pilot && !$plan->is_pilot;
            $startDate = $pilotToDefinitive ? now()->startOfDay() : Carbon::parse($validated['start_date']);
            $endDate = $startDate->copy()->addMonth();
            $isUltraCustom = (bool) $plan->is_ultra_custom;

            // Crear suscripción
            $subscription = Subscription::create([
                'client_id' => $client->id,
                'plan_id' => $plan->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => 'active',
                'monthly_price' => $isUltraCustom ? $validated['custom_monthly_price'] : $plan->price,
                'billing_cycle' => 'monthly',
                'next_billing_date' => $endDate,
                'auto_renew' => false,
                'is_ultra_custom' => $isUltraCustom,
                'custom_cowork_hours' => $isUltraCustom ? $validated['custom_cowork_hours'] : null,
                'custom_meeting_room_hours' => $isUltraCustom ? $validated['custom_meeting_room_hours'] : null,
                'custom_prints_included' => $isUltraCustom ? $validated['custom_prints_included'] : null,
                'custom_events_included' => $isUltraCustom ? $validated['custom_events_included'] : null,
            ]);

            // Siempre actualizar current_subscription_id al suscribir
            $client->update([
                'subscription_status' => 'active',
                'current_subscription_id' => $subscription->id,
            ]);

            if ($pilotToDefinitive && $currentSub) {
                $currentSub->update([
                    'status' => 'inactive',
                    'end_date' => now()->startOfDay(),
                ]);

                UsageRecord::where('client_id', $client->id)
                    ->where('status', 'in_progress')
                    ->where('subscription_id', $currentSub->id)
                    ->update(['subscription_id' => $subscription->id]);
            }

            // Crear registros de horas
            $effectiveCoworkHours = $subscription->effective_cowork_hours;
            $effectiveMeetingRoomHours = $subscription->effective_meeting_room_hours;
            if ($plan->is_pilot || $effectiveCoworkHours > 0) {
                HoursTracking::create([
                    'client_id' => $client->id,
                    'subscription_id' => $subscription->id,
                    'service_type' => 'cowork',
                    'hours_used' => 0,
                    'total_hours_available' => $effectiveCoworkHours,
                    'last_updated' => now(),
                ]);
            }

            if ($plan->is_pilot || $effectiveMeetingRoomHours > 0) {
                HoursTracking::create([
                    'client_id' => $client->id,
                    'subscription_id' => $subscription->id,
                    'service_type' => 'meeting_room',
                    'hours_used' => 0,
                    'total_hours_available' => $effectiveMeetingRoomHours,
                    'last_updated' => now(),
                ]);
            }

            DB::commit();
            return redirect()->route('admin.clientes.plan', $client)
                ->with('success', 'Cliente suscrito exitosamente al plan ' . $plan->name);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error al suscribir: ' . $e->getMessage());
        }
    }

    /**
     * Renovar plan (extiende el plan actual)
     */
    public function renovar(Request $request, Client $client)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        DB::beginTransaction();
        try {
            $plan = Plan::findOrFail($validated['plan_id']);

            // Buscar suscripción actual
            $currentSub = $client->currentSubscription;

            // Fecha de inicio es el día después del fin del plan actual
            $pilotToDefinitive = $currentSub && $currentSub->plan && $currentSub->plan->is_pilot && !$plan->is_pilot;
            $startDate = $pilotToDefinitive
                ? now()->startOfDay()
                : ($currentSub && $currentSub->end_date
                    ? $currentSub->end_date->copy()->addDay()
                    : now());
            $endDate = $startDate->copy()->addMonth();

            // Crear nueva suscripción
            $subscription = Subscription::create([
                'client_id' => $client->id,
                'plan_id' => $plan->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => 'active',
                'monthly_price' => $plan->price,
                'billing_cycle' => 'monthly',
                'next_billing_date' => $endDate,
                'auto_renew' => false,
                'is_ultra_custom' => (bool) $plan->is_ultra_custom,
                'custom_cowork_hours' => null,
                'custom_meeting_room_hours' => null,
                'custom_prints_included' => null,
                'custom_events_included' => null,
            ]);

            if ($pilotToDefinitive && $currentSub) {
                $currentSub->update([
                    'status' => 'inactive',
                    'end_date' => now()->startOfDay(),
                ]);

                UsageRecord::where('client_id', $client->id)
                    ->where('status', 'in_progress')
                    ->where('subscription_id', $currentSub->id)
                    ->update(['subscription_id' => $subscription->id]);
            }

            // Siempre actualizar current_subscription_id al renovar
            $client->update([
                'subscription_status' => 'active',
                'current_subscription_id' => $subscription->id,
            ]);

            // Crear registros de horas para la nueva suscripción
            $effectiveCoworkHours = $subscription->effective_cowork_hours;
            $effectiveMeetingRoomHours = $subscription->effective_meeting_room_hours;
            if ($plan->is_pilot || $effectiveCoworkHours > 0) {
                HoursTracking::create([
                    'client_id' => $client->id,
                    'subscription_id' => $subscription->id,
                    'service_type' => 'cowork',
                    'hours_used' => 0,
                    'total_hours_available' => $effectiveCoworkHours,
                    'last_updated' => now(),
                ]);
            }

            if ($plan->is_pilot || $effectiveMeetingRoomHours > 0) {
                HoursTracking::create([
                    'client_id' => $client->id,
                    'subscription_id' => $subscription->id,
                    'service_type' => 'meeting_room',
                    'hours_used' => 0,
                    'total_hours_available' => $effectiveMeetingRoomHours,
                    'last_updated' => now(),
                ]);
            }

            DB::commit();
            return redirect()->route('admin.clientes.plan', $client)
                ->with('success', 'Plan renovado exitosamente.' . ($endDate ? ' Nueva fecha de fin: ' . $endDate->format('d/m/Y') : ''));

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error al renovar: ' . $e->getMessage());
        }
    }

    /**
     * Modificar plan actual
     */
    public function modificarPlan(Request $request, Client $client)
    {
        $validated = $request->validate([
            'subscription_id' => 'required|exists:subscriptions,id',
            'plan_id' => 'required|exists:plans,id',
            'start_date' => 'required|date',
        ]);

        DB::beginTransaction();
        try {
            $subscription = Subscription::findOrFail($validated['subscription_id']);
            $newPlan = Plan::findOrFail($validated['plan_id']);
            $pilotToDefinitive = $subscription->plan && $subscription->plan->is_pilot && !$newPlan->is_pilot;
            $startDate = $pilotToDefinitive ? now()->startOfDay() : Carbon::parse($validated['start_date']);
            $endDate = $startDate->copy()->addMonth();

            // Actualizar suscripción
            $subscription->update([
                'plan_id' => $newPlan->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'monthly_price' => $newPlan->price,
                'next_billing_date' => $endDate,
                'is_ultra_custom' => (bool) $newPlan->is_ultra_custom,
                'custom_cowork_hours' => null,
                'custom_meeting_room_hours' => null,
                'custom_prints_included' => null,
                'custom_events_included' => null,
            ]);

            $subscription->refresh();

            // Actualizar o crear registros de horas
            HoursTracking::updateOrCreate(
                [
                    'subscription_id' => $subscription->id,
                    'service_type' => 'cowork',
                ],
                [
                    'client_id' => $client->id,
                    'total_hours_available' => $subscription->effective_cowork_hours,
                    'last_updated' => now(),
                ]
            );

            HoursTracking::updateOrCreate(
                [
                    'subscription_id' => $subscription->id,
                    'service_type' => 'meeting_room',
                ],
                [
                    'client_id' => $client->id,
                    'total_hours_available' => $subscription->effective_meeting_room_hours,
                    'last_updated' => now(),
                ]
            );

            DB::commit();
            return redirect()->route('admin.clientes.plan', $client)
                ->with('success', 'Plan modificado exitosamente a ' . $newPlan->name);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error al modificar: ' . $e->getMessage());
        }
    }

    /**
     * Actualizar cupos contratados para una suscripción ultra personalizada.
     */
    public function actualizarCuposUltra(Request $request, Client $client)
    {
        $validated = $request->validate([
            'subscription_id' => 'required|exists:subscriptions,id',
            'custom_cowork_hours' => 'required|numeric|min:0',
            'custom_meeting_room_hours' => 'required|numeric|min:0',
            'custom_prints_included' => 'required|integer|min:0',
            'custom_events_included' => 'required|integer|min:0',
            'custom_monthly_price' => 'required|numeric|min:0',
        ]);

        $subscription = Subscription::with('plan')
            ->where('id', $validated['subscription_id'])
            ->where('client_id', $client->id)
            ->first();

        if (!$subscription) {
            return redirect()->back()->with('error', 'Suscripción no encontrada para este cliente.');
        }

        if (!$subscription->plan || !$subscription->plan->is_ultra_custom) {
            return redirect()->back()->with('error', 'Solo se pueden personalizar cupos en planes ultra personalizados.');
        }

        DB::beginTransaction();
        try {
            $subscription->update([
                'is_ultra_custom' => true,
                'custom_cowork_hours' => $validated['custom_cowork_hours'],
                'custom_meeting_room_hours' => $validated['custom_meeting_room_hours'],
                'custom_prints_included' => $validated['custom_prints_included'],
                'custom_events_included' => $validated['custom_events_included'],
                'monthly_price' => $validated['custom_monthly_price'],
            ]);

            HoursTracking::updateOrCreate(
                [
                    'subscription_id' => $subscription->id,
                    'service_type' => 'cowork',
                ],
                [
                    'client_id' => $client->id,
                    'total_hours_available' => $subscription->effective_cowork_hours,
                    'last_updated' => now(),
                ]
            );

            HoursTracking::updateOrCreate(
                [
                    'subscription_id' => $subscription->id,
                    'service_type' => 'meeting_room',
                ],
                [
                    'client_id' => $client->id,
                    'total_hours_available' => $subscription->effective_meeting_room_hours,
                    'last_updated' => now(),
                ]
            );

            DB::commit();
            return redirect()->route('admin.clientes.plan', $client)
                ->with('success', 'Cupos del plan ultra personalizados actualizados correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al actualizar cupos ultra: ' . $e->getMessage());
        }
    }

    /**
     * Iniciar plan futuro
     */
    public function iniciarPlan(Request $request, Client $client)
    {
        $validated = $request->validate([
            'subscription_id' => 'required|exists:subscriptions,id',
        ]);

        DB::beginTransaction();
        try {
            $subscription = Subscription::findOrFail($validated['subscription_id']);

            // Actualizar fecha de inicio a hoy
            $startDate = now();
            $subscription->load('plan');
            $endDate = $startDate->copy()->addMonth();

            $subscription->update([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'next_billing_date' => $endDate,
            ]);

            // Actualizar cliente
            $client->update([
                'subscription_status' => 'active',
                'current_subscription_id' => $subscription->id,
            ]);

            DB::commit();
            return redirect()->route('admin.clientes.plan', $client)
                ->with('success', 'Plan iniciado exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error al iniciar plan: ' . $e->getMessage());
        }
    }

    /**
     * Recalcular hours_tracking desde los registros del plan.
     */
    public function recalcularHorasTracking(Request $request, Client $client)
    {
        $validated = $request->validate([
            'subscription_id' => 'required|exists:subscriptions,id',
        ]);

        $subscription = Subscription::with('plan')
            ->where('id', $validated['subscription_id'])
            ->where('client_id', $client->id)
            ->first();

        if (!$subscription) {
            abort(404);
        }

        DB::beginTransaction();
        try {
            $records = UsageRecord::where('subscription_id', $subscription->id)
                ->whereIn('service_type', ['cowork', 'meeting_room'])
                ->whereNotNull('check_out')
                ->get(['service_type', 'check_in', 'check_out']);

            $totals = [
                'cowork' => 0,
                'meeting_room' => 0,
            ];

            foreach ($records as $record) {
                $hours = $record->check_in->diffInMinutes($record->check_out) / 60;
                $totals[$record->service_type] += $hours;
            }

            $coworkTracking = HoursTracking::updateOrCreate(
                [
                    'subscription_id' => $subscription->id,
                    'service_type' => 'cowork',
                ],
                [
                    'client_id' => $client->id,
                    'hours_used' => $totals['cowork'],
                    'total_hours_available' => $subscription->effective_cowork_hours,
                    'last_updated' => now(),
                ]
            );

            $salaTracking = HoursTracking::updateOrCreate(
                [
                    'subscription_id' => $subscription->id,
                    'service_type' => 'meeting_room',
                ],
                [
                    'client_id' => $client->id,
                    'hours_used' => $totals['meeting_room'],
                    'total_hours_available' => $subscription->effective_meeting_room_hours,
                    'last_updated' => now(),
                ]
            );

            DB::commit();
            return redirect()->route('admin.clientes.plan', $client)
                ->with('success', 'Horas recalculadas correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al recalcular: ' . $e->getMessage());
        }
    }

    /**
     * Vista limitada para usuarios regulares
     */
    public function indexRegular()
    {
        $clients = Client::with('currentSubscription.plan')
            ->where('client_status', 'active')
            ->orderBy('first_name')
            ->paginate(50);

        return view('regular.clientes.index', compact('clients'));
    }

    /**
     * Crear cliente rápido (sin plan, para invitados/ocasionales)
     */
    public function storeQuick(Request $request)
    {
        $request->validate([
            'document_number' => 'required|string|max:20|unique:clients,document_number',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
        ]);

        $client = Client::create([
            'document_number' => $request->document_number,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone' => $request->phone,
            'email' => $request->email,
            'client_status' => 'active',
            'subscription_status' => 'expired',
        ]);

        return redirect()->route('admin.registro.index', [
            'doc' => $client->document_number,
            'open_service' => 1,
        ])
            ->with('success', 'Cliente ocasional ' . $client->full_name . ' creado exitosamente.');
    }

    /**
     * Vincular un cliente como invitado de otro (master)
     */
    public function linkGuest(Request $request)
    {
        $request->validate([
            'guest_id' => 'required|exists:clients,id',
            'master_id' => 'required|exists:clients,id',
        ]);

        $guest = Client::findOrFail($request->guest_id);
        $master = Client::with('currentSubscription.plan')->findOrFail($request->master_id);

        // Verificar que el master tenga un plan activo
        if (!$master->currentSubscription || !$master->currentSubscription->plan) {
            return redirect()->back()
                ->with('error', $master->full_name . ' no tiene un plan activo.');
        }

        // Validar: Si el invitado tiene servicio activo, verificar que el plan del master lo incluya
        $servicioActivo = UsageRecord::where('client_id', $guest->id)
            ->where('status', 'in_progress')
            ->whereDate('check_in', today())
            ->first();

        if ($servicioActivo) {
            $masterSubscription = $master->currentSubscription;
            $plan = $masterSubscription->plan;
            $effectiveCoworkHours = $masterSubscription->effective_cowork_hours;
            $effectiveMeetingRoomHours = $masterSubscription->effective_meeting_room_hours;

            if (!$plan->is_pilot && $servicioActivo->service_type === 'cowork' && $effectiveCoworkHours <= 0) {
                return redirect()->back()
                    ->with('error', 'No se puede vincular: ' . $guest->full_name . ' está usando cowork pero el plan "' . $plan->name . '" NO incluye horas de cowork.');
            }

            if (!$plan->is_pilot && $servicioActivo->service_type === 'meeting_room' && $effectiveMeetingRoomHours <= 0) {
                return redirect()->back()
                    ->with('error', 'No se puede vincular: ' . $guest->full_name . ' está usando sala de reuniones pero el plan "' . $plan->name . '" NO incluye horas de sala.');
            }

            // Actualizar hours_tracking: Transferir horas al master
            $duracionActual = now()->diffInMinutes($servicioActivo->check_in) / 60;

            $tracking = HoursTracking::firstOrCreate(
                [
                    'client_id' => $master->id,
                    'subscription_id' => $master->current_subscription_id,
                    'service_type' => $servicioActivo->service_type,
                ],
                [
                    'hours_used' => 0,
                    'total_hours_available' => $servicioActivo->service_type === 'cowork'
                        ? $effectiveCoworkHours
                        : $effectiveMeetingRoomHours,
                ]
            );

            $tracking->increment('hours_used', $duracionActual);
            $tracking->update(['last_updated' => now()]);

            $servicioActivo->update([
                'subscription_id' => $master->current_subscription_id,
                'is_billable' => false,
            ]);
        }

        // Vincular el invitado al master
        $guest->update([
            'invited_by_client_id' => $master->id,
            'current_subscription_id' => $master->current_subscription_id,
            'subscription_status' => 'active',
        ]);

        return redirect()->back()
            ->with('success', $guest->full_name . ' ha sido vinculado como invitado de ' . $master->full_name);
    }

    /**
     * Desvincular un cliente invitado
     */
    public function unlinkGuest(Request $request, $id)
    {
        try {
            $guest = Client::with('currentSubscription.plan')->findOrFail($id);

            if (!$guest->invited_by_client_id) {
                return redirect()->back()->with('error', 'Este cliente no está vinculado a ningún plan.');
            }

            $master = Client::find($guest->invited_by_client_id);
            $masterName = $master->full_name ?? 'desconocido';

            $masterSubscriptionIds = Subscription::where('client_id', $master->id)
                ->pluck('id')
                ->toArray();

            // Verificar si hay registros activos
            $registrosActivos = UsageRecord::where('client_id', $guest->id)
                ->whereIn('subscription_id', $masterSubscriptionIds)
                ->where('status', 'in_progress')
                ->get();

            if ($registrosActivos->count() > 0) {
                $tiposServicios = $registrosActivos->pluck('service_type')->unique()->map(function($type) {
                    return $type === 'cowork' ? 'Cowork' : ($type === 'meeting_room' ? 'Sala de Reuniones' : 'Impresión');
                })->join(', ');

                return redirect()->back()->with('error',
                    "No se puede desvincular porque {$guest->full_name} tiene registros activos sin cerrar ({$tiposServicios}). " .
                    "Por favor, finaliza todos los registros abiertos antes de desvincular."
                );
            }

            // Proceder con la desvinculación
            $registrosCompletados = UsageRecord::where('client_id', $guest->id)
                ->whereIn('subscription_id', $masterSubscriptionIds)
                ->where('status', 'completed')
                ->get();

            if ($guest->currentSubscription && $guest->currentSubscription->status === 'active') {
                $planPropio = $guest->currentSubscription;
                $planDetails = $planPropio->plan;
                $effectiveCoworkHours = $planPropio->effective_cowork_hours;
                $effectiveMeetingRoomHours = $planPropio->effective_meeting_room_hours;

                foreach ($registrosCompletados as $registro) {
                    $nuevoSubscriptionId = null;

                    if ($registro->service_type === 'cowork' && ($planDetails->is_pilot || $effectiveCoworkHours > 0)) {
                        $nuevoSubscriptionId = $planPropio->id;
                    } elseif ($registro->service_type === 'meeting_room' && ($planDetails->is_pilot || $effectiveMeetingRoomHours > 0)) {
                        $nuevoSubscriptionId = $planPropio->id;
                    } elseif ($registro->service_type === 'print') {
                        $nuevoSubscriptionId = $planPropio->id;
                    }

                    $registro->subscription_id = $nuevoSubscriptionId;
                    $registro->save();
                }

                $mensaje = "Cliente desvinculado exitosamente de {$masterName}. Los registros completados se reasignaron a su plan propio donde fue posible.";

            } else {
                UsageRecord::where('client_id', $guest->id)
                    ->whereIn('subscription_id', $masterSubscriptionIds)
                    ->where('status', 'completed')
                    ->update(['subscription_id' => null]);

                $mensaje = "Cliente desvinculado exitosamente de {$masterName}. Los registros completados quedaron como ocasionales.";
            }

            // Desvincular el cliente
            $guest->invited_by_client_id = null;
            $guest->current_subscription_id = null;
            $guest->subscription_status = 'expired';
            $guest->save();

            return redirect()->back()->with('success', $mensaje);

        } catch (\Exception $e) {
            \Log::error('Error al desvincular cliente: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al desvincular: ' . $e->getMessage());
        }
    }

    /**
     * Detalle de registro del cliente
     * Muestra todos los registros de la suscripción (incluye invitados)
     */
    public function detalleRegistro(Client $client, Subscription $subscription)
    {
        // Cargar el plan de la suscripción
        $subscription->load('plan');

        $highlightClientId = null;
        if ($client->invited_by_client_id && $subscription->client_id === $client->invited_by_client_id) {
            $highlightClientId = $client->id;
        }

        $registrosQuery = UsageRecord::with('client')
            ->where('subscription_id', $subscription->id);

        $registros = $registrosQuery->orderBy('check_in', 'desc')->paginate(50);

        $clientSubscriptions = $client->subscriptions()
            ->with('plan')
            ->orderBy('start_date', 'desc')
            ->get();

        // Obtener todos los registros para cálculos (sin paginación)
        $todosRegistrosQuery = UsageRecord::where('subscription_id', $subscription->id);
        $todosRegistros = $todosRegistrosQuery->get();

        // Calcular horas usadas de cowork
        $horasCoworkUsadas = 0;
        foreach ($todosRegistros->where('service_type', 'cowork') as $reg) {
            if ($reg->check_out) {
                $horasCoworkUsadas += $reg->check_in->diffInMinutes($reg->check_out) / 60;
            }
        }

        // Calcular horas usadas de sala
        $horasSalaUsadas = 0;
        foreach ($todosRegistros->where('service_type', 'meeting_room') as $reg) {
            if ($reg->check_out) {
                $horasSalaUsadas += $reg->check_in->diffInMinutes($reg->check_out) / 60;
            }
        }

        // Calcular impresiones usadas
        $impresionesUsadas = $todosRegistros->where('service_type', 'print')->sum('quantity');

        // Horas contratadas del plan
        $horasCoworkContratadas = $subscription->effective_cowork_hours;
        $horasSalaContratadas = $subscription->effective_meeting_room_hours;
        $impresionesContratadas = $subscription->effective_prints_included;

        // Horas restantes
        $horasCoworkRestantes = max(0, $horasCoworkContratadas - $horasCoworkUsadas);
        $horasSalaRestantes = max(0, $horasSalaContratadas - $horasSalaUsadas);
        $impresionesRestantes = max(0, $impresionesContratadas - $impresionesUsadas);

        // Contadores
        $contadorCowork = $todosRegistros->where('service_type', 'cowork')->count();
        $contadorSala = $todosRegistros->where('service_type', 'meeting_room')->count();

        return view('admin.clientes.detalle-registro', compact(
            'client',
            'subscription',
            'registros',
            'todosRegistros',
            'clientSubscriptions',
            'highlightClientId',
            'horasCoworkUsadas',
            'horasSalaUsadas',
            'horasCoworkContratadas',
            'horasSalaContratadas',
            'horasCoworkRestantes',
            'horasSalaRestantes',
            'impresionesUsadas',
            'impresionesContratadas',
            'impresionesRestantes',
            'contadorCowork',
            'contadorSala'
        ));
    }

    /**
     * Actualizar fechas de inicio y fin del plan desde detalle de registro.
     */
    public function actualizarFechasPlan(Request $request, Client $client, Subscription $subscription)
    {
        if ($subscription->client_id !== $client->id && $subscription->client_id !== $client->invited_by_client_id) {
            abort(404);
        }

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($validated['start_date'])->startOfDay();
        $endDate = $validated['end_date'] ? Carbon::parse($validated['end_date'])->startOfDay() : null;

        $subscription->update([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'next_billing_date' => $endDate,
        ]);

        if ($client->current_subscription_id === $subscription->id) {
            $isActive = $startDate->lte(now()) && (!$endDate || $endDate->gte(now()->startOfDay()));
            $client->update([
                'subscription_status' => $isActive ? 'active' : 'expired',
            ]);
        }

        return redirect()->route('admin.clientes.detalleRegistro', [$client, $subscription])
            ->with('success', 'Fechas del plan actualizadas correctamente.');
    }

    public function exportDetalleRegistroExcel(Client $client, Subscription $subscription)
    {
        if ($subscription->client_id !== $client->id && $subscription->client_id !== $client->invited_by_client_id) {
            abort(404);
        }

        $clientId = null;
        if ($client->invited_by_client_id && $subscription->client_id === $client->invited_by_client_id) {
            $clientId = $client->id;
        }

        return Excel::download(
            new DetalleRegistroExport($subscription, $clientId),
            'registro-' . $subscription->id . '.xlsx'
        );
    }

    public function exportDetalleRegistroPdf(Client $client, Subscription $subscription)
    {
        if ($subscription->client_id !== $client->id && $subscription->client_id !== $client->invited_by_client_id) {
            abort(404);
        }

        $subscription->load('plan');
        $filterClientId = null;
        if ($client->invited_by_client_id && $subscription->client_id === $client->invited_by_client_id) {
            $filterClientId = $client->id;
        }

        $registrosQuery = UsageRecord::with('client')
            ->where('subscription_id', $subscription->id)
            ->orderBy('check_in', 'desc');

        if ($filterClientId) {
            $registrosQuery->where('client_id', $filterClientId);
        }

        $registros = $registrosQuery->get();

        $horasCoworkUsadas = 0;
        foreach ($registros->where('service_type', 'cowork') as $reg) {
            if ($reg->check_out) {
                $horasCoworkUsadas += $reg->check_in->diffInMinutes($reg->check_out) / 60;
            }
        }

        $horasSalaUsadas = 0;
        foreach ($registros->where('service_type', 'meeting_room') as $reg) {
            if ($reg->check_out) {
                $horasSalaUsadas += $reg->check_in->diffInMinutes($reg->check_out) / 60;
            }
        }

        $impresionesUsadas = $registros->where('service_type', 'print')->sum('quantity');

        $horasCoworkContratadas = $subscription->effective_cowork_hours;
        $horasSalaContratadas = $subscription->effective_meeting_room_hours;
        $impresionesContratadas = $subscription->effective_prints_included;

        $porcentajeCowork = $horasCoworkContratadas > 0
            ? min(100, ($horasCoworkUsadas / $horasCoworkContratadas) * 100)
            : 0;
        $porcentajeSala = $horasSalaContratadas > 0
            ? min(100, ($horasSalaUsadas / $horasSalaContratadas) * 100)
            : 0;
        $porcentajeImpresiones = $impresionesContratadas > 0
            ? min(100, ($impresionesUsadas / $impresionesContratadas) * 100)
            : 0;

        $pdf = Pdf::loadView('admin.clientes.detalle-registro-pdf', [
            'client' => $client,
            'subscription' => $subscription,
            'registros' => $registros,
            'horasCoworkUsadas' => $horasCoworkUsadas,
            'horasSalaUsadas' => $horasSalaUsadas,
            'impresionesUsadas' => $impresionesUsadas,
            'horasCoworkContratadas' => $horasCoworkContratadas,
            'horasSalaContratadas' => $horasSalaContratadas,
            'impresionesContratadas' => $impresionesContratadas,
            'porcentajeCowork' => $porcentajeCowork,
            'porcentajeSala' => $porcentajeSala,
            'porcentajeImpresiones' => $porcentajeImpresiones,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('registro-' . $subscription->id . '.pdf');
    }

    /**
     * Eliminar un registro de uso
     */
    public function eliminarRegistro(UsageRecord $usageRecord)
    {
        $usageRecord->delete();

        return redirect()->back()->with('success', 'Registro eliminado exitosamente.');
    }

    /**
     * Actualizar un registro de uso
     */
    public function actualizarRegistro(Request $request, UsageRecord $usageRecord)
    {
        $validated = $request->validate([
            'check_in_date' => 'required|date',
            'check_in_time' => 'required|date_format:H:i',
            'check_out_date' => 'nullable|date',
            'check_out_time' => 'nullable|date_format:H:i',
            'service_type' => 'required|in:cowork,meeting_room',
            'plan_option' => 'required|in:keep,link,remove,move',
            'new_subscription_id' => 'nullable|exists:subscriptions,id',
            'move_subscription_id' => 'nullable|exists:subscriptions,id',
        ]);

        DB::beginTransaction();
        try {
            // Guardar la duración anterior para recalcular horas tracking
            $oldCheckIn = $usageRecord->check_in;
            $oldCheckOut = $usageRecord->check_out;
            $oldServiceType = $usageRecord->service_type;
            $oldSubscriptionId = $usageRecord->subscription_id;

            // Calcular las nuevas fechas combinando fecha y horas
            $checkInDateTime = Carbon::parse($validated['check_in_date'] . ' ' . $validated['check_in_time']);

            $checkOutDateTime = null;
            if ($validated['check_out_date'] && $validated['check_out_time']) {
                $checkOutDateTime = Carbon::parse($validated['check_out_date'] . ' ' . $validated['check_out_time']);

                // Validar que checkout sea después de checkin
                if ($checkOutDateTime->lte($checkInDateTime)) {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'La fecha y hora de salida debe ser posterior a la fecha y hora de entrada.');
                }
            }

            // Actualizar subscription_id según la opción seleccionada
            $newSubscriptionId = $oldSubscriptionId;

            if ($validated['plan_option'] === 'remove') {
                $newSubscriptionId = null;
            } elseif ($validated['plan_option'] === 'link') {
                if (!$validated['new_subscription_id']) {
                    return redirect()->back()->with('error', 'Debe seleccionar un cliente con plan para vincular.');
                }
                $newSubscriptionId = $validated['new_subscription_id'];
            } elseif ($validated['plan_option'] === 'move') {
                if (!$validated['move_subscription_id']) {
                    return redirect()->back()->with('error', 'Debe seleccionar un plan del cliente.');
                }

                $targetSubscription = Subscription::where('id', $validated['move_subscription_id'])
                    ->where('client_id', $usageRecord->client_id)
                    ->first();

                if (!$targetSubscription) {
                    return redirect()->back()->with('error', 'El plan seleccionado no pertenece a este cliente.');
                }

                $checkDate = $checkInDateTime->toDateString();
                $validWindow = $targetSubscription->start_date->toDateString() <= $checkDate
                    && (!$targetSubscription->end_date || $targetSubscription->end_date->toDateString() >= $checkDate);

                if (!$validWindow) {
                    return redirect()->back()->with('error', 'El plan seleccionado no esta vigente para la fecha de entrada.');
                }

                $newSubscriptionId = $targetSubscription->id;
            }
            // Si es 'keep', mantiene el valor actual ($oldSubscriptionId)

            // Actualizar el registro
            $usageRecord->update([
                'check_in' => $checkInDateTime,
                'check_out' => $checkOutDateTime,
                'service_type' => $validated['service_type'],
                'subscription_id' => $newSubscriptionId,
                'status' => $checkOutDateTime ? 'completed' : 'in_progress',
            ]);

            // Recalcular horas en hours_tracking
            // Primero, restar las horas antiguas si había un plan vinculado
            if ($oldSubscriptionId && $oldCheckOut && in_array($oldServiceType, ['cowork', 'meeting_room'])) {
                $oldDuration = $oldCheckIn->diffInMinutes($oldCheckOut) / 60;

                $oldTracking = HoursTracking::where('subscription_id', $oldSubscriptionId)
                    ->where('service_type', $oldServiceType)
                    ->first();

                if ($oldTracking) {
                    $oldTracking->decrement('hours_used', $oldDuration);
                    $oldTracking->update(['last_updated' => now()]);
                }
            }

            // Luego, sumar las nuevas horas si hay un plan vinculado
            if ($newSubscriptionId && $checkOutDateTime && in_array($validated['service_type'], ['cowork', 'meeting_room'])) {
                $newDuration = $checkInDateTime->diffInMinutes($checkOutDateTime) / 60;

                $newTracking = HoursTracking::firstOrCreate(
                    [
                        'subscription_id' => $newSubscriptionId,
                        'service_type' => $validated['service_type'],
                    ],
                    [
                        'client_id' => $usageRecord->client_id,
                        'hours_used' => 0,
                        'total_hours_available' => 0, // Se actualizará según el plan
                    ]
                );

                $newTracking->increment('hours_used', $newDuration);
                $newTracking->update(['last_updated' => now()]);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Registro actualizado exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al actualizar: ' . $e->getMessage());
        }
    }
}
