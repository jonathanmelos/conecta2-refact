<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class MigrateFromOldDatabaseSeeder extends Seeder
{
    private $oldDb = 'conectac_sistema';
    private $newDb = 'conecta_laravel';
    
    public function run(): void
    {
        $this->command->info('🚀 Iniciando migración de datos...');
        
        // Deshabilitar foreign key checks temporalmente
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        try {
            $this->migrateAreas();
            $this->migratePlans();
            $this->migrateClients();
            $this->migrateSubscriptions();
            $this->migrateUsageRecords();
            $this->calculateHoursTracking(); // ✅ NUEVO: Calcular desde usage_records
            $this->migrateUsers();
            
            $this->command->info('✅ Migración completada exitosamente!');
            
        } catch (\Exception $e) {
            $this->command->error('❌ Error en la migración: ' . $e->getMessage());
            throw $e;
        } finally {
            // Rehabilitar foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }
    
    private function migrateAreas(): void
    {
        $this->command->info('📍 Migrando áreas...');
        
        DB::table('areas')->insert([
            [
                'id' => 1,
                'name' => 'Espacio Coworking',
                'code' => 'COWORK',
                'capacity' => 20,
                'description' => 'Área principal de coworking',
                'has_sensor' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'Oficinas',
                'code' => 'OFFICE',
                'capacity' => 10,
                'description' => 'Oficinas privadas',
                'has_sensor' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'name' => 'Sala de Reuniones',
                'code' => 'MEETING',
                'capacity' => 8,
                'description' => 'Sala de reuniones',
                'has_sensor' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        
        $this->command->info('✅ 3 áreas creadas');
    }
    
    private function migratePlans(): void
    {
        $this->command->info('📋 Migrando planes...');
        
        $oldPlans = DB::connection('mysql')->table("{$this->oldDb}.planes")->get();
        
        foreach ($oldPlans as $oldPlan) {
            DB::table('plans')->insert([
                'id' => $oldPlan->codigo,
                'name' => $oldPlan->nombre,
                'cowork_hours' => $oldPlan->cowork ?? 0,
                'meeting_room_hours' => $oldPlan->sala_reuniones ?? 0,
                'prints_included' => $oldPlan->impresiones ?? 0,
                'events_included' => $oldPlan->evento ?? 0,
                'price' => $oldPlan->precio ?? 0,
                'setup_fee' => 0,
                'deposit_required' => 0,
                'operational_cost' => 0,
                'discount_percentage' => 0,
                'description' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        $this->command->info("✅ {$oldPlans->count()} planes migrados");
    }
    
    private function migrateClients(): void
    {
        $this->command->info('👥 Migrando clientes...');
        
        $oldClients = DB::connection('mysql')->table("{$this->oldDb}.clientes")->get();
        
        foreach ($oldClients as $oldClient) {
            // Mapear estados: A=active, E=deleted
            $clientStatus = ($oldClient->estado === 'A') ? 'active' : 'deleted';
            
            // Mapear suscripción: A=active, F=expired
            $subscriptionStatus = ($oldClient->suscripcion === 'A') ? 'active' : 'expired';
            
            DB::table('clients')->insert([
                'document_number' => trim($oldClient->documento),
                'first_name' => $oldClient->nombre ?? '',
                'last_name' => $oldClient->apellido ?? '',
                'email' => $oldClient->correo ?? null,
                'phone' => $oldClient->telefono ?? null,
                'address' => $oldClient->direccion ?? null,
                'ruc' => null,
                'client_status' => $clientStatus,
                'subscription_status' => $subscriptionStatus,
                'current_subscription_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        $this->command->info("✅ {$oldClients->count()} clientes migrados");
    }
    
    private function migrateSubscriptions(): void
    {
        $this->command->info('📝 Migrando suscripciones...');
        
        $oldSubs = DB::connection('mysql')->table("{$this->oldDb}.planes_registro")->get();
        
        $migrated = 0;
        $skipped = 0;
        
        foreach ($oldSubs as $oldSub) {
            $client = DB::table('clients')
                ->where('document_number', trim($oldSub->documento))
                ->first();
            
            if (!$client) {
                $skipped++;
                continue;
            }
            
            $plan = DB::table('plans')->find($oldSub->codigo);
            
            if (!$plan) {
                $this->command->warn("Plan no encontrado: {$oldSub->codigo}");
                $skipped++;
                continue;
            }
            
            $startDate = $this->validateDate($oldSub->fecha_i) 
                ? $oldSub->fecha_i 
                : now()->format('Y-m-d');
                
            $endDate = $this->validateDate($oldSub->fecha_f) 
                ? $oldSub->fecha_f 
                : now()->addMonth()->format('Y-m-d');
            
            $status = ($oldSub->estado === 'A') ? 'active' : 'expired';
            
            try {
                $subscriptionId = DB::table('subscriptions')->insertGetId([
                    'id' => $oldSub->secuencial_planes,
                    'client_id' => $client->id,
                    'plan_id' => $plan->id,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'status' => $status,
                    'monthly_price' => $plan->price,
                    'setup_fee_paid' => 0,
                    'deposit_paid' => 0,
                    'discount_applied' => 0,
                    'total_paid' => 0,
                    'billing_cycle' => 'monthly',
                    'next_billing_date' => null,
                    'auto_renew' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                if ($status === 'active') {
                    DB::table('clients')
                        ->where('id', $client->id)
                        ->update(['current_subscription_id' => $subscriptionId]);
                }
                
                $migrated++;
                
            } catch (\Exception $e) {
                $this->command->warn("Error en suscripción {$oldSub->secuencial_planes}: " . $e->getMessage());
                $skipped++;
            }
        }
        
        $this->command->info("✅ {$migrated} suscripciones migradas, {$skipped} omitidas");
    }
    
    private function migrateUsageRecords(): void
    {
        $this->command->info('📊 Migrando registros de uso...');
        
        $oldRecords = DB::connection('mysql')->table("{$this->oldDb}.registro")->get();
        
        $migrated = 0;
        $skipped = 0;
        
        foreach ($oldRecords as $oldRecord) {
            $client = DB::table('clients')
                ->where('document_number', trim($oldRecord->documento))
                ->first();
            
            if (!$client) {
                $skipped++;
                continue;
            }
            
            if (!$this->validateDate($oldRecord->entrada)) {
                $skipped++;
                continue;
            }
            
            $checkOut = $this->validateDate($oldRecord->salida) 
                ? $oldRecord->salida 
                : null;
            
            // Manejar campo secuencial_2 que puede no existir
$subscriptionId = $oldRecord->secuencial_2 ?? $oldRecord->secuencial_planes ?? null;
$subscription = $subscriptionId ? DB::table('subscriptions')->find($subscriptionId) : null;
            
            $serviceType = match(strtolower($oldRecord->servicio)) {
                'cowork' => 'cowork',
                'sala' => 'meeting_room',
                'impresiones' => 'print',
                'evento' => 'event',
                default => 'cowork',
            };
            
            $areaId = match($serviceType) {
                'cowork' => 1,
                'meeting_room' => 3,
                default => null,
            };
            
            $status = ($oldRecord->estado === 'E') ? 'in_progress' : 'completed';
            
            try {
                DB::table('usage_records')->insert([
                    'id' => $oldRecord->secuencial_R,
                    'client_id' => $client->id,
                    'subscription_id' => $subscription->id ?? null,
                    'area_id' => $areaId,
                    'service_type' => $serviceType,
                    'check_in' => $oldRecord->entrada,
                    'check_out' => $checkOut,
                    'quantity' => $oldRecord->cantidad ?? 1,
                    'status' => $status,
                    'registration_method' => 'manual',
                    'is_billable' => false,
                    'unit_price' => null,
                    'total_charged' => 0,
                    'invoiced' => false,
                    'invoice_item_id' => null,
                    'notes' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                $migrated++;
                
            } catch (\Exception $e) {
                $skipped++;
            }
        }
        
        $this->command->info("✅ {$migrated} registros de uso migrados, {$skipped} omitidos");
    }
    
    private function calculateHoursTracking(): void
    {
        $this->command->info('⏱️ Calculando control de horas...');
        
        // Obtener todas las suscripciones activas o expiradas recientemente
        $subscriptions = DB::table('subscriptions')->get();
        
        $created = 0;
        
        foreach ($subscriptions as $subscription) {
            $plan = DB::table('plans')->find($subscription->plan_id);
            
            if (!$plan) {
                continue;
            }
            
            // Calcular horas de COWORK
            if ($plan->cowork_hours > 0) {
                $hoursUsed = DB::table('usage_records')
                    ->where('subscription_id', $subscription->id)
                    ->where('service_type', 'cowork')
                    ->where('status', 'completed')
                    ->whereNotNull('check_out')
                    ->get()
                    ->sum(function ($record) {
                        $checkIn = Carbon::parse($record->check_in);
                        $checkOut = Carbon::parse($record->check_out);
                        return $checkIn->diffInHours($checkOut, true);
                    });
                
                DB::table('hours_tracking')->insert([
                    'client_id' => $subscription->client_id,
                    'subscription_id' => $subscription->id,
                    'service_type' => 'cowork',
                    'hours_used' => round($hoursUsed, 2),
                    'total_hours_available' => $plan->cowork_hours,
                    'last_updated' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                $created++;
            }
            
            // Calcular horas de SALA DE REUNIONES
            if ($plan->meeting_room_hours > 0) {
                $hoursUsed = DB::table('usage_records')
                    ->where('subscription_id', $subscription->id)
                    ->where('service_type', 'meeting_room')
                    ->where('status', 'completed')
                    ->whereNotNull('check_out')
                    ->get()
                    ->sum(function ($record) {
                        $checkIn = Carbon::parse($record->check_in);
                        $checkOut = Carbon::parse($record->check_out);
                        return $checkIn->diffInHours($checkOut, true);
                    });
                
                DB::table('hours_tracking')->insert([
                    'client_id' => $subscription->client_id,
                    'subscription_id' => $subscription->id,
                    'service_type' => 'meeting_room',
                    'hours_used' => round($hoursUsed, 2),
                    'total_hours_available' => $plan->meeting_room_hours,
                    'last_updated' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                $created++;
            }
        }
        
        $this->command->info("✅ {$created} registros de control de horas calculados");
    }
    
    private function migrateUsers(): void
    {
        $this->command->info('👤 Migrando usuarios...');
        
        $oldUsers = DB::connection('mysql')->table("{$this->oldDb}.users")->get();
        
        foreach ($oldUsers as $oldUser) {
            DB::table('users')->insert([
                'id' => $oldUser->id,
                'name' => $oldUser->username,
                'email' => $oldUser->username . '@conectacowork.com',
                'password' => Hash::make($oldUser->password),
                'role' => $oldUser->user_type,
                'email_verified_at' => now(),
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        $this->command->info("✅ {$oldUsers->count()} usuarios migrados (contraseñas hasheadas)");
    }
    
    private function validateDate($date): bool
    {
        if (empty($date) || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
            return false;
        }
        
        try {
            Carbon::parse($date);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}