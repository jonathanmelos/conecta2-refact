<?php

namespace App\Services\Mcp\Tools;

use App\Models\Client;
use App\Services\Mcp\McpToolError;

/**
 * Read-only MCP tools over the Client model. Mirrors the field selection
 * already used by AnalyticsController::clients (app/Http/Controllers/Api/
 * AnalyticsController.php) so no client data beyond what's already exposed
 * via the existing analytics API leaks through a new surface.
 */
class ClientsTool
{
    public function definitions(): array
    {
        return [
            [
                'name' => 'list_clients',
                'resource' => 'clients',
                'description' => 'Lista clientes del coworking, con filtros de búsqueda y estado.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'search' => ['type' => 'string', 'description' => 'Busca por número de documento, nombre o apellido.'],
                        'status' => ['type' => 'string', 'enum' => ['active', 'deleted']],
                        'from' => ['type' => 'string', 'description' => 'Fecha de creación desde (YYYY-MM-DD).'],
                        'to' => ['type' => 'string', 'description' => 'Fecha de creación hasta (YYYY-MM-DD).'],
                        'limit' => ['type' => 'integer', 'default' => 200, 'minimum' => 1, 'maximum' => 1000],
                    ],
                ],
            ],
            [
                'name' => 'get_client',
                'resource' => 'clients',
                'description' => 'Obtiene el detalle de un cliente por ID o número de documento, con sus suscripciones.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'document_number' => ['type' => 'string'],
                    ],
                ],
            ],
        ];
    }

    public function call(string $toolName, array $arguments): array|McpToolError
    {
        return match ($toolName) {
            'list_clients' => $this->listClients($arguments),
            'get_client' => $this->getClient($arguments),
            default => new McpToolError('unknown_tool', "Tool no reconocida: {$toolName}"),
        };
    }

    private function listClients(array $args): array
    {
        $query = Client::query()->withCount(['subscriptions', 'usageRecords']);

        if (!empty($args['search'])) {
            $search = $args['search'];
            $query->where(function ($q) use ($search) {
                $q->where('document_number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        if (!empty($args['status'])) {
            $query->where('client_status', $args['status']);
        }
        if (!empty($args['from'])) {
            $query->whereDate('created_at', '>=', $args['from']);
        }
        if (!empty($args['to'])) {
            $query->whereDate('created_at', '<=', $args['to']);
        }

        $limit = min((int) ($args['limit'] ?? 200), 1000);
        $clients = $query->orderByDesc('created_at')->limit($limit)->get();

        return [
            'items' => $clients->map(fn ($c) => [
                'id' => $c->id,
                'document_number' => $c->document_number,
                'full_name' => $c->full_name,
                'email' => $c->email,
                'phone' => $c->phone,
                'client_status' => $c->client_status,
                'subscription_status' => $c->subscription_status,
                'subscriptions_count' => $c->subscriptions_count,
                'usage_records_count' => $c->usage_records_count,
                'created_at' => $c->created_at?->toDateTimeString(),
            ])->all(),
            'total' => $clients->count(),
        ];
    }

    private function getClient(array $args): array|McpToolError
    {
        $query = Client::query()->with(['subscriptions.plan']);

        if (!empty($args['id'])) {
            $client = $query->find($args['id']);
        } elseif (!empty($args['document_number'])) {
            $client = $query->where('document_number', $args['document_number'])->first();
        } else {
            return new McpToolError('invalid_arguments', 'Debes indicar "id" o "document_number".');
        }

        if (!$client) {
            return new McpToolError('not_found', 'Cliente no encontrado.');
        }

        return [
            'id' => $client->id,
            'document_number' => $client->document_number,
            'full_name' => $client->full_name,
            'email' => $client->email,
            'phone' => $client->phone,
            'address' => $client->address,
            'ruc' => $client->ruc,
            'client_status' => $client->client_status,
            'subscription_status' => $client->subscription_status,
            'created_at' => $client->created_at?->toDateTimeString(),
            'subscriptions' => $client->subscriptions->map(fn ($s) => [
                'id' => $s->id,
                'plan_name' => $s->plan?->name,
                'status' => $s->status,
                'start_date' => $s->start_date?->format('Y-m-d'),
                'end_date' => $s->end_date?->format('Y-m-d'),
            ])->all(),
        ];
    }
}
