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
        Schema::table('configuration_profiles', function (Blueprint $table) {
            $table->boolean('ai_generated')->default(false)->after('is_active');
            $table->string('ai_model_used')->nullable()->after('ai_generated');
            $table->text('ai_prompt')->nullable()->after('ai_model_used');
            $table->json('ai_suggestions')->nullable()->after('ai_prompt');
            $table->timestamp('ai_generated_at')->nullable()->after('ai_suggestions');
            $table->integer('ai_confidence_score')->nullable()->after('ai_generated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configuration_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'ai_generated',
                'ai_model_used',
                'ai_prompt',
                'ai_suggestions',
                'ai_generated_at',
                'ai_confidence_score'
            ]);
        });
    }
};
