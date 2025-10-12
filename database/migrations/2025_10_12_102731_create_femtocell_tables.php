<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('femtocell_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cpe_device_id')->constrained('cpe_devices')->onDelete('cascade');
            $table->string('technology', 50)->comment('UMTS, LTE, 5G');
            $table->decimal('gps_latitude', 10, 7)->nullable();
            $table->decimal('gps_longitude', 10, 7)->nullable();
            $table->decimal('gps_altitude', 10, 2)->nullable();
            $table->integer('uarfcn')->nullable()->comment('UMTS ARFCN');
            $table->integer('earfcn')->nullable()->comment('LTE ARFCN');
            $table->integer('physical_cell_id')->nullable()->comment('PCI for LTE');
            $table->integer('tx_power')->comment('Transmission power in dBm');
            $table->integer('max_tx_power')->default(20);
            $table->json('rf_parameters')->nullable()->comment('Additional RF settings');
            $table->json('plmn_list')->nullable()->comment('Allowed PLMN list');
            $table->boolean('auto_config')->default(true);
            $table->string('status', 50)->default('active');
            $table->timestamps();
            $table->index(['cpe_device_id', 'technology']);
        });

        Schema::create('neighbor_cell_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('femtocell_config_id')->constrained('femtocell_configs')->onDelete('cascade');
            $table->string('neighbor_type', 50)->comment('intra_freq, inter_freq, inter_rat');
            $table->integer('neighbor_arfcn')->nullable();
            $table->integer('neighbor_pci')->nullable();
            $table->integer('rssi')->nullable()->comment('Signal strength');
            $table->integer('rsrp')->nullable()->comment('Reference Signal Received Power');
            $table->integer('rsrq')->nullable()->comment('Reference Signal Received Quality');
            $table->boolean('is_blacklisted')->default(false);
            $table->json('rem_data')->nullable()->comment('Radio Environment Map data');
            $table->timestamp('last_scanned')->nullable();
            $table->timestamps();
            $table->index(['femtocell_config_id', 'neighbor_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('neighbor_cell_lists');
        Schema::dropIfExists('femtocell_configs');
    }
};
