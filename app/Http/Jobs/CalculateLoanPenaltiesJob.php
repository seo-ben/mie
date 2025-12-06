<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\LoanPayment;
use App\Models\SystemParameter;
use Carbon\Carbon;

class CalculateLoanPenaltiesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $penaltyRate = SystemParameter::where('parameter_key', 'penalty_rate')
                                    ->value('parameter_value') ?? 0.05;

        $overduePayments = LoanPayment::where('status', 'pending')
                                    ->where('due_date', '<', Carbon::now())
                                    ->get();

        foreach ($overduePayments as $payment) {
            $daysOverdue = Carbon::now()->diffInDays($payment->due_date);
            $penaltyAmount = $payment->expected_amount * ($penaltyRate / 30) * $daysOverdue;

            $payment->update([
                'penalty_amount' => $penaltyAmount,
                'days_overdue' => $daysOverdue,
                'status' => 'overdue'
            ]);

            // Mettre à jour le prêt
            $payment->loan->increment('penalty_amount', $penaltyAmount);
            $payment->loan->update(['days_overdue' => max($payment->loan->days_overdue, $daysOverdue)]);
        }
    }
}