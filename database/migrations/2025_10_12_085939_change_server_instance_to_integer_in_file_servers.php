<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE file_servers ALTER COLUMN server_instance TYPE INTEGER USING server_instance::integer');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE file_servers ALTER COLUMN server_instance TYPE VARCHAR(10) USING server_instance::varchar');
    }
};
