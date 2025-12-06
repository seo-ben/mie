<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('clients', function (Blueprint $table) {
$table->id();
        // Authentication fields
        $table->string('client_number', 20)->unique();
        $table->string('phone')->unique();
        $table->string('password');
        $table->boolean('is_active')->default(false);

        // Personal information
        $table->string('first_name');
        $table->string('last_name');
        $table->string('email')->unique()->nullable();
        $table->string('password_reset_token')->nullable();
        $table->date('date_of_birth')->nullable();
        $table->enum('gender', ['M', 'F', 'Other'])->nullable();
        $table->text('address')->nullable();
        $table->string('city', 100)->nullable();
        $table->string('region', 100)->nullable();
        $table->string('profession', 100)->nullable();
        $table->decimal('monthly_income', 12, 2)->nullable();

        // ID verification
        $table->enum('id_type', ['cni', 'passport', 'driving_license', 'other'])->nullable();
        $table->string('id_number', 50)->nullable();
        $table->date('id_expiry_date')->nullable();
        $table->string('profile_photo_url', 255)->nullable();

        // Registration and KYC
        $table->enum('kyc_status', ['pending', 'approved', 'rejected', 'incomplete'])->default('pending');
        $table->timestamp('kyc_approved_at')->nullable();
        $table->unsignedBigInteger('kyc_approved_by')->nullable();
        $table->enum('registration_channel', ['mobile_app', 'web_portal', 'agent_assisted']);
        $table->enum('registration_type', ['self', 'agency', 'referral'])->default('self');
        $table->enum('registration_status', ['pending', 'approved', 'rejected'])->default('pending');
        $table->text('rejection_reason')->nullable();

        // Relationships
        $table->foreignId('referred_by')->nullable()->constrained('clients');
        $table->string('relationship')->nullable();
        $table->unsignedBigInteger('registered_by')->nullable();
        $table->unsignedBigInteger('agency_id')->nullable();
        $table->decimal('credit_score', 5, 2)->default(0.00);

        // Timestamps
        $table->timestamps();
        $table->timestamp('validated_at')->nullable();

        // Foreign keys
        $table->foreign('kyc_approved_by')->references('id')->on('users')->onDelete('set null');
        $table->foreign('registered_by')->references('id')->on('users')->onDelete('set null');
        $table->foreign('agency_id')->references('id')->on('agencies')->onDelete('set null');

        // Indexes
        $table->index('client_number');
        $table->index('phone');
        $table->index('kyc_status');
        $table->index('agency_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('clients');
    }
};
