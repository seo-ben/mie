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
        if (!Schema::hasColumn('loans', 'application_fee')) {
            Schema::table('loans', function (Blueprint $table) {
                $table->decimal('application_fee', 15, 2)->default(0)->after('requested_amount');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('loans', 'application_fee')) {
            Schema::table('loans', function (Blueprint $table) {
                $table->dropColumn('application_fee');
            });
        }
    }
};
