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
        $registrosCowork = UsageRecord::with(['client.currentSubscription.plan', 'area', 'subscription.plan'])
            ->whereDate('check_in', $fecha)
            ->where('service_type', 'cowork')
            ->orderBy('check_in', 'desc')
            ->get();

        // Registros del día - Sala de reuniones
        $registrosSala = UsageRecord::with(['client.currentSubscription.plan', 'area', 'subscription.plan'])
            ->whereDate('check_in', $fecha)
            ->where('service_type', 'meeting_room')
            ->orderBy('check_in', 'desc')
            ->get();

        // ✅ NUEVO: Registros de impresiones del día
        $registrosImpresiones = UsageRecord::with(['client.currentSubscription.plan', 'subscription.plan'])
            ->whereDate('check_in', $fecha)
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->where('service_type', 'print')
                      ->where('quantity', '>', 0);
                })->orWhere(function ($q) {
                    $q->whereIn('service_type', ['cowork', 'meeting_room'])
                      ->where('quantity', '>', 0)
                      ->where(function ($legacy) {
                          $legacy->whereNull('registration_method')
                                 ->orWhere('registration_method', '');
                      });
                });
            })
            ->orderBy('check_in', 'asc')
            ->get()
            ->map(function($registro) {
                $serviceLabel = 'Ocasional';
                if ($registro->service_type === 'cowork') {
                    $serviceLabel = 'Cowork';
                } elseif ($registro->service_type === 'meeting_room') {
                    $serviceLabel = 'Sala de Reuniones';
                } elseif ($registro->service_type === 'print') {
                    $serviceLabel = 'Impresion';
                }

                return [
                    'client' => $registro->client,
                    'service_type' => $serviceLabel,
                    'time' => $registro->check_in,
                    'prints' => $registro->quantity,
                ];
            });

        // Estadísticas del día
        $stats = [
            'total_cowork' => $registrosCowork->count(),
            'total_sala' => $registrosSala->count(),
            'cowork_activos' => $registrosCowork->where('status', 'in_progress')->count(),
            'sala_activos' => $registrosSala->where('status', 'in_progress')->count(),
            'horas_cowork' => $registrosCowork->sum('duration_in_hours'),
            'horas_sala' => $registrosSala->sum('duration_in_hours'),
            'total_impresiones' => $registrosImpresiones->sum('prints'),
        ];

        return view('admin.diario', compact(
            'fecha',
            'fechaCarbon',
            'registrosCowork',
            'registrosSala',
            'registrosImpresiones',
            'stats'
        ));
    }
}
