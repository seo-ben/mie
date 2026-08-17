<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CashierSession;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ManageCashierSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:manage-sessions {action? : open or close}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automate cashier session opening and closing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        $now = Carbon::now();

        if ($action === 'open' || ($action === null && $now->format('H:i') === '07:30')) {
            $this->openSessions();
        }

        if ($action === 'close' || ($action === null && $now->format('H:i') === '18:00')) {
            $this->closeSessions();
        }

        return 0;
    }

    /**
     * Auto-open sessions for all active cashier users
     */
    private function openSessions()
    {
        $cashiers = User::whereIn('role', ['agent_agence', 'caissier'])
            ->where('is_active', true)
            ->get();

        foreach ($cashiers as $cashier) {
            // Check if user already has an open session
            $exists = CashierSession::where('user_id', $cashier->id)
                ->where('status', 'open')
                ->exists();

            if (!$exists) {
                // Determine opening balance (e.g., previous day closing balance or 0)
                $previousSession = CashierSession::where('user_id', $cashier->id)
                    ->latest()
                    ->first();
                
                $openingBalance = $previousSession ? $previousSession->closing_balance : 0;

                CashierSession::create([
                    'user_id' => $cashier->id,
                    'agency_id' => $cashier->agency_id,
                    'opened_at' => now(),
                    'opening_balance' => $openingBalance ?? 0,
                    'status' => 'open',
                ]);
                
                $this->info("Opened session for cashier: {$cashier->username}");
            }
        }
    }

    /**
     * Auto-close all open sessions
     */
    private function closeSessions()
    {
        $openSessions = CashierSession::where('status', 'open')->get();

        foreach ($openSessions as $session) {
            // Calculate totals
            $totals = Transaction::where('cashier_session_id', $session->id)
                ->where('status', 'completed')
                ->select(
                    DB::raw('SUM(CASE WHEN transaction_type = "deposit" THEN amount ELSE 0 END) as total_deposits'),
                    DB::raw('SUM(CASE WHEN transaction_type = "withdrawal" THEN amount ELSE 0 END) as total_withdrawals')
                )->first();

            $expectedBalance = $session->opening_balance + ($totals->total_deposits ?? 0) - ($totals->total_withdrawals ?? 0);

            $session->update([
                'closed_at' => now(),
                'closing_balance' => $expectedBalance,
                'expected_closing_balance' => $expectedBalance,
                'total_deposits' => $totals->total_deposits ?? 0,
                'total_withdrawals' => $totals->total_withdrawals ?? 0,
                'status' => 'closed',
                'notes' => 'Fermeture automatique par le système',
            ]);

            $this->info("Closed session for ID: {$session->id}");
        }
    }
}
