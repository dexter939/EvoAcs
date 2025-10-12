<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $fileServers = DB::table('file_servers')
            ->orderBy('storage_service_id')
            ->orderBy('id')
            ->get();

        $serverCounts = [];

        foreach ($fileServers as $server) {
            $storageServiceId = $server->storage_service_id;
            
            if (!isset($serverCounts[$storageServiceId])) {
                $serverCounts[$storageServiceId] = 0;
            }
            
            $serverCounts[$storageServiceId]++;
            
            DB::table('file_servers')
                ->where('id', $server->id)
                ->update(['server_instance' => (string)$serverCounts[$storageServiceId]]);
        }
    }

    public function down(): void
    {
        DB::table('file_servers')->update(['server_instance' => null]);
    }
};
