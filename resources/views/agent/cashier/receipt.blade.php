<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reçu Officiel - {{ $transaction->transaction_reference }}</title>
    <style>
        html { background: #f0f2f5; } /* Arrière-plan pour isoler le ticket sur écran */
        @media print {
            html { background: transparent; }
            body { box-shadow: none; margin: 0; }
        }
        @page { size: 80mm auto; margin: 0; }
        body {
            font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
            background: #fff;
            color: #000;
            margin: 0 auto; /* Centrage automatique */
            padding: 5mm;
            width: 70mm; /* Largeur utile du ticket */
            line-height: 1.3;
            font-size: 11px;
            position: relative;
            box-shadow: 0 0 15px rgba(0,0,0,0.1); /* Effet papier pour le visuel */
        }

        /* FILIGRANE / WATERMARK */
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 40px;
            font-weight: 900;
            color: rgba(0, 0, 0, 0.05);
            white-space: nowrap;
            z-index: -1;
            text-transform: uppercase;
            letter-spacing: 5px;
            pointer-events: none;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px double #000;
        }

        .header .brand { font-size: 18px; font-weight: 900; margin: 0; letter-spacing: 1px; }
        .header .tagline { font-size: 9px; margin-top: 2px; text-transform: uppercase; font-weight: 600; }
        .header .agency-info { margin-top: 8px; font-size: 10px; font-weight: 500; }

        .receipt-title {
            text-align: center;
            font-weight: 800;
            margin: 10px 0;
            font-size: 12px;
            background: #000;
            color: #fff;
            padding: 3px 0;
            text-transform: uppercase;
        }

        .info-grid { margin-bottom: 15px; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 4px; border-bottom: 1px solid #eee; }
        .info-label { font-weight: 700; color: #444; }
        .info-value { font-weight: 600; text-align: right; }

        .amount-section {
            text-align: center;
            margin: 15px 0;
            padding: 10px;
            border: 1.5px solid #000;
            border-radius: 4px;
        }
        .amount-section .label { font-weight: 800; text-transform: uppercase; font-size: 10px; display: block; margin-bottom: 5px;}
        .amount-section .value { font-size: 22px; font-weight: 900; letter-spacing: -1px; }

        .fees-row { font-style: italic; color: #666; font-size: 10px; padding-top: 4px; }

        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            padding-top: 5px;
            text-align: center;
            font-size: 9px;
            font-weight: 700;
        }
        .signature-box { width: 45%; }
        .signature-line { margin-top: 30px; border-top: 1px solid #000; }

        .footer {
            text-align: center;
            margin-top: 25px;
            font-size: 9px;
            padding-top: 10px;
            border-top: 1px dashed #666;
            color: #444;
        }

        .no-print {
            position: fixed;
            bottom: 20px;
            left: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
            z-index: 1000;
        }
        .btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .btn-print { background: #000; color: #fff; }
        .btn-close { background: #eee; color: #333; }

        @media print {
            .no-print { display: none !important; }
            body { padding: 0; width: 80mm; }
        }
    </style>
</head>
<body>

    <!-- Filigrane Professionnelle -->
    <div class="watermark">YAYRA MIE</div>
    <div class="watermark" style="top: 25%;">YAYRA MIE</div>
    <div class="watermark" style="top: 75%;">YAYRA MIE</div>

    <div class="header">
        <h1 class="brand">MIE YAYRA</h1>
        <div class="tagline">Confiance • Innovation • Excellence</div>
        <div class="agency-info">
            {{ strtoupper(auth()->user()->agency->name) }}<br>
            {{ auth()->user()->agency->address ?? 'Lomé, Togo' }}<br>
            Tél: {{ auth()->user()->agency->phone ?? '+228 22 12 34 56' }}
        </div>
    </div>

    <div class="receipt-title">
        @php
            $txType = $transaction->transaction_type;
            $accType = $transaction->account ? $transaction->account->account_type : null;
        @endphp
        @switch($txType)
            @case('loan_disbursement') 
            @case('payout')
                DÉCAISSEMENT DE PRÊT
                @break
            @case('loan_repayment')
                REMBOURSEMENT DE PRÊT
                @break
            @case('deposit')
                @if($accType === 'tontine') COTISATION TONTINE @else ÉPARGNE DÉPÔT @endif
                @break
            @case('withdrawal')
                @if($accType === 'tontine') RETRAIT TONTINE @else ÉPARGNE RETRAIT @endif
                @break
            @default
                {{ strtoupper(str_replace('_', ' ', $txType)) }}
        @endswitch
    </div>

    <div class="info-grid">
        <div class="info-row">
            <span class="info-label">Référence :</span>
            <span class="info-value">{{ $transaction->transaction_reference }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Date/Heure :</span>
            <span class="info-value">{{ $transaction->transaction_date->format('d/m/Y H:i') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Titulaire :</span>
            <span class="info-value">{{ strtoupper($clientName) }}</span>
        </div>
        @if($transaction->account)
        <div class="info-row">
            <span class="info-label">Compte :</span>
            <span class="info-value">{{ $transaction->account->account_number }}</span>
        </div>
        @endif
        @if($transaction->payment_method)
        <div class="info-row">
            <span class="info-label">Opération :</span>
            <span class="info-value">{{ strtoupper($transaction->payment_method) }}</span>
        </div>
        @endif
    </div>

    <div class="amount-section">
        <span class="label">Montant de la transaction</span>
        <div class="value">{{ number_format($transaction->amount, 0, ',', ' ') }} FCFA</div>
        
        @if($transaction->fee_amount > 0)
        <div class="fees-row">
            Inclu frais/commissions : {{ number_format($transaction->fee_amount, 0, ',', ' ') }} FCFA
        </div>
        @endif
    </div>

    @if($transaction->account)
    <div class="info-grid">
        <div class="info-row" style="border-bottom: 2px solid #000; padding: 4px 0;">
            <span class="info-label">SOLDE DISPONIBLE :</span>
            <span class="info-value">{{ number_format($transaction->balance_after, 0, ',', ' ') }} FCFA</span>
        </div>
    </div>
    @endif

    <div class="signatures">
        <div class="signature-box">
            <span>Signature Client</span>
            <div class="signature-line"></div>
        </div>
        <div class="signature-box">
            <span>{{ strtoupper(auth()->user()->last_name) }} (Le Caissier)</span>
            <div class="signature-line"></div>
        </div>
    </div>

    <div class="footer">
        <div style="font-weight: 900; margin-bottom: 5px;">* {{ $transaction->transaction_reference }} *</div>
        <p>
            MIE YAYRA - Microfinance d'Innovation<br>
            Merci de votre confiance et à bientôt !<br>
            <span style="font-size: 8px; color: #888;">Imprimé par le Terminal Caissier {{ now()->format('H:i:s') }}</span>
        </p>
    </div>

    <div class="no-print">
        <button onclick="window.close()" class="btn btn-close">FERMER</button>
        <button onclick="window.print()" class="btn btn-print">IMPRIMER LE REÇU</button>
    </div>

    <script>
        window.onload = function() {
            // Auto-print optionnel
            // window.print();
        };
    </script>
</body>
</html>
