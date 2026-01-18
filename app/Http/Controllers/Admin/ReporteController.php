<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Subscription;
use App\Models\UsageRecord;
use App\Models\Plan;
use Illuminate\Http\Request;

class ReporteController extends Controller
{
    public function index()
    {
        return view('admin.reportes.index');
    }

    public function porFecha(Request $request)
    {
        $fecha = $request->get('fecha', now()->format('Y-m-d'));
        $estado = $request->get('estado', '%');

        if ($estado === 'E') {
            $registros = collect();
        } else {
            $registros = UsageRecord::with(['client.invitedBy'])
                ->where(function($query) use ($fecha) {
                    $query->whereDate('check_in', $fecha)
                        ->orWhereDate('check_out', $fecha);
                })
                ->when($estado === 'A', function($query) {
                    $query->where('status', 'completed');
                })
                ->when($estado === 'C', function($query) {
                    $query->where('status', 'in_progress');
                })
                ->orderBy('check_in')
                ->get();
        }

        $totalCoworkMin = UsageRecord::where('service_type', 'cowork')
            ->where('status', 'completed')
            ->whereDate('check_out', $fecha)
            ->get()
            ->sum(function($record) {
                return $record->check_out
                    ? $record->check_in->diffInMinutes($record->check_out)
                    : 0;
            });

        $totalSalaMin = UsageRecord::where('service_type', 'meeting_room')
            ->where('status', 'completed')
            ->whereDate('check_out', $fecha)
            ->get()
            ->sum(function($record) {
                return $record->check_out
                    ? $record->check_in->diffInMinutes($record->check_out)
                    : 0;
            });

        return view('admin.reportes.fecha', [
            'fecha' => $fecha,
            'estado' => $estado,
            'registros' => $registros,
            'totalCoworkMin' => $totalCoworkMin,
            'totalSalaMin' => $totalSalaMin,
        ]);
    }

    public function porCliente(Request $request)
    {
        $documento = $request->get('documento');
        $client = null;
        $subscriptions = collect();

        if ($documento) {
            $client = Client::where('document_number', $documento)->first();
            if ($client) {
                $subscriptions = $client->subscriptions()
                    ->with('plan')
                    ->orderBy('start_date')
                    ->get();
            }
        }

        return view('admin.reportes.cliente', [
            'documento' => $documento,
            'client' => $client,
            'subscriptions' => $subscriptions,
        ]);
    }

    public function porPeriodo(Request $request)
    {
        $fechaInicio = $request->get('fecha_i', now()->startOfMonth()->format('Y-m-d'));
        $fechaFin = $request->get('fecha', now()->format('Y-m-d'));
        $busqueda = $request->get('busqueda', 'reg');

        $registros = collect();
        $planes = collect();

        if ($busqueda === 'reg') {
            $registros = UsageRecord::with(['client.invitedBy'])
                ->whereDate('check_in', '>=', $fechaInicio)
                ->whereDate('check_in', '<=', $fechaFin)
                ->orderBy('check_in')
                ->get();
        }

        if ($busqueda === 'plan') {
            $planes = Subscription::with(['client', 'plan'])
                ->whereDate('start_date', '>=', $fechaInicio)
                ->whereDate('start_date', '<=', $fechaFin)
                ->orderBy('start_date')
                ->get();
        }

        return view('admin.reportes.periodo', [
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin,
            'busqueda' => $busqueda,
            'registros' => $registros,
            'planes' => $planes,
        ]);
    }

    public function calculadora(Request $request)
    {
        $plans = Plan::active()->orderBy('price')->get();

        $inputs = [
            'personas' => max(1, (int) $request->get('personas', 1)),
            'horas' => max(0, (int) $request->get('horas', 0)),
            'visita' => max(0, (int) $request->get('visita', 0)),
        ];

        // Calcular horas estimadas al mes: personas * horas * (visitas_semana * 4)
        $diasMes = $inputs['visita'] * 4;
        $horasEstimadas = $inputs['personas'] * $inputs['horas'] * $diasMes;

        $suggestedPlan = null;
        $otherPlans = collect();

        if ($horasEstimadas > 0) {
            // Buscar el plan más cercano por horas de cowork (como el PHP original)
            $suggestedPlan = $plans->sortBy(function ($plan) use ($horasEstimadas) {
                return abs($plan->cowork_hours - $horasEstimadas);
            })->first();

            // Otros planes ordenados para upselling:
            // 1. Primero planes con MÁS horas (upgrades), ordenados por cercanía
            // 2. Luego planes con MENOS horas, ordenados por cercanía
            $otherPlans = $plans->filter(function ($plan) use ($suggestedPlan) {
                return $suggestedPlan && $plan->id !== $suggestedPlan->id;
            })->sortBy(function ($plan) use ($horasEstimadas) {
                $diferencia = $plan->cowork_hours - $horasEstimadas;
                if ($diferencia >= 0) {
                    // Planes con más horas: orden 0 + cercanía (aparecen primero)
                    return $diferencia;
                } else {
                    // Planes con menos horas: orden 10000 + cercanía (aparecen después)
                    return 10000 + abs($diferencia);
                }
            })->values();
        } else {
            $otherPlans = $plans;
        }

        $view = $request->routeIs('regular.calculadora')
            ? 'regular.calculadora'
            : 'admin.calculadora';

        return view($view, [
            'plans' => $plans,
            'inputs' => $inputs,
            'horasEstimadas' => $horasEstimadas,
            'suggestedPlan' => $suggestedPlan,
            'otherPlans' => $otherPlans,
        ]);
    }
}
