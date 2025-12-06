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
        // Vérifier si les colonnes n'existent pas déjà avant de les ajouter
        if (!Schema::hasColumn('accounts', 'suspension_reason')) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->text('suspension_reason')->nullable()->after('status');
                $table->timestamp('suspended_at')->nullable()->after('suspension_reason');
                $table->unsignedBigInteger('suspended_by')->nullable()->after('suspended_at');

                $table->foreign('suspended_by')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropForeign(['suspended_by']);
            $table->dropColumn(['suspension_reason', 'suspended_at', 'suspended_by']);
        });
    }
};
