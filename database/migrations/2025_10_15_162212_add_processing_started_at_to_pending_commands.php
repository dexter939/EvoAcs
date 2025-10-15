<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pending_commands', function (Blueprint $table) {
            $table->timestamp('processing_started_at')->nullable()->after('executed_at');
        });
        
        // Backfill: imposta processing_started_at per comandi già in processing
        // Backfill: set processing_started_at for commands already in processing
        DB::statement("
            UPDATE pending_commands 
            SET processing_started_at = COALESCE(executed_at, updated_at) 
            WHERE status = 'processing' AND processing_started_at IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pending_commands', function (Blueprint $table) {
            $table->dropColumn('processing_started_at');
        });
    }
};
