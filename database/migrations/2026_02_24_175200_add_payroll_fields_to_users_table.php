<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('base_salary', 15, 2)->nullable()->after('role');
            $table->string('payment_method')->default('cash')->after('base_salary');
            $table->text('bank_account_info')->nullable()->after('payment_method');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['base_salary', 'payment_method', 'bank_account_info']);
        });
    }
};
