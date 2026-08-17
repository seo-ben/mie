<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if column exists first to avoid errors if partially run
        if (!Schema::hasColumn('transactions', 'agency_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->unsignedBigInteger('agency_id')->nullable()->after('validated_by');
                $table->foreign('agency_id')->references('id')->on('agencies')->onDelete('set null');
                $table->index('agency_id');
            });
        }

        // Backfill agency_id from account.client.agency_id for existing transactions
        DB::statement("
            UPDATE transactions t
            INNER JOIN accounts a ON t.account_id = a.id
            INNER JOIN clients c ON a.client_id = c.id
            SET t.agency_id = c.agency_id
            WHERE t.agency_id IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['agency_id']);
            $table->dropColumn('agency_id');
        });
    }
};
