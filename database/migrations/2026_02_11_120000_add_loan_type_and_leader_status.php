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
        Schema::table('loans', function (Blueprint $table) {
            $table->string('loan_type')->nullable()->after('loan_number');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('is_leader_or_elected')->default(false)->after('profession');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn('loan_type');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('is_leader_or_elected');
        });
    }
};
