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
        Schema::table('router_manufacturers', function (Blueprint $table) {
            $table->string('website')->nullable()->after('name');
            $table->text('description')->nullable()->after('country');
        });

        Schema::table('router_products', function (Blueprint $table) {
            $table->string('oui')->nullable()->after('model_name');
            $table->string('product_class')->nullable()->after('oui');
            $table->integer('ports_count')->nullable()->after('max_speed');
            $table->boolean('has_usb')->default(false)->after('ports_count');
            $table->integer('max_speed_mbps')->nullable()->after('max_speed');
            $table->boolean('supports_tr069')->default(false)->after('gaming_features');
            $table->boolean('supports_tr369')->default(false)->after('supports_tr069');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('router_manufacturers', function (Blueprint $table) {
            $table->dropColumn(['website', 'description']);
        });

        Schema::table('router_products', function (Blueprint $table) {
            $table->dropColumn(['oui', 'product_class', 'ports_count', 'has_usb', 'max_speed_mbps', 'supports_tr069', 'supports_tr369']);
        });
    }
};
