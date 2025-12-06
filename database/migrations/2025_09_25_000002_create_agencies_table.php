<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('agencies', function (Blueprint $table) {
            $table->unsignedBigInteger('id', true);
            $table->string('name', 100);
            $table->string('code', 20)->unique();
            $table->text('address')->nullable();
            $table->string('city',100)->nullable();
            $table->string('region',100)->nullable();
            $table->string('phone',20)->nullable();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->decimal('cash_limit', 15,2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('manager_id')->references('id')->on('users')->onDelete('set null');
            
            $table->index('code');
            $table->index('is_active');
        });

        // Add agency_id to users table
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('agency_id')->nullable()->after('mfa_secret');
            $table->foreign('agency_id')->references('id')->on('agencies')->onDelete('set null');
            $table->index('agency_id');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['agency_id']);
            $table->dropColumn('agency_id');
        });
        Schema::dropIfExists('agencies');
    }
};