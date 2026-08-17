<div class="transaction-detail-content">
    <!-- En-tête -->
    <div class="p-6 text-white bg-gradient-to-r from-blue-500 to-blue-600">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Détails Transaction</h3>
            <span class="px-3 py-1 text-xs bg-white rounded-full bg-opacity-20">
                {{ $transaction->transaction_reference }}
            </span>
        </div>

        <div class="py-4 text-center">
            <p class="mb-2 text-sm opacity-90">Montant</p>
            <p class="text-4xl font-bold">
                {{ number_format($transaction->amount, 0, ',', ' ') }} <span class="text-2xl">FCFA</span>
            </p>
        </div>
    </div>

    <div class="p-6 space-y-6">
        <!-- Statut -->
        <div>
            <label class="text-xs font-medium tracking-wider text-gray-500 uppercase">Statut</label>
            <div class="mt-2">
                @switch($transaction->status)
                    @case('completed')
                        <span class="inline-flex items-center px-4 py-2 text-sm font-medium text-green-800 bg-green-100 rounded-lg">
                            <i class="mr-2 fas fa-check-circle"></i>Transaction Complétée
                        </span>
                        @break
                    @case('pending')
                        <span class="inline-flex items-center px-4 py-2 text-sm font-medium text-yellow-800 bg-yellow-100 rounded-lg">
                            <i class="mr-2 fas fa-clock"></i>En Attente de Validation
                        </span>
                        @break
                    @case('failed')
                        <span class="inline-flex items-center px-4 py-2 text-sm font-medium text-red-800 bg-red-100 rounded-lg">
                            <i class="mr-2 fas fa-times-circle"></i>Transaction Échouée
                        </span>
                        @break
                    @case('rejected')
                        <span class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-800 bg-gray-100 rounded-lg">
                            <i class="mr-2 fas fa-ban"></i>Transaction Rejetée
                        </span>
                        @break
                @endswitch
            </div>
        </div>

        <!-- Informations Client -->
        <div class="pt-4 border-t border-gray-200">
            <label class="block mb-3 text-xs font-medium tracking-wider text-gray-500 uppercase">Client</label>
            <div class="p-4 rounded-lg bg-gray-50">
                <a href="{{ route('admin.clients.show', $transaction->account->client_id) }}"
                   class="text-lg font-semibold text-blue-600 hover:text-blue-800">
                    {{ $transaction->account->client->first_name }} {{ $transaction->account->client->last_name }}
                </a>
                <div class="mt-2 space-y-1 text-sm text-gray-600">
                    <div class="flex items-center">
                        <i class="w-5 mr-2 fas fa-id-card"></i>
                        <span>{{ $transaction->account->client->client_number }}</span>
                    </div>
                    <div class="flex items-center">
                        <i class="w-5 mr-2 fas fa-phone"></i>
                        <span>{{ $transaction->account->client->phone }}</span>
                    </div>
                    @if($transaction->account->client->email)
                    <div class="flex items-center">
                        <i class="w-5 mr-2 fas fa-envelope"></i>
                        <span>{{ $transaction->account->client->email }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Informations Compte -->
        <div class="pt-4 border-t border-gray-200">
            <label class="block mb-3 text-xs font-medium tracking-wider text-gray-500 uppercase">Compte</label>
            <div class="p-4 rounded-lg bg-gray-50">
                <a href="{{ route('admin.accounts.show', $transaction->account_id) }}"
                   class="font-semibold text-blue-600 hover:text-blue-800">
                    {{ $transaction->account->account_number }}
                </a>
                <div class="mt-2 space-y-1 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Type:</span>
                        <span class="font-medium text-gray-900 capitalize">
                            {{ $transaction->account->account_type === 'savings' ? 'Épargne' : 'Tontine' }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Solde avant:</span>
                        <span class="font-semibold text-gray-900">{{ number_format($transaction->balance_before, 0, ',', ' ') }} FCFA</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Frais:</span>
                        <span class="font-semibold text-gray-900">{{ number_format($transaction->fee_amount, 0, ',', ' ') }} FCFA</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Solde après:</span>
                        <span class="font-semibold text-green-600">{{ number_format($transaction->balance_after, 0, ',', ' ') }} FCFA</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Détails Transaction -->
        <div class="pt-4 border-t border-gray-200">
            <label class="block mb-3 text-xs font-medium tracking-wider text-gray-500 uppercase">Détails</label>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600">Type:</span>
                    <span class="font-medium text-gray-900 capitalize">
                        {{ str_replace('_', ' ', $transaction->transaction_type) }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Méthode de paiement:</span>
                    <span class="font-medium text-gray-900">
                        @switch($transaction->payment_method)
                            @case('cash')
                                <i class="mr-1 fas fa-money-bill-wave"></i>Espèces
                                @break
                            @case('mobile_money')
                                <i class="mr-1 fas fa-mobile-alt"></i>Mobile Money
                                @if($transaction->mobile_money_operator)
                                    ({{ strtoupper($transaction->mobile_money_operator) }})
                                @endif
                                @break
                            @case('bank_transfer')
                                <i class="mr-1 fas fa-university"></i>Virement Bancaire
                                @break
                            @default
                                {{ $transaction->payment_method }}
                        @endswitch
                    </span>
                </div>
                @if($transaction->payment_reference)
                <div class="flex justify-between">
                    <span class="text-gray-600">Référence paiement:</span>
                    <span class="font-mono text-gray-900">{{ $transaction->payment_reference }}</span>
                </div>
                @endif
                <div class="flex justify-between">
                    <span class="text-gray-600">Date transaction:</span>
                    <span class="font-medium text-gray-900">{{ $transaction->transaction_date->format('d/m/Y H:i') }}</span>
                </div>
                @if($transaction->processed_at)
                <div class="flex justify-between">
                    <span class="text-gray-600">Date traitement:</span>
                    <span class="font-medium text-gray-900">{{ $transaction->processed_at->format('d/m/Y H:i') }}</span>
                </div>
                @endif
            </div>
        </div>

        <!-- Description -->
        @if($transaction->description)
        <div class="pt-4 border-t border-gray-200">
            <label class="block mb-2 text-xs font-medium tracking-wider text-gray-500 uppercase">Description</label>
            <p class="p-3 text-sm text-gray-700 rounded-lg bg-gray-50">
                {{ $transaction->description }}
            </p>
        </div>
        @endif

        <!-- Traité par -->
        <div class="pt-4 border-t border-gray-200">
            <label class="block mb-3 text-xs font-medium tracking-wider text-gray-500 uppercase">Traitement</label>
            <div class="space-y-3">
                @if($transaction->processedBy)
                <div class="flex items-center space-x-3">
                    <div class="flex items-center justify-center w-10 h-10 bg-blue-100 rounded-full">
                        <i class="text-blue-600 fas fa-user"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900">
                            {{ $transaction->processedBy->first_name }} {{ $transaction->processedBy->last_name }}
                        </p>
                        <p class="text-xs text-gray-500">Traité par</p>
                    </div>
                </div>
                @endif

                @if($transaction->validatedBy && $transaction->validated_at)
                <div class="flex items-center space-x-3">
                    <div class="flex items-center justify-center w-10 h-10 bg-green-100 rounded-full">
                        <i class="text-green-600 fas fa-check"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900">
                            {{ $transaction->validatedBy->first_name }} {{ $transaction->validatedBy->last_name }}
                        </p>
                        <p class="text-xs text-gray-500">
                            Validé le {{ $transaction->validated_at->format('d/m/Y H:i') }}
                        </p>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Actions pour transactions en attente -->
        @if($transaction->status === 'pending')
        <div class="pt-4 border-t border-gray-200">
            <label class="block mb-3 text-xs font-medium tracking-wider text-gray-500 uppercase">Actions</label>
            <div class="space-y-2">
                <button onclick="validateTransaction({{ $transaction->id }})"
                        class="w-full px-4 py-2 text-white transition-colors bg-green-600 rounded-lg hover:bg-green-700">
                    <i class="mr-2 fas fa-check"></i>Valider la Transaction
                </button>
                <button onclick="showRejectModal({{ $transaction->id }})"
                        class="w-full px-4 py-2 text-white transition-colors bg-red-600 rounded-lg hover:bg-red-700">
                    <i class="mr-2 fas fa-times"></i>Rejeter la Transaction
                </button>
            </div>
        </div>
        @endif

        <!-- Reçu Numérique Sécurisé -->
        <div class="pt-4 border-t border-gray-200">
            <label class="block mb-3 text-xs font-medium tracking-wider text-gray-500 uppercase">Gouvernance & Protocole</label>
            <div class="space-y-2">
                <a href="{{ route('admin.transactions.receipt', $transaction->id) }}"
                   target="_blank"
                   class="flex items-center justify-center px-4 py-3 text-white transition-all bg-slate-900 rounded-xl hover:bg-slate-800 shadow-lg shadow-slate-200 group">
                    <i class="mr-2 fas fa-fingerprint text-blue-400 group-hover:scale-110 transition"></i>
                    <span class="text-xs font-black uppercase tracking-widest">Générer l'Artefact de Transaction</span>
                </a>
                
                @if($transaction->receipt)
                <a href="{{ $transaction->receipt->receipt_url }}"
                   target="_blank"
                   class="flex items-center justify-center px-4 py-2 text-blue-600 transition-colors rounded-lg bg-blue-50 hover:bg-blue-100 text-xs font-bold uppercase">
                    <i class="mr-2 fas fa-receipt"></i>Télécharger le Reçu Leguacy
                </a>
                @endif
            </div>
        </div>

        <!-- Timeline d'activité -->
        <div class="pt-4 border-t border-gray-200">
            <label class="block mb-3 text-xs font-medium tracking-wider text-gray-500 uppercase">Timeline</label>
            <div class="space-y-3">
                <div class="flex items-start space-x-3">
                    <div class="w-2 h-2 mt-2 bg-blue-600 rounded-full"></div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900">Transaction créée</p>
                        <p class="text-xs text-gray-500">{{ $transaction->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
                @if($transaction->processed_at)
                <div class="flex items-start space-x-3">
                    <div class="w-2 h-2 mt-2 bg-green-600 rounded-full"></div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900">Transaction traitée</p>
                        <p class="text-xs text-gray-500">{{ $transaction->processed_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
                @endif
                @if($transaction->validated_at)
                <div class="flex items-start space-x-3">
                    <div class="w-2 h-2 mt-2 bg-purple-600 rounded-full"></div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900">Transaction validée</p>
                        <p class="text-xs text-gray-500">{{ $transaction->validated_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal de rejet -->
<div id="reject-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden p-4 bg-black bg-opacity-50">
    <div class="w-full max-w-md p-6 bg-white shadow-xl rounded-xl">
        <h3 class="mb-4 text-lg font-semibold text-gray-900">Rejeter la Transaction</h3>
        <form id="reject-form" onsubmit="rejectTransaction(event, {{ $transaction->id }})">
            <div class="mb-4">
                <label class="block mb-2 text-sm font-medium text-gray-700">Raison du rejet</label>
                <textarea name="reason"
                          rows="4"
                          required
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                          placeholder="Expliquez pourquoi cette transaction est rejetée..."></textarea>
            </div>
            <div class="flex space-x-3">
                <button type="submit" class="flex-1 px-4 py-2 text-white transition-colors bg-red-600 rounded-lg hover:bg-red-700">
                    Confirmer le Rejet
                </button>
                <button type="button" onclick="hideRejectModal()" class="flex-1 px-4 py-2 text-gray-700 transition-colors bg-gray-300 rounded-lg hover:bg-gray-400">
                    Annuler
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function validateTransaction(transactionId) {
        if (!confirm('Êtes-vous sûr de vouloir valider cette transaction ?')) return;

        fetch(`/admin/transactions/${transactionId}/validate`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue');
        });
    }

    function showRejectModal(transactionId) {
        document.getElementById('reject-modal').classList.remove('hidden');
    }

    function hideRejectModal() {
        document.getElementById('reject-modal').classList.add('hidden');
    }

    function rejectTransaction(event, transactionId) {
        event.preventDefault();

        const form = event.target;
        const formData = new FormData(form);

        fetch(`/admin/transactions/${transactionId}/reject`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue');
        });
    }

    // Fermer le modal si on clique en dehors
    document.getElementById('reject-modal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            hideRejectModal();
        }
    });
</script>
