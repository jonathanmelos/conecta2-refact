<?php

namespace App\Services\Mcp\Tools;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Mcp\McpToolError;

class BillingTool
{
    public function definitions(): array
    {
        return [
            [
                'name' => 'list_invoices',
                'resource' => 'invoices',
                'description' => 'Lista facturas, con filtros de cliente, estado y saldo pendiente.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'client_id' => ['type' => 'integer'],
                        'status' => ['type' => 'string', 'enum' => ['draft', 'sent', 'paid', 'overdue', 'cancelled']],
                        'overdue' => ['type' => 'boolean', 'description' => 'Solo facturas vencidas (status no pagado y due_date pasada).'],
                        'due_this_month' => ['type' => 'boolean', 'description' => 'Solo facturas que vencen este mes.'],
                        'limit' => ['type' => 'integer', 'default' => 200, 'minimum' => 1, 'maximum' => 1000],
                    ],
                ],
            ],
            [
                'name' => 'list_payments',
                'resource' => 'payments',
                'description' => 'Lista pagos registrados, con filtros de cliente, método y rango de fechas.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'client_id' => ['type' => 'integer'],
                        'method' => ['type' => 'string', 'enum' => ['cash', 'transfer', 'card', 'other']],
                        'from' => ['type' => 'string', 'description' => 'payment_date desde (YYYY-MM-DD).'],
                        'to' => ['type' => 'string', 'description' => 'payment_date hasta (YYYY-MM-DD).'],
                        'limit' => ['type' => 'integer', 'default' => 200, 'minimum' => 1, 'maximum' => 1000],
                    ],
                ],
            ],
        ];
    }

    public function call(string $toolName, array $arguments): array|McpToolError
    {
        return match ($toolName) {
            'list_invoices' => $this->listInvoices($arguments),
            'list_payments' => $this->listPayments($arguments),
            default => new McpToolError('unknown_tool', "Tool no reconocida: {$toolName}"),
        };
    }

    private function listInvoices(array $args): array
    {
        $query = Invoice::query()->with('client')->withSum('payments', 'amount');

        if (!empty($args['client_id'])) {
            $query->where('client_id', $args['client_id']);
        }
        if (!empty($args['status'])) {
            $query->where('status', $args['status']);
        }
        if (!empty($args['overdue'])) {
            $query->where('status', '!=', 'paid')->whereDate('due_date', '<', now());
        }
        if (!empty($args['due_this_month'])) {
            $query->whereBetween('due_date', [now()->startOfMonth(), now()->endOfMonth()]);
        }

        $limit = min((int) ($args['limit'] ?? 200), 1000);
        $invoices = $query->orderByDesc('invoice_date')->limit($limit)->get();

        return [
            'items' => $invoices->map(function ($invoice) {
                $paid = (float) ($invoice->payments_sum_amount ?? 0);
                $total = (float) $invoice->total;

                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'client_id' => $invoice->client_id,
                    'client_name' => $invoice->client?->full_name,
                    'invoice_date' => $invoice->invoice_date?->format('Y-m-d'),
                    'due_date' => $invoice->due_date?->format('Y-m-d'),
                    'total' => $total,
                    'paid' => $paid,
                    'balance' => round($total - $paid, 2),
                    'status' => $invoice->status,
                ];
            })->all(),
        ];
    }

    private function listPayments(array $args): array
    {
        $query = Payment::query()->with('client');

        if (!empty($args['client_id'])) {
            $query->where('client_id', $args['client_id']);
        }
        if (!empty($args['method'])) {
            $query->where('payment_method', $args['method']);
        }
        if (!empty($args['from'])) {
            $query->whereDate('payment_date', '>=', $args['from']);
        }
        if (!empty($args['to'])) {
            $query->whereDate('payment_date', '<=', $args['to']);
        }

        $limit = min((int) ($args['limit'] ?? 200), 1000);
        $payments = $query->orderByDesc('payment_date')->limit($limit)->get();

        return [
            'items' => $payments->map(fn ($p) => [
                'id' => $p->id,
                'client_id' => $p->client_id,
                'client_name' => $p->client?->full_name,
                'invoice_id' => $p->invoice_id,
                'payment_date' => $p->payment_date?->format('Y-m-d'),
                'amount' => (float) $p->amount,
                'payment_method' => $p->payment_method,
                'transaction_reference' => $p->transaction_reference,
            ])->all(),
        ];
    }
}
