<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('client_documents', function (Blueprint $table) {
            $table->unsignedBigInteger('id', true);
            $table->unsignedBigInteger('client_id');
            $table->enum('document_type',['id_front','id_back','photo','proof_address','proof_income','other']);
            $table->string('file_url',255);
            $table->string('file_name',255)->nullable();
            $table->integer('file_size')->nullable();
            $table->string('mime_type',100)->nullable();
            $table->enum('status',['pending','approved','rejected'])->default('pending');
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('verified_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['client_id','document_type']);
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('client_documents');
    }
};