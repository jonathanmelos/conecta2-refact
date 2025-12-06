<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\UsageRecord;
use App\Models\HoursTracking;
use Illuminate\Http\Request;
use Carbon\Carbon;

class RegistroController extends Controller
{
    public function index(Request $request)
{
    $doc = $request->get('doc', '0');
    $selectedClient = null;
    $hoursTracking = null;
    
    // Si hay un documento seleccionado, buscar el cliente
    if ($doc !== '0' && $doc !== '') {
        $selectedClient = Client::with(['currentSubscription.plan', 'hoursTracking', 'invitedBy', 'guests'])
            ->where('document_number', $doc)
            ->first();
        
        if ($selectedClient && $selectedClient->currentSubscription) {
            $hoursTracking = $selectedClient->hoursTracking()
                ->where('subscription_id', $selectedClient->current_subscription_id)
                ->get()
                ->keyBy('service_type');
        }
    }
    
    // ✅ Registros del día de hoy CON ESTADO ACTUAL DEL CLIENTE
    $hoy = date('Y-m-d');
    $registrosHoy = UsageRecord::with([
        'client.invitedBy.currentSubscription.plan',  // ✅ Agregar estado actual
        'client.currentSubscription.plan',            // ✅ Agregar estado actual
        'subscription.plan'                           // Mantener para histórico
    ])
        ->whereDate('check_in', $hoy)
        ->orderBy('check_in', 'desc')
        ->get();
    
    return view('admin.registro.index', compact(
        'selectedClient',
        'hoursTracking',
        'registrosHoy'
    ));
}
    
public function buscar(Request $request)
{
    $search = $request->get('search', '');
    
    $clients = Client::with(['currentSubscription.plan', 'hoursTracking'])
        ->where('client_status', 'active')
        ->where(function($query) use ($search) {
            $query->where('document_number', 'LIKE', "%{$search}%")
                  ->orWhere('first_name', 'LIKE', "%{$search}%")
                  ->orWhere('last_name', 'LIKE', "%{$search}%");
        })
        ->limit(10)
        ->get();
    
    // ✅ Transformar datos para que sean seguros en JSON
    $clientsData = $clients->map(function($client) {
        // Verificar servicios activos HOY solo del cliente mismo
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
        
        // Preparar datos de suscripción
        $subscriptionData = null;
        if ($client->currentSubscription) {
            $subscriptionData = [
                'id' => $client->currentSubscription->id,
                'plan_id' => $client->currentSubscription->plan_id,
                'start_date' => $client->currentSubscription->start_date->format('Y-m-d'),
                'end_date' => $client->currentSubscription->end_date->format('Y-m-d'),
                'days_remaining' => max(0, now()->diffInDays($client->currentSubscription->end_date, false)),
                'plan' => [
                    'id' => $client->currentSubscription->plan->id,
                    'name' => $client->currentSubscription->plan->name,
                    'cowork_hours' => $client->currentSubscription->plan->cowork_hours,
                    'meeting_room_hours' => $client->currentSubscription->plan->meeting_room_hours,
                ]
            ];
        }
        
        // Obtener horas usadas
        $coworkTracking = HoursTracking::where('client_id', $client->id)
            ->where('subscription_id', $client->current_subscription_id)
            ->where('service_type', 'cowork')
            ->first();
        
        $salaTracking = HoursTracking::where('client_id', $client->id)
            ->where('subscription_id', $client->current_subscription_id)
            ->where('service_type', 'meeting_room')
            ->first();
        
        return [
            'id' => $client->id,
            'document_number' => $client->document_number,
            'first_name' => $client->first_name,
            'last_name' => $client->last_name,
            'email' => $client->email,
            'phone' => $client->phone,
            'active_cowork_today' => $activeCowork,
            'active_sala_today' => $activeSala,
            'current_subscription' => $subscriptionData,
            'subscription_dates' => $subscriptionData ? 
                $client->currentSubscription->start_date->format('d/m/Y') . ' - ' . $client->currentSubscription->end_date->format('d/m/Y') 
                : null,
            'cowork_hours_used' => $coworkTracking ? (float)$coworkTracking->hours_used : 0,
            'sala_hours_used' => $salaTracking ? (float)$salaTracking->hours_used : 0,
        ];
    });
    
    return response()->json($clientsData);
}  
   public function cowork(Request $request)
{
    $client = Client::with(['currentSubscription.plan', 'invitedBy.currentSubscription.plan'])
        ->findOrFail($request->client_id);
    
    // ✅ VERIFICAR QUE ESTE CLIENTE NO ESTÉ USANDO OTRO SERVICIO HOY
    // IMPORTANTE: Solo verificar sus propios registros, NO los de otros
    $existingService = UsageRecord::where('client_id', $client->id) // ✅ Solo este cliente
        ->where('status', 'in_progress')
        ->whereDate('check_in', today())
        ->first();
    
    if ($existingService) {
        $serviceName = $existingService->service_type === 'cowork' ? 'cowork' : 'sala de reuniones';
        return redirect()->route('admin.registro.index')
            ->with('error', $client->full_name . ' ya está usando ' . $serviceName . '. Debe finalizar ese servicio primero.');
    }
    
    // ✅ SI SE PROPORCIONA MASTER_ID, VINCULAR PRIMERO
    if ($request->master_id) {
        $master = Client::with('currentSubscription.plan')->findOrFail($request->master_id);
        
        if (!$master->currentSubscription || !$master->currentSubscription->plan) {
            return redirect()->route('admin.registro.index')
                ->with('error', 'El cliente master debe tener un plan activo.');
        }
        
        // Vincular el cliente como invitado
        $client->update([
            'invited_by_client_id' => $master->id,
            'current_subscription_id' => $master->current_subscription_id,
            'subscription_status' => 'active',
        ]);
        
        // Recargar las relaciones
        $client->refresh();
        $client->load(['currentSubscription.plan', 'invitedBy.currentSubscription.plan']);
    }
    
    // ✅ DETERMINAR LA SUSCRIPCIÓN Y VALIDAR SEGÚN TIPO DE CLIENTE
    $subscriptionId = null;
    $isBillable = true;
    $planToCheck = null;
    
    if ($client->invitedBy && $client->invitedBy->currentSubscription) {
        // ES INVITADO → usar plan del master
        $subscriptionId = $client->invitedBy->current_subscription_id;
        $planToCheck = $client->invitedBy->currentSubscription->plan;
        $isBillable = false;
    } elseif ($client->currentSubscription) {
        // TIENE PLAN PROPIO
        $subscriptionId = $client->current_subscription_id;
        $planToCheck = $client->currentSubscription->plan;
        $isBillable = false;
    } else {
        // CLIENTE OCASIONAL (sin plan)
        $subscriptionId = null;
        $isBillable = true;
    }
    
    // ✅ SI TIENE PLAN, VALIDAR QUE INCLUYA HORAS DE COWORK
    if ($planToCheck) {
        if (!$planToCheck->cowork_hours || $planToCheck->cowork_hours <= 0) {
            return redirect()->route('admin.registro.index')
                ->with('error', 'El plan ' . $planToCheck->name . ' no incluye horas de cowork.');
        }
    }
    
    // Crear registro de entrada a cowork
    UsageRecord::create([
        'client_id' => $client->id,
        'subscription_id' => $subscriptionId,
        'area_id' => 1,
        'service_type' => 'cowork',
        'check_in' => now(),
        'status' => 'in_progress',
        'registration_method' => 'manual',
        'is_billable' => $isBillable,
        'quantity' => 1,
    ]);
    
    $message = '✓ ' . $client->full_name . ' ingresó a cowork';
    
    if ($isBillable) {
        $message .= ' (Cliente ocasional - se facturará por separado)';
    } elseif ($request->master_id) {
        $master = Client::find($request->master_id);
        $message .= ' (Vinculado como invitado de ' . $master->full_name . ')';
    }
    
    return redirect()->route('admin.registro.index')
        ->with('success', $message);
}

public function sala(Request $request)
{
    $client = Client::with(['currentSubscription.plan', 'invitedBy.currentSubscription.plan'])
        ->findOrFail($request->client_id);
    
    // ✅ VERIFICAR QUE ESTE CLIENTE NO ESTÉ USANDO OTRO SERVICIO HOY
    // IMPORTANTE: Solo verificar sus propios registros, NO los de otros
    $existingService = UsageRecord::where('client_id', $client->id) // ✅ Solo este cliente
        ->where('status', 'in_progress')
        ->whereDate('check_in', today())
        ->first();
    
    if ($existingService) {
        $serviceName = $existingService->service_type === 'cowork' ? 'cowork' : 'sala de reuniones';
        return redirect()->route('admin.registro.index')
            ->with('error', $client->full_name . ' ya está usando ' . $serviceName . '. Debe finalizar ese servicio primero.');
    }
    
    // ✅ SI SE PROPORCIONA MASTER_ID, VINCULAR PRIMERO
    if ($request->master_id) {
        $master = Client::with('currentSubscription.plan')->findOrFail($request->master_id);
        
        if (!$master->currentSubscription || !$master->currentSubscription->plan) {
            return redirect()->route('admin.registro.index')
                ->with('error', 'El cliente master debe tener un plan activo.');
        }
        
        // Vincular el cliente como invitado
        $client->update([
            'invited_by_client_id' => $master->id,
            'current_subscription_id' => $master->current_subscription_id,
            'subscription_status' => 'active',
        ]);
        
        // Recargar las relaciones
        $client->refresh();
        $client->load(['currentSubscription.plan', 'invitedBy.currentSubscription.plan']);
    }
    
    // ✅ DETERMINAR LA SUSCRIPCIÓN Y VALIDAR SEGÚN TIPO DE CLIENTE
    $subscriptionId = null;
    $isBillable = true;
    $planToCheck = null;
    
    if ($client->invitedBy && $client->invitedBy->currentSubscription) {
        // ES INVITADO → usar plan del master
        $subscriptionId = $client->invitedBy->current_subscription_id;
        $planToCheck = $client->invitedBy->currentSubscription->plan;
        $isBillable = false;
    } elseif ($client->currentSubscription) {
        // TIENE PLAN PROPIO
        $subscriptionId = $client->current_subscription_id;
        $planToCheck = $client->currentSubscription->plan;
        $isBillable = false;
    } else {
        // CLIENTE OCASIONAL (sin plan)
        $subscriptionId = null;
        $isBillable = true;
    }
    
    // ✅ SI TIENE PLAN, VALIDAR QUE INCLUYA HORAS DE SALA
    if ($planToCheck) {
        if (!$planToCheck->meeting_room_hours || $planToCheck->meeting_room_hours <= 0) {
            return redirect()->route('admin.registro.index')
                ->with('error', 'El plan ' . $planToCheck->name . ' no incluye horas de sala de reuniones.');
        }
    }
    
    // Crear registro de entrada a sala
    UsageRecord::create([
        'client_id' => $client->id,
        'subscription_id' => $subscriptionId,
        'area_id' => 3,
        'service_type' => 'meeting_room',
        'check_in' => now(),
        'status' => 'in_progress',
        'registration_method' => 'manual',
        'is_billable' => $isBillable,
        'quantity' => 1,
    ]);
    
    $message = '✓ ' . $client->full_name . ' ingresó a sala de reuniones';
    
    if ($isBillable) {
        $message .= ' (Cliente ocasional - se facturará por separado)';
    } elseif ($request->master_id) {
        $master = Client::find($request->master_id);
        $message .= ' (Vinculado como invitado de ' . $master->full_name . ')';
    }
    
    return redirect()->route('admin.registro.index')
        ->with('success', $message);
}
    public function finalizar($id)
    {
        $registro = UsageRecord::findOrFail($id);
        
        // Verificar que esté en progreso
        if ($registro->status !== 'in_progress') {
            return redirect()->route('admin.registro.index')
                ->with('warning', 'Este registro ya fue finalizado.');
        }
        
        // Registrar salida
        $registro->update([
            'check_out' => now(),
            'status' => 'completed',
        ]);
        
        // Refrescar el modelo para obtener duration_in_hours calculado
        $registro->refresh();
        
        // Calcular duración
        $duracion = $registro->duration_in_hours ?? 0;
        
        // Actualizar horas consumidas en hours_tracking
        $this->actualizarHoras($registro);
        
        return redirect()->route('admin.registro.index')
            ->with('success', '✓ Registro finalizado. Duración: ' . number_format($duracion, 2) . ' horas');
    }
    
    public function impresion(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'cantidad' => 'required|integer|min:1',
        ]);
        
        $client = Client::findOrFail($request->client_id);
        
        // Crear registro de impresiones
        UsageRecord::create([
            'client_id' => $client->id,
            'subscription_id' => $client->current_subscription_id,
            'service_type' => 'print',
            'check_in' => now(),
            'check_out' => now(),
            'quantity' => $request->cantidad,
            'status' => 'completed',
            'registration_method' => 'manual',
            'is_billable' => false,
        ]);
        
        return redirect()->route('admin.registro.index')
            ->with('success', 'Se registraron ' . $request->cantidad . ' impresiones para ' . $client->full_name);
    }
    
    private function actualizarHoras(UsageRecord $registro)
{
    // ✅ SOLO ACTUALIZAR HOURS_TRACKING SI TIENE SUBSCRIPTION_ID
    if (!$registro->duration_in_hours || !$registro->subscription_id) {
        return; // Es cliente ocasional o no tiene horas que descontar
    }
    
    // ✅ IMPORTANTE: Para invitados, usar el client_id del MASTER, no del invitado
    $client = Client::with('invitedBy')->find($registro->client_id);
    
    // Determinar el client_id correcto para hours_tracking
    if ($client && $client->invitedBy) {
        // Es un invitado → usar el client_id del master
        $trackingClientId = $client->invited_by_client_id;
    } else {
        // Es el cliente con su propio plan
        $trackingClientId = $registro->client_id;
    }
    
    // Buscar o crear registro de horas
    $tracking = HoursTracking::firstOrCreate(
        [
            'client_id' => $trackingClientId, // ✅ Client ID correcto
            'subscription_id' => $registro->subscription_id,
            'service_type' => $registro->service_type,
        ],
        [
            'hours_used' => 0,
            'total_hours_available' => $this->getTotalHorasDisponibles($registro),
        ]
    );
    
    // Incrementar horas usadas
    $tracking->increment('hours_used', $registro->duration_in_hours);
    $tracking->update(['last_updated' => now()]);
}
    private function getTotalHorasDisponibles($registro)
    {
        if (!$registro->subscription || !$registro->subscription->plan) {
            return 0;
        }
        
        $plan = $registro->subscription->plan;
        
        if ($registro->service_type === 'cowork') {
            return $plan->cowork_hours ?? 0;
        } elseif ($registro->service_type === 'meeting_room') {
            return $plan->meeting_room_hours ?? 0;
        }
        
        return 0;
    }
}