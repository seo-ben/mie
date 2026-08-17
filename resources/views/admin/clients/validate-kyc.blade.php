@extends('layouts.app_admin')

@section('title', 'Arbitrage de Conformité KYC - ' . $client->full_name)
@section('page-title', 'Protocole / Audit de Sécurité')

@section('content')
<div class="max-w-6xl mx-auto space-y-8">
    <!-- En-tête Institutionnel -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 no-print">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.clients.show', $client->id) }}" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-xl flex items-center justify-center transition border border-slate-200 text-slate-600">
                <i class="fas fa-chevron-left text-xs"></i>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Arbitrage de Conformité KYC</h2>
                <p class="text-slate-500 text-sm font-medium">Révision et complétion du dossier avant validation opérationnelle</p>
            </div>
        </div>
        <span class="px-4 py-1.5 bg-amber-50 text-amber-700 text-[10px] font-black rounded-full border border-amber-200 uppercase tracking-widest">
            Audit en Cours
        </span>
    </div>

    <!-- Alertes de Validation -->
    @if ($errors->any())
        <div class="bank-card !border-rose-200 !bg-rose-50/50 p-6">
            <div class="flex gap-3">
                <i class="fas fa-triangle-exclamation text-rose-500 mt-1"></i>
                <div>
                    <h3 class="text-sm font-black text-rose-900 uppercase tracking-widest leading-none">Données KYC Manquantes ou Invalides</h3>
                    <ul class="mt-3 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li class="text-[11px] font-bold text-rose-700 list-disc list-inside">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <form action="{{ route('admin.clients.approve-kyc', $client->id) }}" method="POST" id="kycForm">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Colonne Gauche : Documents & Preuves -->
            <div class="lg:col-span-1 space-y-6">
                <div class="bank-card p-6">
                    <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4 border-b pb-2">Artefact de Profil</h3>
                    <div class="flex flex-col items-center gap-4">
                        @if($client->profile_photo_url)
                            <img src="{{ asset('storage/' . $client->profile_photo_url) }}" class="w-full aspect-square rounded-2xl object-cover shadow-sm border border-slate-100">
                        @else
                            <div class="w-full aspect-square bg-slate-100 rounded-2xl flex items-center justify-center text-4xl font-black text-slate-300 border-2 border-dashed border-slate-200 uppercase">
                                {{ substr($client->first_name, 0, 1) }}{{ substr($client->last_name, 0, 1) }}
                            </div>
                        @endif
                    </div>
                </div>

                <div class="bank-card p-6">
                    <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4 border-b pb-2">Documents Versés au Dossier</h3>
                    <div class="space-y-3">
                        @forelse($client->documents as $doc)
                            <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank" class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl border border-slate-100 hover:bg-slate-100 transition group">
                                <div class="w-8 h-8 rounded-lg bg-white flex items-center justify-center text-blue-600 shadow-sm border border-slate-100">
                                    <i class="fas fa-file-pdf"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-[10px] font-black text-slate-800 truncate uppercase">{{ $doc->document_type }}</p>
                                    <p class="text-[9px] font-bold text-slate-400 uppercase">Versé le {{ $doc->created_at->format('d/m/Y') }}</p>
                                </div>
                                <i class="fas fa-external-link text-[10px] opacity-0 group-hover:opacity-100 transition"></i>
                            </a>
                        @empty
                            <div class="py-12 text-center">
                                <i class="fas fa-file-circle-exclamation text-3xl text-slate-200 mb-3 block"></i>
                                <span class="text-[9px] font-bold text-slate-400 uppercase">Aucun document numérisé</span>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Colonne Droite : Formulaire d'Audit & Correction -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bank-card p-8">
                    <div class="flex items-center justify-between mb-8 border-b pb-4 border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div>
                                <h3 class="text-[10px] font-black text-slate-800 uppercase tracking-widest">Audit des Données KYC</h3>
                                <p class="text-[9px] text-slate-400 font-bold uppercase">Vérifiez et complétez les informations selon les documents</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="col-span-2 grid grid-cols-2 gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-100 mb-4">
                            <div>
                                <p class="text-[9px] font-bold text-slate-400 uppercase">Nom Complet</p>
                                <p class="text-sm font-black text-slate-900 uppercase">{{ $client->full_name }}</p>
                            </div>
                            <div>
                                <p class="text-[9px] font-bold text-slate-400 uppercase">Canal Téléphonique</p>
                                <p class="text-sm font-black text-slate-900">{{ $client->phone }}</p>
                            </div>
                        </div>

                        <!-- Date de Naissance & Genre -->
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Date de Naissance *</label>
                            <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $client->date_of_birth?->format('Y-m-d')) }}" required class="bank-input">
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Genre / Sexe *</label>
                            <select name="gender" required class="bank-input uppercase">
                                <option value="">Choisir...</option>
                                <option value="M" {{ old('gender', $client->gender) == 'M' ? 'selected' : '' }}>Masculin (M)</option>
                                <option value="F" {{ old('gender', $client->gender) == 'F' ? 'selected' : '' }}>Féminin (F)</option>
                                <option value="Other" {{ old('gender', $client->gender) == 'Other' ? 'selected' : '' }}>Autre</option>
                            </select>
                        </div>

                        <!-- Localisation -->
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Adresse Physique *</label>
                            <input type="text" name="address" value="{{ old('address', $client->address) }}" required class="bank-input" placeholder="Quartier, Rue...">
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Ville / Localisation *</label>
                            <input type="text" name="city" value="{{ old('city', $client->city) }}" required class="bank-input" placeholder="Ex: Lomé">
                        </div>

                        <!-- Identification -->
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Type de Document *</label>
                            <select name="id_type" required class="bank-input uppercase">
                                <option value="">Choisir...</option>
                                <option value="cni" {{ old('id_type', $client->id_type) == 'cni' ? 'selected' : '' }}>CNI</option>
                                <option value="passport" {{ old('id_type', $client->id_type) == 'passport' ? 'selected' : '' }}>Passeport</option>
                                <option value="driving_license" {{ old('id_type', $client->id_type) == 'driving_license' ? 'selected' : '' }}>Permis</option>
                                <option value="other" {{ old('id_type', $client->id_type) == 'other' ? 'selected' : '' }}>Autre</option>
                            </select>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Numéro de Série du Doc *</label>
                            <input type="text" name="id_number" value="{{ old('id_number', $client->id_number) }}" required class="bank-input font-mono" placeholder="S/N: XXXXXXXX">
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Échéance de Validité</label>
                            <input type="date" name="id_expiry_date" value="{{ old('id_expiry_date', $client->id_expiry_date?->format('Y-m-d')) }}" class="bank-input">
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Revenu Estimé (XOF)</label>
                            <input type="number" name="monthly_income" value="{{ old('monthly_income', $client->monthly_income) }}" step="1" class="bank-input font-bold" placeholder="0">
                        </div>
                    </div>

                    <div class="mt-12 flex items-center justify-between gap-4 border-t pt-8 border-slate-100">
                        <button type="button" onclick="openRejectModal()" class="btn-bank btn-bank-danger px-8 py-3 text-xs shadow-lg shadow-rose-500/10">
                            <i class="fas fa-shield-xmark mr-2 text-xs"></i> Rejeter le Dossier
                        </button>
                        
                        <div class="flex items-center gap-4">
                            <a href="{{ route('admin.clients.show', $client->id) }}" class="text-[10px] font-black text-slate-400 uppercase tracking-widest hover:text-slate-600 transition">Ajourner</a>
                            <button type="submit" class="btn-bank btn-bank-success px-12 py-3 text-sm shadow-lg shadow-emerald-500/20 font-black uppercase">
                                <i class="fas fa-shield-check mr-2 text-xs"></i> Approuver le KYC
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Modal de Rejet de Conformité -->
<div id="rejectModal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all border border-slate-100">
        <div class="px-8 py-6 bg-rose-600 text-white flex items-center justify-between">
            <div>
                <h3 class="text-lg font-black uppercase tracking-tight">Notification de Rejet</h3>
                <p class="text-[10px] font-bold text-white/70 uppercase">Protocole d'Audit de Sécurité</p>
            </div>
            <button onclick="closeRejectModal()" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition">
                <i class="fas fa-xmark text-sm"></i>
            </button>
        </div>
        <form action="{{ route('admin.clients.reject-kyc', $client->id) }}" method="POST" class="p-8 space-y-6">
            @csrf
            <div class="space-y-2">
                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Justification Administrative du Rejet *</label>
                <textarea name="rejection_reason" rows="4" required placeholder="Spécifiez les anomalies détectées..." class="bank-input resize-none focus:ring-rose-500"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4 pt-2">
                <button type="button" onclick="closeRejectModal()" class="btn-bank btn-bank-outline py-3 text-xs">Annuler</button>
                <button type="submit" class="btn-bank btn-bank-danger py-3 text-xs shadow-lg shadow-rose-500/20 font-black">Confirmer le Rejet</button>
            </div>
        </form>
    </div>
</div>

<script>
function openRejectModal(){ document.getElementById('rejectModal').classList.remove('hidden'); }
function closeRejectModal(){ document.getElementById('rejectModal').classList.add('hidden'); }
document.getElementById('rejectModal')?.addEventListener('click', e => { if(e.target===e.currentTarget) closeRejectModal(); });
</script>
@endsection
