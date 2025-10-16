<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop existing constraint
        DB::statement('ALTER TABLE cpe_devices DROP CONSTRAINT IF EXISTS cpe_devices_mtp_type_check');
        
        // Add new constraint with 'http' included
        DB::statement("ALTER TABLE cpe_devices ADD CONSTRAINT cpe_devices_mtp_type_check CHECK (mtp_type::text = ANY (ARRAY['mqtt'::character varying, 'websocket'::character varying, 'stomp'::character varying, 'coap'::character varying, 'uds'::character varying, 'http'::character varying]::text[]))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop constraint with 'http'
        DB::statement('ALTER TABLE cpe_devices DROP CONSTRAINT IF EXISTS cpe_devices_mtp_type_check');
        
        // Restore original constraint without 'http'
        DB::statement("ALTER TABLE cpe_devices ADD CONSTRAINT cpe_devices_mtp_type_check CHECK (mtp_type::text = ANY (ARRAY['mqtt'::character varying, 'websocket'::character varying, 'stomp'::character varying, 'coap'::character varying, 'uds'::character varying]::text[]))");
    }
};
