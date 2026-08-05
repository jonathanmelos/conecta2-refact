<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\McpPermission;

class McpPermissionsSeeder extends Seeder
{
    /**
     * All resources start disabled — the admin turns on exactly what
     * they want Claude to be able to read from the "Permisos MCP" panel.
     */
    public function run(): void
    {
        $resources = [
            'clients',
            'subscriptions',
            'invoices',
            'payments',
            'usage_records',
            'reservations',
            'occupancy',
        ];

        foreach ($resources as $resource) {
            McpPermission::firstOrCreate(
                ['resource' => $resource],
                ['read_enabled' => false]
            );
        }
    }
}
