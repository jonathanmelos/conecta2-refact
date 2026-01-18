<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Detalle de Registro</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #222; }
        h1 { font-size: 18px; margin: 0 0 6px 0; }
        .subtle { color: #666; margin: 0 0 12px 0; }
        .header { width: 100%; margin-bottom: 10px; border: none; }
        .header td { vertical-align: middle; border: none; }
        .header-info { font-size: 10px; color: #444; line-height: 1.4; }
        .logo { height: 40px; }
        .summary { margin: 8px 0 12px 0; }
        .summary-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .summary-table td { border: 1px solid #e6e6e6; padding: 8px; vertical-align: top; }
        .card-title { font-weight: bold; margin-bottom: 6px; }
        .pill { display: inline-block; padding: 2px 6px; border-radius: 10px; font-size: 10px; }
        .pill-blue { background: #e3f2fd; color: #0d47a1; }
        .pill-green { background: #e8f5e9; color: #1b5e20; }
        .pill-amber { background: #fff8e1; color: #8d6e00; }
        .bar { height: 8px; background: #eee; border-radius: 6px; overflow: hidden; margin-top: 6px; }
        .bar-fill { height: 8px; }
        .bar-blue { background: #1e88e5; }
        .bar-green { background: #43a047; }
        .bar-amber { background: #f9a825; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 6px 6px; border: 1px solid #ddd; }
        th { background: #f2f2f2; text-align: left; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td style="width: 40%;">
                <img src="{{ public_path('images/logo.jpg') }}" alt="Conecta" class="logo">
            </td>
            <td style="width: 60%; text-align: right;">
                <div class="header-info">
                    <div>www.conectacowork.com</div>
                    <div>Edificio la Previsora, Av. Naciones Unidas, y 1084, Quito 170507</div>
                    <div>0968097085</div>
                </div>
            </td>
        </tr>
    </table>
    <h1>Detalle de Registro #{{ $subscription->id }}</h1>
    <p class="subtle">
        {{ $client->full_name }}{{ $client->invited_by_client_id ? ' (Invitado)' : '' }} - {{ $subscription->plan->name ?? 'Plan' }}
        {{ $subscription->plan && $subscription->plan->is_pilot ? ' (Piloto)' : '' }}
        ({{ $subscription->start_date->format('d/m/Y') }} - {{ $subscription->end_date ? $subscription->end_date->format('d/m/Y') : 'Sin vencimiento' }})
    </p>

    @php
        $fmtHoras = function ($horas) {
            $mins = (int) round($horas * 60);
            $h = intdiv($mins, 60);
            $m = $mins % 60;
            return sprintf('%d:%02d', $h, $m);
        };
        $horasCoworkRestantes = max(0, $horasCoworkContratadas - $horasCoworkUsadas);
        $horasSalaRestantes = max(0, $horasSalaContratadas - $horasSalaUsadas);
        $impresionesRestantes = max(0, $impresionesContratadas - $impresionesUsadas);
    @endphp

    <div class="summary">
        <table class="summary-table">
            <tr>
                <td>
                    <div class="card-title">Cowork</div>
                    <span class="pill pill-blue">{{ $fmtHoras($horasCoworkUsadas) }} de {{ $fmtHoras($horasCoworkContratadas) }}</span>
                    <div class="bar">
                        <div class="bar-fill bar-blue" style="width: {{ $porcentajeCowork }}%;"></div>
                    </div>
                    <div class="subtle">Restantes: {{ $fmtHoras($horasCoworkRestantes) }}</div>
                </td>
                <td>
                    <div class="card-title">Sala Reuniones</div>
                    <span class="pill pill-green">{{ $fmtHoras($horasSalaUsadas) }} de {{ $fmtHoras($horasSalaContratadas) }}</span>
                    <div class="bar">
                        <div class="bar-fill bar-green" style="width: {{ $porcentajeSala }}%;"></div>
                    </div>
                    <div class="subtle">Restantes: {{ $fmtHoras($horasSalaRestantes) }}</div>
                </td>
                <td>
                    <div class="card-title">Impresiones</div>
                    <span class="pill pill-amber">{{ $impresionesUsadas }} de {{ $impresionesContratadas }}</span>
                    <div class="bar">
                        <div class="bar-fill bar-amber" style="width: {{ $porcentajeImpresiones }}%;"></div>
                    </div>
                    <div class="subtle">Restantes: {{ $impresionesRestantes }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="summary-table" style="margin-bottom: 14px;">
        <tr>
            <td><strong>Resumen Cowork</strong></td>
            <td>Usadas: {{ $fmtHoras($horasCoworkUsadas) }}</td>
            <td>Contratadas: {{ $fmtHoras($horasCoworkContratadas) }}</td>
            <td>Restantes: {{ $fmtHoras($horasCoworkRestantes) }}</td>
        </tr>
        <tr>
            <td><strong>Resumen Sala</strong></td>
            <td>Usadas: {{ $fmtHoras($horasSalaUsadas) }}</td>
            <td>Contratadas: {{ $fmtHoras($horasSalaContratadas) }}</td>
            <td>Restantes: {{ $fmtHoras($horasSalaRestantes) }}</td>
        </tr>
        <tr>
            <td><strong>Resumen Impresiones</strong></td>
            <td>Usadas: {{ $impresionesUsadas }}</td>
            <td>Contratadas: {{ $impresionesContratadas }}</td>
            <td>Restantes: {{ $impresionesRestantes }}</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Fecha</th>
                <th>Servicio</th>
                <th>Entrada</th>
                <th>Salida</th>
                <th>Duracion</th>
                <th>Impresiones</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($registros as $registro)
                <tr>
                    <td>
                        @if($registro->client)
                            {{ $registro->client->full_name }}
                            @if($registro->client_id != $client->id)
                                (Invitado)
                            @endif
                        @else
                            {{ $client->full_name }}
                        @endif
                    </td>
                    <td>{{ $registro->check_in->format('d/m/Y') }}</td>
                    <td>
                        @switch($registro->service_type)
                            @case('cowork')
                                Cowork
                                @break
                            @case('meeting_room')
                                Sala Reuniones
                                @break
                            @case('print')
                                Impresion
                                @break
                            @default
                                {{ $registro->service_type }}
                        @endswitch
                    </td>
                    <td>
                        @if($registro->service_type !== 'print')
                            {{ $registro->check_in->format('H:i') }}
                        @endif
                    </td>
                    <td>
                        @if($registro->service_type !== 'print' && $registro->check_out)
                            {{ $registro->check_out->format('H:i') }}
                        @endif
                    </td>
                    <td>
                        @if($registro->check_out && in_array($registro->service_type, ['cowork', 'meeting_room']))
                            @php
                                $duracion = $registro->check_in->diffInMinutes($registro->check_out);
                                $horas = floor($duracion / 60);
                                $minutos = $duracion % 60;
                            @endphp
                            {{ sprintf('%02d:%02d', $horas, $minutos) }}
                        @elseif($registro->status === 'in_progress' && $registro->service_type !== 'print')
                            En curso
                        @endif
                    </td>
                    <td class="text-right">
                        {{ $registro->quantity && $registro->quantity > 0 ? $registro->quantity : 0 }}
                    </td>
                    <td>{{ $registro->status === 'completed' ? 'Completado' : ($registro->status === 'in_progress' ? 'En curso' : $registro->status) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">No hay registros.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
