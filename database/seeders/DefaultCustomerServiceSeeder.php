<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\Service;
use App\Models\CpeDevice;
use Illuminate\Support\Facades\DB;

class DefaultCustomerServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Crea cliente e servizio di default per dispositivi legacy senza assegnazione
     * Creates default customer and service for legacy devices without assignment
     */
    public function run(): void
    {
        DB::transaction(function () {
            $customer = Customer::firstOrCreate(
                ['external_id' => 'DEFAULT_CUSTOMER'],
                [
                    'name' => 'Default Customer',
                    'status' => 'active',
                    'contact_email' => 'admin@acs.local',
                    'timezone' => 'UTC',
                    'metadata' => [
                        'type' => 'system',
                        'description' => 'Default customer for legacy devices',
                    ],
                ]
            );

            $this->command->info("✓ Customer creato: {$customer->name} (ID: {$customer->id})");

            $service = Service::firstOrCreate(
                ['billing_reference' => 'DEFAULT_SERVICE'],
                [
                    'customer_id' => $customer->id,
                    'name' => 'Unassigned Service',
                    'service_type' => 'Other',
                    'status' => 'active',
                    'activation_at' => now(),
                    'sla_tier' => 'Standard',
                    'metadata' => [
                        'type' => 'system',
                        'description' => 'Default service for unassigned devices',
                    ],
                ]
            );

            $this->command->info("✓ Service creato: {$service->name} (ID: {$service->id})");

            $unassignedDevices = CpeDevice::whereNull('service_id')->get();
            
            if ($unassignedDevices->count() > 0) {
                $updated = CpeDevice::whereNull('service_id')
                    ->update(['service_id' => $service->id]);
                
                $this->command->info("✓ Assegnati {$updated} dispositivi al servizio di default");
            } else {
                $this->command->info("ℹ Nessun dispositivo da assegnare");
            }

            $this->command->info("\n✅ Seeder completato con successo!");
            $this->command->info("   Customer: {$customer->name}");
            $this->command->info("   Service: {$service->name}");
            $this->command->info("   Dispositivi assegnati: {$unassignedDevices->count()}");
        });
    }
}
