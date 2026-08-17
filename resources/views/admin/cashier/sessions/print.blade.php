<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>RAPPORT DE CAISSE - SESSION #{{ $session->id }}</title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; color: #1a1a1a; margin: 40px; font-size: 13px; line-height: 1.4; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 15px; }
        .institution-name { font-size: 20px; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; }
        .report-title { font-size: 16px; font-weight: bold; text-decoration: underline; margin-bottom: 10px; }
        
        .meta-info { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .meta-box { width: 48%; }
        .label { font-weight: bold; text-transform: uppercase; font-size: 11px; display: inline-block; width: 120px; }
        
        .stats-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .stats-table th, .stats-table td { border: 1px solid #000; padding: 8px; text-align: left; }
        .stats-table th { background-color: #f0f0f0; }
        .amount { text-align: right; font-weight: bold; }
        
        .transactions-table { width: 100%; border-collapse: collapse; }
        .transactions-table th { border-bottom: 2px solid #000; padding: 10px 5px; text-align: left; font-size: 11px; text-transform: uppercase; }
        .transactions-table td { border-bottom: 1px solid #eee; padding: 8px 5px; font-size: 11px; }
        
        .footer { margin-top: 50px; }
        .signatures { display: flex; justify-content: space-between; margin-top: 60px; }
        .signature-box { width: 200px; text-align: center; border-top: 1px solid #000; padding-top: 10px; font-weight: bold; text-transform: uppercase; font-size: 10px; }
        
        @media print {
            .no-print { display: none; }
            body { margin: 20px; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #000; color: #fff; cursor: pointer; border: none; font-weight: bold;">[ IMPRIMER LE RAPPORT ]</button>
    </div>

    <div class="header">
        <div class="institution-name">MIE YAYRA - MICROFINANCE</div>
        <div style="font-size: 10px; margin-bottom: 10px;">Agence : {{ $session->agency->name }} | Code : {{ $session->agency->code }}</div>
        <div class="report-title">RAPPORT JOURNALIER DE CAISSE (SOULIGNEMENT)</div>
        <div>Session #{{ $session->id }} | Date : {{ $session->opened_at->format('d/m/Y') }}</div>
    </div>

    <div class="meta-info">
        <div class="meta-box">
            <div><span class="label">Caissier :</span> {{ $session->user->full_name }}</div>
            <div><span class="label">Ouverture :</span> {{ $session->opened_at->format('H:i:s') }}</div>
            <div><span class="label">Clôture :</span> {{ $session->closed_at ? $session->closed_at->format('H:i:s') : 'EN COURS' }}</div>
        </div>
        <div class="meta-box" style="text-align: right;">
            <div><span class="label">Imprimé le :</span> {{ now()->format('d/m/Y H:i') }}</div>
            <div><span class="label">Statut :</span> {{ strtoupper($session->status) }}</div>
        </div>
    </div>

    <h3 style="text-transform: uppercase; font-size: 12px; border-left: 4px solid #000; padding-left: 10px;">Récapitulatif des Flux</h3>
    <table class="stats-table">
        <tr>
            <th>Libellé de l'opération</th>
            <th style="text-align: center;">Nombre</th>
            <th style="text-align: right;">Crédit (+)</th>
            <th style="text-align: right;">Débit (-)</th>
        </tr>
        <tr>
            <td>Solde Initial (Report)</td>
            <td style="text-align: center;">-</td>
            <td class="amount">{{ number_format($session->opening_balance, 0, ',', ' ') }}</td>
            <td class="amount">0</td>
        </tr>
        <tr>
            <td>Dépôts & Versements</td>
            <td style="text-align: center;">{{ $stats->count_deposits }}</td>
            <td class="amount">{{ number_format($stats->total_in, 0, ',', ' ') }}</td>
            <td class="amount">-</td>
        </tr>
        <tr>
            <td>Retraits & Décaissements</td>
            <td style="text-align: center;">{{ $stats->count_withdrawals }}</td>
            <td class="amount">-</td>
            <td class="amount">{{ number_format($stats->total_out, 0, ',', ' ') }}</td>
        </tr>
        <tr style="background-color: #f9f9f9; font-weight: bold;">
            <td colspan="2">TOTAUX GLOBAUX</td>
            <td class="amount">{{ number_format($session->opening_balance + $stats->total_in, 0, ',', ' ') }}</td>
            <td class="amount">{{ number_format($stats->total_out, 0, ',', ' ') }}</td>
        </tr>
        <tr style="background-color: #eee; font-size: 14px;">
            <td colspan="2">SOLDE THÉORIQUE FINAL</td>
            <td colspan="2" style="text-align: right; font-weight: bold;">
                {{ number_format($session->opening_balance + $stats->total_in - $stats->total_out, 0, ',', ' ') }} CFA
            </td>
        </tr>
    </table>

    <h3 style="text-transform: uppercase; font-size: 12px; border-left: 4px solid #000; padding-left: 10px;">Détail des Transactions ({{ $session->transactions->count() }})</h3>
    <table class="transactions-table">
        <thead>
            <tr>
                <th>Heure</th>
                <th>Référence</th>
                <th>Compte / Client</th>
                <th>Type</th>
                <th style="text-align: right;">Montant</th>
            </tr>
        </thead>
        <tbody>
            @foreach($session->transactions as $tx)
            <tr>
                <td>{{ $tx->created_at->format('H:i') }}</td>
                <td>{{ $tx->transaction_reference }}</td>
                <td>{{ $tx->account->account_number ?? 'N/A' }} - {{ $tx->account->client->full_name ?? 'INTERNE' }}</td>
                <td>{{ strtoupper($tx->transaction_type) }}</td>
                <td style="text-align: right; font-weight: bold;">{{ number_format($tx->amount, 0, ',', ' ') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <div style="font-style: italic; font-size: 10px; margin-bottom: 20px;">
            Observations : {{ $session->notes ?? 'Aucune observation particulière.' }}
        </div>
        
        <div class="signatures">
            <div class="signature-box">Signature Caissier</div>
            <div class="signature-box">Contrôle Interne</div>
            <div class="signature-box">Signature Chef d'Agence</div>
        </div>
    </div>

    <script>
        // Auto-print option commented out to allow viewing before print
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
