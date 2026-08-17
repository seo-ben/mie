<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artefact de Transaction #{{ $transaction->transaction_reference }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        @media print {
            .no-print { display: none; }
            body { background: white; }
            .receipt-container { shadow: none; border: 1px solid #e2e8f0; }
        }
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full receipt-container bg-white shadow-2xl rounded-[2.5rem] overflow-hidden border border-slate-100 relative">
        <!-- Blue Accent Top -->
        <div class="h-3 bg-blue-600 w-full"></div>
        
        <div class="px-8 pt-10 pb-8">
            <!-- Header Section -->
            <div class="flex justify-between items-start mb-10">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 leading-tight tracking-tighter uppercase">Reçu de<br>Transaction</h1>
                    <p class="text-[10px] font-bold text-blue-600 mt-1 uppercase tracking-widest">Preuve de Transaction Sécurisée</p>
                </div>
                <div class="bg-slate-50 p-3 rounded-2xl border border-slate-100">
                    {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(60)->generate($qrData) !!}
                </div>
            </div>

            <!-- Transaction Reference -->
            <div class="mb-10 text-center py-6 bg-slate-50 rounded-[2rem] border border-slate-100">
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 text-nowrap">Référence d'Authentification</p>
                <p class="font-mono text-lg font-bold text-slate-900 tracking-widest uppercase">{{ $transaction->transaction_reference }}</p>
            </div>

            <!-- Main Details -->
            <div class="space-y-6 mb-10">
                <div class="flex justify-between items-end border-b border-slate-50 pb-4">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Titulaire du Compte</span>
                    <span class="text-sm font-black text-slate-900 text-right">{{ $transaction->account->client->full_name }}</span>
                </div>
                <div class="flex justify-between items-end border-b border-slate-50 pb-4">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Type d'Opération</span>
                    <span class="text-sm font-black text-blue-600 uppercase">
                        @if($transaction->transaction_type == 'withdrawal') RETRAIT
                        @elseif($transaction->transaction_type == 'deposit') DÉPÔT
                        @else {{ strtoupper(str_replace('_', ' ', $transaction->transaction_type)) }}
                        @endif
                    </span>
                </div>
                <div class="flex justify-between items-end border-b border-slate-50 pb-4">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Date et Heure</span>
                    <span class="text-sm font-black text-slate-900">{{ $transaction->transaction_date->format('d/m/Y - H:i') }}</span>
                </div>
                <div class="flex justify-between items-end border-b border-slate-50 pb-4">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Compte Source</span>
                    <span class="font-mono text-xs font-bold text-slate-700">{{ $transaction->account->account_number }}</span>
                </div>
            </div>

            <!-- Amount Section -->
            <div class="mb-10 p-8 bg-slate-900 rounded-[2.5rem] text-center shadow-xl shadow-slate-200">
                <p class="text-[10px] font-black text-white/40 uppercase tracking-[0.3em] mb-3">Montant de la Transaction</p>
                <div class="text-3xl font-black text-white tracking-tighter">
                    {{ number_format($transaction->amount, 0, ',', ' ') }}
                    <span class="text-sm text-white/60 font-medium ml-1">XOF</span>
                </div>
                @if($transaction->fee_amount > 0)
                    <div class="mt-3 text-[10px] font-bold text-rose-400/80 uppercase">
                        Frais de Service : {{ number_format($transaction->fee_amount, 0, ',', ' ') }} XOF
                    </div>
                @endif
            </div>

            <!-- Security Check -->
            <div class="flex items-center gap-3 p-4 bg-emerald-50 rounded-2xl border border-emerald-100 mb-8">
                <div class="w-8 h-8 rounded-full bg-emerald-500 flex items-center justify-center text-white text-xs">
                    <i class="fas fa-shield-check"></i>
                </div>
                <div>
                    <p class="text-[9px] font-black text-emerald-800 uppercase tracking-tight">Vérification de Conformité</p>
                    <p class="text-[10px] font-bold text-emerald-600 uppercase">Certifié par le système</p>
                </div>
            </div>

            <!-- Footer Message -->
            <div class="text-center">
                <p class="text-[8px] font-bold text-slate-400 uppercase leading-loose italic">
                    Ce reçu est généré électroniquement par le système de gestion.<br>
                    Toute altération rend ce document nul.
                </p>
            </div>
        </div>

        <!-- Buttons -->
        <div class="px-8 pb-10 flex gap-3 no-print">
            <button onclick="window.print()" class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-black text-[10px] uppercase py-4 rounded-2xl transition border border-slate-200">
                <i class="fas fa-print mr-2"></i> Imprimer
            </button>
            <button onclick="window.close()" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-black text-[10px] uppercase py-4 rounded-2xl transition shadow-lg shadow-blue-500/25">
                Fermer
            </button>
        </div>

        <div class="absolute bottom-0 left-0 w-full h-1 bg-blue-600"></div>
    </div>

    <!-- FontAwesome -->
    <script src="https://kit.fontawesome.com/your-code.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</body>
</html>
