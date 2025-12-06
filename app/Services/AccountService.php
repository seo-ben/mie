<?php
namespace App\Services;

use App\Models\Account;
use App\Models\SavingsAccount;
use App\Models\TontineAccount;
use App\Models\SystemParameter;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AccountService
{
    public function createAccount($clientId, $data)
    {
        DB::beginTransaction();
        try {
            // Récupérer les frais d'activation
            $activationFee = $this->getActivationFee($data['account_type'], $data['tontine_amount'] ?? null);

            // Créer le compte principal
            $account = Account::create([
                'client_id' => $clientId,
                'account_type' => $data['account_type'],
                'activation_fee' => $activationFee,
                'status' => 'pending_activation',
                'created_by' => auth()->id()
            ]);

            // Créer le compte spécialisé
            if ($data['account_type'] === 'savings') {
                SavingsAccount::create([
                    'account_id' => $account->id,
                    'interest_rate' => $this->getInterestRate('savings'),
                    'minimum_balance' => $this->getParameter('savings_minimum_balance', 1000)
                ]);
            } elseif ($data['account_type'] === 'tontine') {
                $this->createTontineAccount($account->id, $data);
            }

            DB::commit();
            return $account;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function createTontineAccount($accountId, $data)
    {
        $tontineAmount = $data['tontine_amount'];
        $cycleDuration = $data['cycle_duration_months'] ?? 12;
        
        TontineAccount::create([
            'account_id' => $accountId,
            'tontine_amount' => $tontineAmount,
            'cycle_duration_months' => $cycleDuration,
            'expected_monthly_payment' => $tontineAmount,
            'total_expected' => $tontineAmount * $cycleDuration,
            'cycle_start_date' => Carbon::now()->startOfMonth(),
            'cycle_end_date' => Carbon::now()->addMonths($cycleDuration)->endOfMonth()
        ]);
    }

    public function activateAccount($accountId, $paymentData)
    {
        $account = Account::findOrFail($accountId);
        
        if ($account->status !== 'pending_activation') {
            return ['success' => false, 'message' => 'Compte déjà activé ou invalide'];
        }

        DB::beginTransaction();
        try {
            // Vérifier le paiement selon la méthode
            $paymentValid = $this->verifyPayment($paymentData, $account->activation_fee);
            
            if (!$paymentValid['success']) {
                return $paymentValid;
            }

            // Activer le compte
            $account->update([
                'status' => 'active',
                'activation_fee_paid' => true,
                'activation_payment_method' => $paymentData['payment_method'],
                'activation_reference' => $paymentData['reference'] ?? null,
                'activated_at' => now(),
                'activated_by' => auth()->id()
            ]);

            // Enregistrer la transaction d'activation
            Transaction::create([
                'account_id' => $account->id,
                'transaction_type' => 'fee',
                'amount' => $account->activation_fee,
                'payment_method' => $paymentData['payment_method'],
                'payment_reference' => $paymentData['reference'] ?? null,
                'description' => 'Frais d\'activation du compte',
                'status' => 'completed',
                'balance_before' => 0,
                'balance_after' => 0,
                'processed_by' => auth()->id()
            ]);

            DB::commit();
            return ['success' => true, 'message' => 'Compte activé avec succès', 'data' => $account];
        } catch (\Exception $e) {
            DB::rollBack();
            return ['success' => false, 'message' => 'Erreur lors de l\'activation: ' . $e->getMessage()];
        }
    }

    public function getBalanceHistory($accountId, $days = 30)
    {
        $startDate = Carbon::now()->subDays($days)->startOfDay();
        
        $transactions = Transaction::where('account_id', $accountId)
            ->where('transaction_date', '>=', $startDate)
            ->where('status', 'completed')
            ->orderBy('transaction_date')
            ->select('transaction_date', 'balance_after')
            ->get();

        // Créer un tableau avec une entrée par jour
        $history = [];
        $currentBalance = $transactions->first()->balance_after ?? 0;
        
        for ($date = $startDate->copy(); $date <= Carbon::now(); $date->addDay()) {
            $dayTransactions = $transactions->where('transaction_date', '>=', $date->startOfDay())
                                           ->where('transaction_date', '<=', $date->endOfDay());
            
            if ($dayTransactions->isNotEmpty()) {
                $currentBalance = $dayTransactions->last()->balance_after;
            }
            
            $history[] = [
                'date' => $date->format('Y-m-d'),
                'balance' => $currentBalance
            ];
        }

        return $history;
    }

    private function getActivationFee($accountType, $tontineAmount = null)
    {
        if ($accountType === 'savings') {
            return $this->getParameter('savings_account_activation_fee', 7000);
        } elseif ($accountType === 'tontine') {
            return $tontineAmount; // Les frais d'activation tontine = montant de la tontine
        }
        
        return 0;
    }

    private function getInterestRate($accountType)
    {
        return $this->getParameter($accountType . '_interest_rate', 0.02);
    }

    private function getParameter($key, $default = null)
    {
        return SystemParameter::where('parameter_key', $key)->value('parameter_value') ?? $default;
    }

    private function verifyPayment($paymentData, $expectedAmount)
    {
        switch ($paymentData['payment_method']) {
            case 'mobile_money':
                return $this->verifyMobileMoneyPayment($paymentData, $expectedAmount);
            case 'bank_transfer':
                return $this->verifyBankTransfer($paymentData, $expectedAmount);
            case 'cash':
                return ['success' => true]; // Validation par l'agent
            default:
                return ['success' => false, 'message' => 'Méthode de paiement non supportée'];
        }
    }

    private function verifyMobileMoneyPayment($paymentData, $expectedAmount)
    {
        // Intégration avec les APIs Mobile Money (MTN, Orange, Moov)
        // Pour le moment, simulation
        return ['success' => true];
    }

    private function verifyBankTransfer($paymentData, $expectedAmount)
    {
        // Vérification des virements bancaires
        // Pour le moment, simulation
        return ['success' => true];
    }
}