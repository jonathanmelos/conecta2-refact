<?php

namespace App\Services\Mcp\Tools;

use App\Models\Area;
use App\Models\AreaOccupancy;
use App\Models\Reservation;
use App\Services\Mcp\McpToolError;

class OccupancyTool
{
    public function definitions(): array
    {
        return [
            [
                'name' => 'list_reservations',
                'resource' => 'reservations',
                'description' => 'Lista reservas de cowork, sala de reuniones o eventos corporativos.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'type' => ['type' => 'string', 'enum' => ['cowork', 'meeting_room', 'corporate_event']],
                        'status' => ['type' => 'string', 'enum' => ['confirmed', 'cancelled', 'completed']],
                        'client_id' => ['type' => 'integer'],
                        'upcoming' => ['type' => 'boolean', 'description' => 'Solo reservas futuras y confirmadas.'],
                        'from' => ['type' => 'string'],
                        'to' => ['type' => 'string'],
                        'limit' => ['type' => 'integer', 'default' => 200, 'minimum' => 1, 'maximum' => 1000],
                    ],
                ],
            ],
            [
                'name' => 'get_area_occupancy',
                'resource' => 'occupancy',
                'description' => 'Ocupación actual de todas las áreas del coworking (quién está adentro ahora, capacidad y espacio disponible).',
                'inputSchema' => ['type' => 'object', 'properties' => (object) []],
            ],
        ];
    }

    public function call(string $toolName, array $arguments): array|McpToolError
    {
        return match ($toolName) {
            'list_reservations' => $this->listReservations($arguments),
            'get_area_occupancy' => $this->getAreaOccupancy(),
            default => new McpToolError('unknown_tool', "Tool no reconocida: {$toolName}"),
        };
    }

    private function listReservations(array $args): array
    {
        $query = Reservation::query()->with('client');

        if (!empty($args['type'])) {
            $query->where('reservation_type', $args['type']);
        }
        if (!empty($args['status'])) {
            $query->where('status', $args['status']);
        }
        if (!empty($args['client_id'])) {
            $query->where('client_id', $args['client_id']);
        }
        if (!empty($args['upcoming'])) {
            $query->where('reservation_date', '>=', now()->toDateString())->where('status', 'confirmed');
        }
        if (!empty($args['from'])) {
            $query->whereDate('reservation_date', '>=', $args['from']);
        }
        if (!empty($args['to'])) {
            $query->whereDate('reservation_date', '<=', $args['to']);
        }

        $limit = min((int) ($args['limit'] ?? 200), 1000);
        $reservations = $query->orderBy('reservation_date')->orderBy('start_time')->limit($limit)->get();

        return [
            'items' => $reservations->map(fn ($r) => [
                'id' => $r->id,
                'client_id' => $r->client_id,
                'client_name' => $r->client?->full_name,
                'reservation_type' => $r->reservation_type,
                'event_title' => $r->event_title,
                'reservation_date' => $r->reservation_date?->format('Y-m-d'),
                'start_time' => $r->start_time,
                'end_time' => $r->end_time,
                'status' => $r->status,
                'attendees_count' => $r->attendees_count,
            ])->all(),
        ];
    }

    /**
     * Area::current_occupancy is a per-model accessor that runs one query
     * each time it's called (app/Models/Area.php) — fine for a single area,
     * wasteful for listing all of them. This does one GROUP BY query over
     * area_occupancy instead, matching the [area_id, check_out] index
     * that migration already defines for exactly this kind of lookup.
     */
    private function getAreaOccupancy(): array
    {
        $activeCounts = AreaOccupancy::query()
            ->whereNull('check_out')
            ->selectRaw('area_id, COUNT(*) as active_count')
            ->groupBy('area_id')
            ->pluck('active_count', 'area_id');

        $areas = Area::query()->where('is_active', true)->get();

        return [
            'areas' => $areas->map(function ($area) use ($activeCounts) {
                $current = (int) ($activeCounts[$area->id] ?? 0);

                return [
                    'id' => $area->id,
                    'name' => $area->name,
                    'code' => $area->code,
                    'capacity' => $area->capacity,
                    'current_occupancy' => $current,
                    'available_space' => max(0, $area->capacity - $current),
                    'is_full' => $current >= $area->capacity,
                ];
            })->all(),
        ];
    }
}
