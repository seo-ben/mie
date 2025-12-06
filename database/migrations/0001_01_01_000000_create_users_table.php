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
        Schema::create('users', function (Blueprint $table) {
            $table->unsignedBigInteger('id', true);
            $table->string('username', 50)->unique();
            $table->string('email', 100)->unique();
            $table->string('phone', 20)->unique()->nullable();
            $table->string('password', 255);
            $table->enum('role', [
                'agent_terrain',
                'agent_agence',
                'gestionnaire_superviseur',
                'gestionnaire_credit',
                'administrateur_systeme',
                'administrateur_reglementaire']);
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login')->nullable();
            $table->string('password_reset_token',255)->nullable();
            $table->timestamp('password_reset_expires')->nullable();
            $table->boolean('mfa_enabled')->default(false);
            $table->string('mfa_secret',32)->nullable();
            $table->string('remember_token',32)->nullable();
            $table->timestamps();

            $table->index('role');
            $table->index('is_active');
            $table->index('email');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
