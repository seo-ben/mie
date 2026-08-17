<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu Transaction #{{ $transaction->transaction_reference }}</title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; color: #000; margin: 40px; font-size: 14px; line-height: 1.5; }
        .receipt { max-width: 400px; margin: 0 auto; border: 1px dotted #ccc; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px dashed #000; padding-bottom: 10px; }
        .institution { font-size: 18px; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; }
        .branch { font-size: 12px; margin-bottom: 5px; }
        .title { font-weight: bold; text-decoration: underline; margin-bottom: 15px; text-transform: uppercase; }
        
        .row { display: flex; justify-content: space-between; margin-bottom: 8px; }
        .label { font-weight: normal; text-transform: uppercase; font-size: 12px; }
        .value { font-weight: bold; }
        
        .amount-box { border-top: 2px dashed #000; border-bottom: 2px dashed #000; padding: 10px 0; margin: 15px 0; text-align: center; }
        .amount { font-size: 20px; font-weight: bold; }
        
        .footer { margin-top: 30px; text-align: center; font-size: 12px; border-top: 1px dashed #000; padding-top: 10px; }
        .signature { margin-top: 50px; display: flex; justify-content: space-between; }
        .sig-box { width: 45%; text-align: center; border-top: 1px solid #000; padding-top: 5px; font-size: 10px; font-weight: bold; text-transform: uppercase; }
        
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
            .receipt { border: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px; text-align: center;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #000; color: #fff; cursor: pointer; border: none; font-weight: bold;">[ IMPRIMER LE REÇU ]</button>
    </div>

    <div class="receipt">
        <div class="header">
            <div class="institution">MIE YAYRA - MICROFINANCE</div>
            <div class="branch">Agence : {{ $transaction->account->agency->name ?? 'Siège Central' }}</div>
            <div class="branch">TEL: +228 00 00 00 00</div>
            <br>
            <div class="title">Reçu d'Opération</div>
            <div style="font-size: 10px;">Réf : {{ $transaction->transaction_reference }}</div>
        </div>

        <div class="row">
            <span class="label">Date :</span>
            <span class="value">{{ $transaction->created_at->format('d/m/Y H:i') }}</span>
        </div>
        <div class="row">
            <span class="label">Client :</span>
            <span class="value">{{ $transaction->account->client->full_name }}</span>
        </div>
        <div class="row">
            <span class="label">N° Client :</span>
            <span class="value">{{ $transaction->account->client->client_number }}</span>
        </div>
        <div class="row">
            <span class="label">N° Compte :</span>
            <span class="value">{{ $transaction->account->account_number }}</span>
        </div>
        <div class="row">
            <span class="label">Type Compte :</span>
            <span class="value">{{ strtoupper($transaction->account->account_type) }}</span>
        </div>
        
        <br>
        <div class="row">
            <span class="label">Opération :</span>
            <span class="value">{{ strtoupper($transaction->transaction_type) }}</span>
        </div>

        <div class="amount-box">
            <span class="label" style="display: block; margin-bottom: 5px;">Montant de l'opération</span>
            <span class="amount">{{ number_format($transaction->amount, 0, ',', ' ') }} CFA</span>
        </div>

        <div class="row">
            <span class="label">Ancien Solde :</span>
            <span class="value">{{ number_format($transaction->balance_before, 0, ',', ' ') }} CFA</span>
        </div>
        <div class="row">
            <span class="label">Nouveau Solde :</span>
            <span class="value" style="font-size: 16px;">{{ number_format($transaction->balance_after, 0, ',', ' ') }} CFA</span>
        </div>

        <div class="footer">
            <div>Opéré par : {{ $transaction->processedBy->full_name ?? 'SYSTÈME' }}</div>
            <br>
            <div style="font-style: italic;">Merci de votre confiance !</div>
            <div style="font-size: 9px; margin-top: 5px;">Document généré le {{ now()->format('d/m/Y H:i:s') }}</div>
        </div>

        <div class="signature">
            <div class="sig-box">Signature Client</div>
            <div class="sig-box">Signature Agent</div>
        </div>
    </div>

    <script>
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
