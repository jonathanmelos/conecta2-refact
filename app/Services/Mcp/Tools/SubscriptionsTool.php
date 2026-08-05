<?php

namespace App\Services\Mcp\Tools;

use App\Models\Plan;
use App\Models\Subscription;
use App\Services\Mcp\McpToolError;

class SubscriptionsTool
{
    public function definitions(): array
    {
        return [
            [
                'name' => 'list_plans',
                'resource' => 'subscriptions',
                'description' => 'Lista los planes de membresía del coworking.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'active' => ['type' => 'boolean', 'description' => 'Filtra solo planes activos si es true.'],
                    ],
                ],
            ],
            [
                'name' => 'list_subscriptions',
                'resource' => 'subscriptions',
                'description' => 'Lista suscripciones de clientes, con filtros de estado, cliente y rango de fechas.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'status' => ['type' => 'string'],
                        'client_id' => ['type' => 'integer'],
                        'from' => ['type' => 'string', 'description' => 'start_date desde (YYYY-MM-DD).'],
                        'to' => ['type' => 'string', 'description' => 'start_date hasta (YYYY-MM-DD).'],
                        'limit' => ['type' => 'integer', 'default' => 200, 'minimum' => 1, 'maximum' => 1000],
                    ],
                ],
            ],
        ];
    }

    public function call(string $toolName, array $arguments): array|McpToolError
    {
        return match ($toolName) {
            'list_plans' => $this->listPlans($arguments),
            'list_subscriptions' => $this->listSubscriptions($arguments),
            default => new McpToolError('unknown_tool', "Tool no reconocida: {$toolName}"),
        };
    }

    private function listPlans(array $args): array
    {
        $query = Plan::query()->withCount('subscriptions');

        if (array_key_exists('active', $args)) {
            $query->where('is_active', (bool) $args['active']);
        }

        $plans = $query->orderBy('price')->get();

        return [
            'items' => $plans->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'cowork_hours' => $p->cowork_hours,
                'meeting_room_hours' => $p->meeting_room_hours,
                'prints_included' => $p->prints_included,
                'events_included' => $p->events_included,
                'price' => (float) $p->price,
                'is_active' => (bool) $p->is_active,
                'subscriptions_count' => $p->subscriptions_count,
            ])->all(),
        ];
    }

    private function listSubscriptions(array $args): array
    {
        $query = Subscription::query()->with(['client', 'plan']);

        if (!empty($args['status'])) {
            $query->where('status', $args['status']);
        }
        if (!empty($args['client_id'])) {
            $query->where('client_id', $args['client_id']);
        }
        if (!empty($args['from'])) {
            $query->whereDate('start_date', '>=', $args['from']);
        }
        if (!empty($args['to'])) {
            $query->whereDate('start_date', '<=', $args['to']);
        }

        $limit = min((int) ($args['limit'] ?? 200), 1000);
        $subscriptions = $query->orderByDesc('start_date')->limit($limit)->get();

        return [
            'items' => $subscriptions->map(fn ($s) => [
                'id' => $s->id,
                'client_id' => $s->client_id,
                'client_name' => $s->client?->full_name,
                'plan_id' => $s->plan_id,
                'plan_name' => $s->plan?->name,
                'status' => $s->status,
                'start_date' => $s->start_date?->format('Y-m-d'),
                'end_date' => $s->end_date?->format('Y-m-d'),
                'monthly_price' => (float) ($s->monthly_price ?? $s->plan?->price ?? 0),
            ])->all(),
        ];
    }
}
