<?php

namespace App\Exports;

use App\Models\Subscription;
use App\Models\UsageRecord;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DetalleRegistroExport implements FromArray, WithHeadings
{
    private Subscription $subscription;

    public function __construct(Subscription $subscription)
    {
        $this->subscription = $subscription->load('client');
    }

    public function headings(): array
    {
        return [
            'Cliente',
            'Fecha',
            'Servicio',
            'Entrada',
            'Salida',
            'Duracion',
            'Impresiones',
            'Estado',
        ];
    }

    public function array(): array
    {
        $registros = UsageRecord::with('client')
            ->where('subscription_id', $this->subscription->id)
            ->orderBy('check_in', 'desc')
            ->get();

        $rows = [];
        foreach ($registros as $registro) {
            $rows[] = [
                $this->formatCliente($registro),
                $registro->check_in ? $registro->check_in->format('d/m/Y') : '',
                $this->formatServiceType($registro->service_type),
                $registro->service_type !== 'print' && $registro->check_in
                    ? $registro->check_in->format('H:i')
                    : '',
                $registro->service_type !== 'print' && $registro->check_out
                    ? $registro->check_out->format('H:i')
                    : '',
                $this->formatDuracion($registro),
                $registro->quantity && $registro->quantity > 0 ? $registro->quantity : 0,
                $this->formatStatus($registro->status),
            ];
        }

        $horasCoworkUsadas = $this->sumHoras($registros, 'cowork');
        $horasSalaUsadas = $this->sumHoras($registros, 'meeting_room');
        $impresionesUsadas = $registros->sum('quantity');

        $horasCoworkContratadas = $this->subscription->plan->cowork_hours ?? 0;
        $horasSalaContratadas = $this->subscription->plan->meeting_room_hours ?? 0;
        $impresionesContratadas = $this->subscription->plan->prints_included ?? 0;

        $rows[] = [];
        $rows[] = ['Resumen', '', '', '', '', '', '', ''];
        $rows[] = ['Horas Cowork usadas', $this->formatHoras($horasCoworkUsadas), '', '', '', '', '', ''];
        $rows[] = ['Horas Cowork contratadas', $this->formatHoras($horasCoworkContratadas), '', '', '', '', '', ''];
        $rows[] = ['Horas Cowork restantes', $this->formatHoras(max(0, $horasCoworkContratadas - $horasCoworkUsadas)), '', '', '', '', '', ''];
        $rows[] = ['Horas Sala usadas', $this->formatHoras($horasSalaUsadas), '', '', '', '', '', ''];
        $rows[] = ['Horas Sala contratadas', $this->formatHoras($horasSalaContratadas), '', '', '', '', '', ''];
        $rows[] = ['Horas Sala restantes', $this->formatHoras(max(0, $horasSalaContratadas - $horasSalaUsadas)), '', '', '', '', '', ''];
        $rows[] = ['Impresiones usadas', $impresionesUsadas, '', '', '', '', '', ''];
        $rows[] = ['Impresiones contratadas', $impresionesContratadas, '', '', '', '', '', ''];
        $rows[] = ['Impresiones restantes', max(0, $impresionesContratadas - $impresionesUsadas), '', '', '', '', '', ''];

        return $rows;
    }

    private function formatCliente(UsageRecord $registro): string
    {
        $nombre = $registro->client ? $registro->client->full_name : '';
        if ($this->subscription->client_id && $registro->client_id && $registro->client_id !== $this->subscription->client_id) {
            return trim($nombre) . ' (Invitado)';
        }

        return $nombre ?: 'Cliente';
    }

    private function formatServiceType(?string $type): string
    {
        return match ($type) {
            'cowork' => 'Cowork',
            'meeting_room' => 'Sala Reuniones',
            'print' => 'Impresion',
            default => $type ?? '',
        };
    }

    private function formatDuracion(UsageRecord $registro): string
    {
        if ($registro->check_out && in_array($registro->service_type, ['cowork', 'meeting_room'], true)) {
            $duracion = $registro->check_in->diffInMinutes($registro->check_out);
            $horas = intdiv($duracion, 60);
            $minutos = $duracion % 60;
            return sprintf('%02d:%02d', $horas, $minutos);
        }

        if ($registro->status === 'in_progress' && $registro->service_type !== 'print') {
            return 'En curso';
        }

        return '';
    }

    private function formatStatus(?string $status): string
    {
        return match ($status) {
            'in_progress' => 'En curso',
            'completed' => 'Completado',
            default => $status ?? '',
        };
    }

    private function sumHoras($registros, string $serviceType): float
    {
        $total = 0;
        foreach ($registros->where('service_type', $serviceType) as $reg) {
            if ($reg->check_out) {
                $total += $reg->check_in->diffInMinutes($reg->check_out) / 60;
            }
        }

        return $total;
    }

    private function formatHoras(float $horas): string
    {
        $mins = (int) round($horas * 60);
        $h = intdiv($mins, 60);
        $m = $mins % 60;
        return sprintf('%d:%02d', $h, $m);
    }
}
