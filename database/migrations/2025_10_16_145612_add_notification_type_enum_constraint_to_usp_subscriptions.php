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
        // Clean up any legacy event_path values that don't match the new enum
        DB::statement("DELETE FROM usp_subscriptions WHERE notification_type NOT IN ('ValueChange', 'Event', 'ObjectCreation', 'ObjectDeletion', 'OperationComplete')");
        
        // Now add the constraint
        DB::statement("ALTER TABLE usp_subscriptions ADD CONSTRAINT usp_subscriptions_notification_type_check CHECK (notification_type::text = ANY (ARRAY['ValueChange'::character varying, 'Event'::character varying, 'ObjectCreation'::character varying, 'ObjectDeletion'::character varying, 'OperationComplete'::character varying]::text[]))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE usp_subscriptions DROP CONSTRAINT IF EXISTS usp_subscriptions_notification_type_check');
    }
};
