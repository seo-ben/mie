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
        // 1. Ajouter les champs de vérification et comptage à la table loans
        Schema::table('loans', function (Blueprint $table) {
            if (!Schema::hasColumn('loans', 'verifications')) {
                $table->json('verifications')->nullable()->after('approval_notes');
            }
            if (!Schema::hasColumn('loans', 'guarantors_count')) {
                $table->unsignedTinyInteger('guarantors_count')->default(0)->after('verifications');
            }
        });

        // 2. Créer la table des cautionnaires / garants
        if (!Schema::hasTable('loan_guarantors')) {
            Schema::create('loan_guarantors', function (Blueprint $table) {
                $table->id();
                $table->foreignId('loan_id')->constrained('loans')->onDelete('cascade');
                $table->unsignedTinyInteger('guarantor_order')->default(1); // 1, 2 ou 3
                $table->string('full_name');
                $table->string('phone');
                $table->string('id_card_number')->nullable(); // CNI, Passeport, etc.
                $table->string('relationship')->nullable(); // Parenté ou lien
                $table->string('profession')->nullable();
                $table->string('address')->nullable();
                $table->decimal('collateral_value', 15, 2)->nullable(); // Valeur estimée de l'engagement/gage
                $table->text('collateral_description')->nullable(); // Description du gage / caution
                $table->boolean('is_verified')->default(false); // Contrôlé et validé
                $table->text('verification_notes')->nullable();
                $table->timestamps();

                $table->index(['loan_id', 'guarantor_order']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_guarantors');

        Schema::table('loans', function (Blueprint $table) {
            if (Schema::hasColumn('loans', 'guarantors_count')) {
                $table->dropColumn('guarantors_count');
            }
            if (Schema::hasColumn('loans', 'verifications')) {
                $table->dropColumn('verifications');
            }
        });
    }
};
