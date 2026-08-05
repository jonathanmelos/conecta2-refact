<?php

namespace App\Services\Mcp\Tools;

use App\Models\Client;
use App\Models\UsageRecord;
use App\Services\Mcp\McpToolError;
use Carbon\Carbon;

class UsageTool
{
    public function definitions(): array
    {
        return [
            [
                'name' => 'list_usage_records',
                'resource' => 'usage_records',
                'description' => 'Lista registros de uso (check-in/check-out de cowork, sala de reuniones, impresiones).',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'service_type' => ['type' => 'string'],
                        'status' => ['type' => 'string'],
                        'client_id' => ['type' => 'integer'],
                        'from' => ['type' => 'string', 'description' => 'check_in desde (YYYY-MM-DD).'],
                        'to' => ['type' => 'string', 'description' => 'check_in hasta (YYYY-MM-DD).'],
                        'limit' => ['type' => 'integer', 'default' => 200, 'minimum' => 1, 'maximum' => 1000],
                    ],
                ],
            ],
            [
                'name' => 'get_client_hours_summary',
                'resource' => 'usage_records',
                'description' => 'Calcula horas consumidas por un cliente (cowork y sala de reuniones) en un rango de fechas, comparado contra su suscripción activa.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'client_id' => ['type' => 'integer'],
                        'from' => ['type' => 'string'],
                        'to' => ['type' => 'string'],
                    ],
                    'required' => ['client_id'],
                ],
            ],
            [
                'name' => 'get_daily_summary',
                'resource' => 'usage_records',
                'description' => 'Resumen operativo de un día: check-ins por tipo de servicio, horas consumidas, impresiones y quiénes están activos ahora mismo.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'date' => ['type' => 'string', 'description' => 'YYYY-MM-DD, por defecto hoy.'],
                    ],
                ],
            ],
        ];
    }

    public function call(string $toolName, array $arguments): array|McpToolError
    {
        return match ($toolName) {
            'list_usage_records' => $this->listUsageRecords($arguments),
            'get_client_hours_summary' => $this->getClientHoursSummary($arguments),
            'get_daily_summary' => $this->getDailySummary($arguments),
            default => new McpToolError('unknown_tool', "Tool no reconocida: {$toolName}"),
        };
    }

    private function listUsageRecords(array $args): array
    {
        $query = UsageRecord::query()->with(['client', 'subscription.plan']);

        if (!empty($args['service_type'])) {
            $query->where('service_type', $args['service_type']);
        }
        if (!empty($args['status'])) {
            $query->where('status', $args['status']);
        }
        if (!empty($args['client_id'])) {
            $query->where('client_id', $args['client_id']);
        }
        if (!empty($args['from'])) {
            $query->whereDate('check_in', '>=', $args['from']);
        }
        if (!empty($args['to'])) {
            $query->whereDate('check_in', '<=', $args['to']);
        }

        $limit = min((int) ($args['limit'] ?? 200), 1000);
        $records = $query->orderByDesc('check_in')->limit($limit)->get();

        return [
            'items' => $records->map(fn ($r) => [
                'id' => $r->id,
                'client_id' => $r->client_id,
                'client_name' => $r->client?->full_name,
                'subscription_id' => $r->subscription_id,
                'plan_name' => $r->subscription?->plan?->name,
                'service_type' => $r->service_type,
                'status' => $r->status,
                'check_in' => $r->check_in?->toDateTimeString(),
                'check_out' => $r->check_out?->toDateTimeString(),
                'duration_minutes' => $r->duration_in_minutes,
                'is_billable' => (bool) $r->is_billable,
            ])->all(),
        ];
    }

    /**
     * Replicates the aggregation pattern from
     * ClientController::recalcularHorasTracking (app/Http/Controllers/Admin/
     * ClientController.php) — hours consumed are computed by summing
     * check_in→check_out diffs per service_type over completed records,
     * not a plain SUM() column, so that logic is intentionally duplicated
     * here rather than querying a precomputed total.
     */
    private function getClientHoursSummary(array $args): array|McpToolError
    {
        if (empty($args['client_id'])) {
            return new McpToolError('invalid_arguments', 'Debes indicar "client_id".');
        }

        $client = Client::find($args['client_id']);
        if (!$client) {
            return new McpToolError('not_found', 'Cliente no encontrado.');
        }

        $query = UsageRecord::query()
            ->where('client_id', $client->id)
            ->where('status', 'completed')
            ->whereNotNull('check_out');

        if (!empty($args['from'])) {
            $query->whereDate('check_in', '>=', $args['from']);
        }
        if (!empty($args['to'])) {
            $query->whereDate('check_in', '<=', $args['to']);
        }

        $records = $query->get(['service_type', 'check_in', 'check_out']);

        $minutesByType = [];
        foreach ($records as $record) {
            $minutes = Carbon::parse($record->check_in)->diffInMinutes(Carbon::parse($record->check_out));
            $minutesByType[$record->service_type] = ($minutesByType[$record->service_type] ?? 0) + $minutes;
        }

        $activeSubscription = $client->subscriptions()
            ->where('status', 'active')
            ->with('plan')
            ->latest('start_date')
            ->first();

        return [
            'client_id' => $client->id,
            'client_name' => $client->full_name,
            'usage_by_service_type' => collect($minutesByType)->map(fn ($minutes) => [
                'minutes' => $minutes,
                'hours' => round($minutes / 60, 2),
            ])->all(),
            'active_subscription' => $activeSubscription ? [
                'plan_name' => $activeSubscription->plan?->name,
                'cowork_hours_included' => $activeSubscription->effective_cowork_hours,
                'meeting_room_hours_included' => $activeSubscription->effective_meeting_room_hours,
            ] : null,
        ];
    }

    /**
     * Adapted from DashboardController::diario (app/Http/Controllers/Admin/
     * DashboardController.php) — same day-level stats shape, recomputed via
     * MCP-safe read-only queries.
     */
    private function getDailySummary(array $args): array
    {
        $date = !empty($args['date']) ? Carbon::parse($args['date']) : now();

        $records = UsageRecord::query()
            ->whereDate('check_in', $date->toDateString())
            ->get(['service_type', 'status', 'check_in', 'check_out']);

        $byType = $records->groupBy('service_type')->map->count();
        $active = $records->where('status', 'in_progress')->count();

        $hoursByType = [];
        foreach ($records->where('status', 'completed')->whereNotNull('check_out') as $record) {
            $minutes = Carbon::parse($record->check_in)->diffInMinutes(Carbon::parse($record->check_out));
            $hoursByType[$record->service_type] = ($hoursByType[$record->service_type] ?? 0) + round($minutes / 60, 2);
        }

        return [
            'date' => $date->toDateString(),
            'checkins_by_service_type' => $byType->all(),
            'active_now' => $active,
            'hours_by_service_type' => $hoursByType,
            'total_records' => $records->count(),
        ];
    }
}
