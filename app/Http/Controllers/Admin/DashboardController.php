<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UsageRecord;
use App\Models\Client;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
public function diario(Request $request)
{
    // Obtener fecha del query string o usar hoy
    $fecha = $request->get('fecha', date('Y-m-d'));
    
    // Validar formato de fecha
    try {
        $fechaCarbon = Carbon::parse($fecha);
    } catch (\Exception $e) {
        $fechaCarbon = Carbon::today();
        $fecha = $fechaCarbon->format('Y-m-d');
    }

    // Registros del día - Cowork
    $registrosCowork = UsageRecord::with(['client', 'area', 'subscription.plan'])
        ->whereDate('check_in', $fecha)
        ->where('service_type', 'cowork')
        ->orderBy('check_in', 'desc')
        ->get();

    // Registros del día - Sala de reuniones
    $registrosSala = UsageRecord::with(['client', 'area', 'subscription.plan'])
        ->whereDate('check_in', $fecha)
        ->where('service_type', 'meeting_room')
        ->orderBy('check_in', 'desc')
        ->get();

    // Registros pendientes de aprobación (todos, no solo del día)
    $registrosPendientes = UsageRecord::with(['client'])
        ->where('status', 'completed')
        ->where('invoiced', false)
        ->where('is_billable', true)
        ->whereNotNull('check_out')
        ->orderBy('check_in', 'desc')
        ->limit(10)
        ->get();

    // Registros no concluidos (en progreso - todos)
    $registrosNoConcluidos = UsageRecord::with(['client', 'area'])
        ->where('status', 'in_progress')
        ->whereNull('check_out')
        ->orderBy('check_in', 'desc')
        ->limit(10)
        ->get();

    // Estadísticas del día
    $stats = [
        'total_cowork' => $registrosCowork->count(),
        'total_sala' => $registrosSala->count(),
        'cowork_activos' => $registrosCowork->where('status', 'in_progress')->count(),
        'sala_activos' => $registrosSala->where('status', 'in_progress')->count(),
        'horas_cowork' => $registrosCowork->sum('duration_in_hours'),
        'horas_sala' => $registrosSala->sum('duration_in_hours'),
    ];

    return view('admin.diario', compact(
        'fecha',
        'fechaCarbon',
        'registrosCowork',
        'registrosSala',
        'registrosPendientes',
        'registrosNoConcluidos',
        'stats'
    ));
}
}