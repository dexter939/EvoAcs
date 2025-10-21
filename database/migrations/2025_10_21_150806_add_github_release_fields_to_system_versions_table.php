<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('system_versions')) {
            return;
        }

        Schema::table('system_versions', function (Blueprint $table) {
            if (!Schema::hasColumn('system_versions', 'github_release_url')) {
                $table->string('github_release_url')->nullable()->after('rollback_version');
            }
            
            if (!Schema::hasColumn('system_versions', 'github_release_tag')) {
                $table->string('github_release_tag', 100)->nullable()->after('github_release_url');
            }
            
            if (!Schema::hasColumn('system_versions', 'download_path')) {
                $table->string('download_path')->nullable()->after('github_release_tag');
            }
            
            if (!Schema::hasColumn('system_versions', 'package_checksum')) {
                $table->string('package_checksum', 64)->nullable()->after('download_path')
                    ->comment('SHA256 checksum of downloaded package');
            }
            
            if (!Schema::hasColumn('system_versions', 'approval_status')) {
                $table->enum('approval_status', ['pending', 'approved', 'rejected', 'scheduled'])
                    ->default('pending')
                    ->after('package_checksum');
            }
            
            if (!Schema::hasColumn('system_versions', 'approved_by')) {
                $table->string('approved_by', 100)->nullable()->after('approval_status');
            }
            
            if (!Schema::hasColumn('system_versions', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
            
            if (!Schema::hasColumn('system_versions', 'scheduled_at')) {
                $table->timestamp('scheduled_at')->nullable()->after('approved_at')
                    ->comment('When update is scheduled to be applied');
            }
            
            if (!Schema::hasColumn('system_versions', 'changelog')) {
                $table->text('changelog')->nullable()->after('scheduled_at');
            }
            
            if (!Schema::hasColumn('system_versions', 'release_notes')) {
                $table->text('release_notes')->nullable()->after('changelog');
            }
        });

        Schema::table('system_versions', function (Blueprint $table) {
            $indexName = 'system_versions_approval_status_index';
            $indexes = DB::select("SELECT indexname FROM pg_indexes WHERE tablename = 'system_versions' AND indexname = ?", [$indexName]);
            if (empty($indexes)) {
                $table->index('approval_status');
            }
            
            $indexName = 'system_versions_github_release_tag_index';
            $indexes = DB::select("SELECT indexname FROM pg_indexes WHERE tablename = 'system_versions' AND indexname = ?", [$indexName]);
            if (empty($indexes)) {
                $table->index('github_release_tag');
            }
        });
    }

    public function down(): void
    {
        Schema::table('system_versions', function (Blueprint $table) {
            $table->dropColumn([
                'github_release_url',
                'github_release_tag',
                'download_path',
                'package_checksum',
                'approval_status',
                'approved_by',
                'approved_at',
                'scheduled_at',
                'changelog',
                'release_notes',
            ]);
        });
    }
};
