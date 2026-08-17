<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Agency;
use App\Models\User;

class CashierUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. S'assurer qu'au moins une agence existe
        $agency = Agency::first();
        if (!$agency) {
            $agency = Agency::create([
                'name' => 'Agence Centrale de Lomé',
                'code' => 'ACL-001',
                'address' => 'Boulevard du 13 Janvier, Lomé',
                'phone' => '+228 22 21 00 00',
                'is_active' => true
            ]);
        }

        // 2. Création de l'utilisateur Caissier
        $cashierEmail = 'caissier@mie.tg';
        
        // Vérifier si l'utilisateur existe déjà
        $user = User::where('email', $cashierEmail)->first();
        
        if (!$user) {
            User::create([
                'username' => 'caissier01',
                'email' => $cashierEmail,
                'password' => Hash::make('password'),
                'role' => 'caissier',
                'first_name' => 'Kodjo',
                'last_name' => 'CAISSIER',
                'phone' => '+228 90 12 34 56',
                'agency_id' => $agency->id,
                'is_active' => true,
            ]);

            $this->command->info('✅ Utilisateur Caissier créé avec succès !');
            $this->command->info('📧 Email : ' . $cashierEmail);
            $this->command->info('🔑 Mot de passe : password');
        } else {
            // Mise à jour du rôle si l'utilisateur existe déjà
            $user->update(['role' => 'caissier']);
            $this->command->warn('⚠️ L\'utilisateur existe déjà. Son rôle a été mis à jour vers "caissier".');
        }
    }
}
