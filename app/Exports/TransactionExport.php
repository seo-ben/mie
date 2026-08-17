<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class TransactionExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize
{
    protected $transactions;

    public function __construct($transactions)
    {
        $this->transactions = $transactions;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->transactions;
    }

    public function headings(): array
    {
        return [
            'Référence',
            'Date',
            'Client',
            'Compte',
            'Type',
            'Montant',
            'Méthode',
            'Statut',
            'Traité par'
        ];
    }

    public function map($transaction): array
    {
        return [
            $transaction->transaction_reference,
            $transaction->transaction_date->format('d/m/Y H:i'),
            $transaction->account->client->full_name ?? 'N/A',
            $transaction->account->account_number ?? 'N/A',
            $transaction->transaction_type,
            $transaction->amount,
            $transaction->payment_method,
            $transaction->status,
            $transaction->processedBy->full_name ?? 'N/A',
        ];
    }

    public function title(): string
    {
        return 'Journal des Transactions';
    }
}
