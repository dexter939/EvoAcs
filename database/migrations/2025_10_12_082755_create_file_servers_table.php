<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_servers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('storage_service_id')->constrained()->onDelete('cascade');
            $table->string('server_type'); // FTP, SFTP, HTTP, HTTPS, SAMBA, NFS
            $table->boolean('enabled')->default(true);
            
            // Server Configuration
            $table->integer('port')->nullable();
            $table->string('bind_interface')->default('0.0.0.0');
            $table->integer('max_connections')->default(10);
            $table->integer('timeout')->default(300); // seconds
            
            // FTP/SFTP Specific
            $table->string('welcome_message')->nullable();
            $table->boolean('anonymous_enabled')->default(false);
            $table->string('anonymous_directory')->nullable();
            $table->boolean('passive_mode')->default(true);
            $table->integer('passive_port_min')->nullable();
            $table->integer('passive_port_max')->nullable();
            
            // HTTP/HTTPS Specific
            $table->string('document_root')->nullable();
            $table->boolean('directory_listing')->default(false);
            $table->boolean('ssl_enabled')->default(false);
            $table->text('ssl_certificate')->nullable();
            $table->text('ssl_private_key')->nullable();
            
            // SAMBA Specific
            $table->string('workgroup')->nullable();
            $table->string('server_string')->nullable();
            $table->boolean('guest_ok')->default(false);
            
            // Authentication
            $table->boolean('auth_required')->default(true);
            $table->string('auth_method')->default('local'); // local, ldap, ad
            
            // Access Control
            $table->json('allowed_users')->nullable();
            $table->json('allowed_groups')->nullable();
            $table->json('ip_whitelist')->nullable();
            $table->json('ip_blacklist')->nullable();
            
            // Logging
            $table->boolean('logging_enabled')->default(true);
            $table->string('log_level')->default('INFO'); // DEBUG, INFO, WARNING, ERROR
            $table->string('log_file_path')->nullable();
            
            // Statistics
            $table->bigInteger('total_connections')->default(0);
            $table->bigInteger('total_uploads')->default(0);
            $table->bigInteger('total_downloads')->default(0);
            $table->bigInteger('bytes_uploaded')->default(0);
            $table->bigInteger('bytes_downloaded')->default(0);
            $table->timestamp('last_connection')->nullable();
            
            // Status
            $table->string('status')->default('Stopped'); // Running, Stopped, Error
            $table->integer('current_connections')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['storage_service_id', 'server_type']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_servers');
    }
};
