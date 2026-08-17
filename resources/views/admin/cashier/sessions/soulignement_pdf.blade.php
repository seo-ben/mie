<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Soulignement de Caisse - Session #{{ $session->id }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 11pt; color: #333; margin: 0; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #1E40AF; padding-bottom: 10px; }
        .title { font-size: 18pt; font-weight: bold; color: #1E40AF; text-transform: uppercase; margin-bottom: 5px; }
        .subtitle { font-size: 10pt; color: #666; }
        
        .session-info { margin-bottom: 25px; background: #F3F4F6; padding: 15px; border-radius: 5px; }
        .info-grid { width: 100%; border-collapse: collapse; }
        .info-label { font-weight: bold; width: 30%; color: #374151; }
        
        .summary-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .summary-table th, .summary-table td { padding: 10px; border: 1px solid #D1D5DB; text-align: left; }
        .summary-table th { background-color: #F9FAFB; font-weight: bold; }
        
        .transactions-title { font-size: 14pt; font-weight: bold; margin-bottom: 10px; color: #1E40AF; border-left: 4px solid #1E40AF; padding-left: 10px; }
        .transactions-table { width: 100%; border-collapse: collapse; font-size: 9pt; }
        .transactions-table th { background-color: #1E40AF; color: white; padding: 8px; text-align: left; }
        .transactions-table td { padding: 8px; border-bottom: 1px solid #E5E7EB; }
        .text-right { text-align: right; }
        
        .footer { margin-top: 50px; }
        .signatures { width: 100%; margin-top: 40px; }
        .signature-box { width: 33%; text-align: center; vertical-align: top; }
        .signature-line { margin-top: 60px; border-top: 1px solid #333; width: 80%; margin-left: auto; margin-right: auto; }
        
        .amount { font-family: 'Courier', monospace; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Soulignement de Caisse</div>
        <div class="subtitle">Microfinance Institutionnelle - MIE YAYRA</div>
        <div style="margin-top: 10px; font-weight: bold;">Rapport de Clôture de Session #{{ $session->id }}</div>
    </div>

    <div class="session-info">
        <table class="info-grid">
            <tr>
                <td class="info-label">Période de Session :</td>
                <td>Du <strong>{{ $session->opened_at->format('d/m/Y \à H:i') }}</strong> au <strong>{{ $session->closed_at ? $session->closed_at->format('d/m/Y \à H:i') : 'En cours' }}</strong></td>
            </tr>
            <tr>
                <td class="info-label">Agent (Caissier) :</td>
                <td>{{ $session->user->full_name }} ({{ $session->user->username }})</td>
            </tr>
            <tr>
                <td class="info-label">Agence :</td>
                <td>{{ $session->agency->name }}</td>
            </tr>
        </table>
    </div>

    <table class="summary-table">
        <thead>
            <tr>
                <th colspan="2" style="text-align: center; background-color: #E5E7EB;">RÉSUMÉ FINANCIER</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>SOLDE INITIAL (Report)</td>
                <td class="text-right amount">{{ number_format($session->opening_balance, 0, ',', ' ') }} FCFA</td>
            </tr>
            <tr>
                <td>TOTAL ENCAISSEMENTS (+)</td>
                <td class="text-right amount text-success" style="color: #059669;">+ {{ number_format($session->total_deposits, 0, ',', ' ') }} FCFA</td>
            </tr>
            <tr>
                <td>TOTAL DÉCAISSEMENTS (-)</td>
                <td class="text-right amount text-danger" style="color: #DC2626;">- {{ number_format($session->total_withdrawals, 0, ',', ' ') }} FCFA</td>
            </tr>
            <tr style="background-color: #F3F4F6; font-size: 12pt;">
                <td style="font-weight: bold;">SOLDE FINAL THÉORIQUE</td>
                <td class="text-right amount" style="font-weight: bold;">{{ number_format($session->opening_balance + $session->total_deposits - $session->total_withdrawals, 0, ',', ' ') }} FCFA</td>
            </tr>
            @if($session->closed_at)
            <tr>
                <td>SOLDE PHYSIQUE COMPTÉ</td>
                <td class="text-right amount">{{ number_format($session->closing_balance, 0, ',', ' ') }} FCFA</td>
            </tr>
            <tr>
                <td>ÉCART DE CAISSE</td>
                @php $diff = $session->closing_balance - $session->expected_closing_balance; @endphp
                <td class="text-right amount" style="color: {{ $diff >= 0 ? '#059669' : '#DC2626' }};">
                    {{ $diff > 0 ? '+' : '' }}{{ number_format($diff, 0, ',', ' ') }} FCFA
                </td>
            </tr>
            @endif
        </tbody>
    </table>

    <div class="transactions-title">Détail des Opérations (Journal)</div>
    <table class="transactions-table">
        <thead>
            <tr>
                <th>Date/Heure</th>
                <th>Réf. Transaction</th>
                <th>Compte</th>
                <th>Client</th>
                <th>Opération</th>
                <th class="text-right">Montant</th>
            </tr>
        </thead>
        <tbody>
            @forelse($session->transactions as $transaction)
            <tr>
                <td>{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $transaction->transaction_reference }}</td>
                <td>{{ $transaction->account->account_number }}</td>
                <td>{{ $transaction->account->client->full_name }}</td>
                <td>{{ ucfirst($transaction->transaction_type) }}</td>
                <td class="text-right amount">{{ number_format($transaction->amount, 0, ',', ' ') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center; padding: 20px;">Aucune opération enregistrée.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($session->notes)
    <div style="margin-top: 20px; font-size: 9pt;">
        <strong>Notes / Observations :</strong><br>
        {{ $session->notes }}
    </div>
    @endif

    <div class="footer">
        <table class="signatures">
            <tr>
                <td class="signature-box">
                    <strong>Le Caissier</strong>
                    <div class="signature-line"></div>
                    <small>{{ $session->user->full_name }}</small>
                </td>
                <td class="signature-box">
                    <strong>Le Chef d'Agence</strong>
                    <div class="signature-line"></div>
                </td>
                <td class="signature-box">
                    <strong>Audit Interne</strong>
                    <div class="signature-line"></div>
                </td>
            </tr>
        </table>
        <div style="margin-top: 40px; text-align: center; font-size: 8pt; color: #999;">
            Document généré le {{ now()->format('d/m/Y à H:i:s') }} par le système de pilotage MIE YAYRA.
        </div>
    </div>
</body>
</html>
