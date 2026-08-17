@extends('layouts.app_admin')

@section('title', 'Audit Visuel Tontine')
@section('page-title', 'Supervision / Audit Visuel Interactif')

@section('content')
<div class="space-y-6">
    <!-- En-tête -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-bold text-slate-900 tracking-tight">Audit Visuel de Tontine</h2>
            <p class="text-slate-400 text-xs">Recherche par numéro de compte et navigation interactive entre les cycles</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.tontines.index') }}" class="btn-bank btn-bank-outline text-xs">
                <i class="fas fa-list mr-2 text-[9px]"></i> Retour au Registre
            </a>
        </div>
    </div>

    <!-- Layout : Recherche (3/12) | Résultats (9/12) -->
    <div class="grid grid-cols-12 gap-6 items-start">

        <!-- PANNEAU GAUCHE : RECHERCHE (3/12) -->
        <div class="col-span-3 sticky top-24">
            <div class="bank-card p-5 border-trust">
                <h5 class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4 flex items-center gap-2">
                    <i class="fas fa-search text-blue-600"></i> Localisation du Dossier
                </h5>

                <div class="space-y-3">
                    <div>
                        <label class="text-[9px] font-black text-slate-400 uppercase mb-1.5 block tracking-tight">Numéro de Compte</label>
                        <div class="relative">
                            <input type="text" id="accountSearch"
                                placeholder="Ex: ACC-2026-..."
                                class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2.5 text-xs font-bold text-slate-800 focus:ring-2 focus:ring-blue-500 outline-none transition uppercase">
                            <button id="searchBtn"
                                class="absolute right-1.5 top-1/2 -translate-y-1/2 w-7 h-7 bg-blue-600 text-white rounded-md flex items-center justify-center hover:bg-blue-700 transition">
                                <i class="fas fa-magnifying-glass text-[10px]"></i>
                            </button>
                        </div>
                    </div>

                    <div id="searchPlaceholder" class="py-6 text-center bg-slate-50/50 rounded-xl border border-dashed border-slate-100">
                        <i class="fas fa-folder-open text-xl text-slate-200 mb-1.5 block"></i>
                        <p class="text-[9px] font-bold text-slate-300 uppercase">Prêt pour l'audit</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- PANNEAU DROIT : RÉSULTATS (9/12) -->
        <div class="col-span-9 min-h-[600px]">

            <!-- Contenu affiché après recherche -->
            <div id="auditContent" class="hidden space-y-5 animate-in fade-in slide-in-from-right-4 duration-500">

                <!-- Header client -->
                <div class="bank-card p-5 border-trust bg-gradient-to-r from-blue-600 to-blue-100 text-white">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-white/10 backdrop-blur-md border border-white/20 flex items-center justify-center">
                                <i class="fas fa-user-tie text-base text-white"></i>
                            </div>
                            <div>
                                <p class="text-[5px] font-bold text-blue-100 uppercase tracking-widest opacity-80 mb-0.5">Dossier Titulaire</p>
                                <h3 id="dispName" class="text-base font-black tracking-tight"></h3>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <div class="px-3 py-1.5 bg-white/10 backdrop-blur-md rounded-lg border border-white/20">
                                <p class="text-[4px] font-bold text-blue-100 uppercase mb-0.5">Fréquence</p>
                                <p id="dispFreq" class="text-xs font-black uppercase tracking-tighter"></p>
                            </div>
                            <div class="px-3 py-1.5 bg-white/10 backdrop-blur-md rounded-lg border border-white/20">
                                <p class="text-[4px] font-bold text-blue-100 uppercase mb-0.5">Solde Tontine</p>
                                <p id="dispBalance" class="text-xs font-black font-numeric"></p>
                            </div>
                            <div id="loanAction" class="flex items-center">
                                <!-- Bouton injecté par JS -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chronologie des cycles -->
                <div class="bank-card p-5 border-trust">
                    <div class="flex items-center justify-between mb-3">
                        <h5 class="text-[9px] font-black text-slate-400 uppercase tracking-widest flex items-center gap-2">
                            <i class="fas fa-history text-blue-600"></i> Chronologie des Cycles
                        </h5>
                        <p class="text-[8px] font-bold text-slate-400 uppercase italic">Cliquez sur un cycle pour auditer</p>
                    </div>
                    <div id="cycleNav" class="flex flex-wrap gap-1.5"></div>
                </div>

                <!-- KPIs du cycle sélectionné -->
                <div class="grid grid-cols-3 gap-3">
                    <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm text-center">
                        <p class="text-[8px] font-bold text-slate-400 uppercase mb-1">Cible du Mois</p>
                        <p id="targetAmt" class="text-base font-black text-slate-900 font-numeric"></p>
                    </div>
                    <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm text-center">
                        <p class="text-[8px] font-bold text-slate-400 uppercase mb-1">Collecté au Cycle</p>
                        <p id="collectedAmt" class="text-base font-black text-purple-600 font-numeric"></p>
                    </div>
                    <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm text-center">
                        <p class="text-[8px] font-bold text-slate-400 uppercase mb-1">Statut Temporel</p>
                        <div id="cycleStatusBadge" class="mt-1"></div>
                    </div>
                </div>

                <!-- Grille visuelle calendrier -->
                <div class="bank-card p-6 border-trust">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
                                <i class="fas fa-calendar-alt text-xs"></i>
                            </div>
                            <div>
                                <h4 class="text-xs font-black text-slate-900 uppercase tracking-tight">Progression du Cycle Sélectionné</h4>
                                <p id="cycleDates" class="text-[9px] font-bold text-slate-400"></p>
                            </div>
                        </div>
                        <span id="gridStats" class="text-[9px] font-black text-slate-500 bg-slate-100 px-2.5 py-1 rounded-full uppercase"></span>
                    </div>

                    <div id="visualGrid" class="grid grid-cols-7 sm:grid-cols-11 md:grid-cols-15 lg:grid-cols-31 gap-2">
                        <!-- Généré par JS -->
                    </div>

                    <div class="mt-8 pt-6 border-t border-slate-100">
                        <div class="flex justify-between text-[9px] font-black uppercase mb-2 px-1">
                            <span class="text-slate-500">Intégrité de la Collecte</span>
                            <span id="progPercent" class="text-purple-600">0%</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden shadow-inner p-0.5">
                            <div id="progBar" class="bg-gradient-to-r from-purple-500 to-purple-800 h-1.5 rounded-full transition-all duration-1000" style="width: 0%"></div>
                        </div>
                    </div>
                </div>

                <!-- Note d'information -->
                <div class="p-4 bg-blue-50/50 rounded-xl border border-blue-100 flex items-start gap-3">
                    <div class="w-7 h-7 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center shrink-0">
                        <i class="fas fa-info-circle text-[10px]"></i>
                    </div>
                    <div>
                        <h6 class="text-[9px] font-black text-blue-900 uppercase mb-0.5">Audit en temps réel</h6>
                        <p class="text-[9px] text-blue-700 leading-relaxed font-medium">Vous naviguez entre les cycles sans rechargement de page. Les cases rouges barrées représentent les mises effectives du client enregistrées dans le grand livre numérique pour la période sélectionnée.</p>
                    </div>
                </div>
            </div>

            <!-- État vide (avant recherche) -->
            <div id="emptyAudit" class="flex flex-col items-center justify-center py-28 bg-slate-50/50 border-2 border-dashed border-slate-100 rounded-2xl text-center">
                <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center shadow-sm mb-4">
                    <i class="fas fa-magnifying-glass text-xl text-slate-200"></i>
                </div>
                <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">En Attente de Données</h4>
                <p class="text-[9px] font-bold text-slate-300 max-w-xs uppercase leading-relaxed">Veuillez saisir un numéro de compte dans le panneau latéral pour charger l'analyse visuelle</p>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('accountSearch');
    const searchBtn = document.getElementById('searchBtn');
    const visualGrid = document.getElementById('visualGrid');
    const cycleNav = document.getElementById('cycleNav');

    let currentAccountNumber = '';

    function formatMoney(amount) {
        return new Intl.NumberFormat('fr-FR').format(amount) + ' <small class="text-[10px]">XOF</small>';
    }

    async function loadAuditData(accNum, cycleNum = null) {
        try {
            searchBtn.innerHTML = '<i class="fas fa-spinner fa-spin text-[10px]"></i>';
            const url = new URL('{{ route("admin.tontines.visual-audit.data") }}', window.location.origin);
            url.searchParams.append('account_number', accNum);
            if (cycleNum) url.searchParams.append('cycle_number', cycleNum);

            const response = await fetch(url);
            const data = await response.json();

            if (!response.ok) {
                Swal.fire({ icon: 'error', title: 'Erreur', text: data.error || 'Une erreur est survenue' });
                return;
            }

            currentAccountNumber = accNum;
            document.getElementById('emptyAudit').classList.add('hidden');
            document.getElementById('auditContent').classList.remove('hidden');
            document.getElementById('searchPlaceholder').classList.add('hidden');

            // Header
            document.getElementById('dispName').textContent = data.tontine.client_name;
            document.getElementById('dispFreq').textContent = data.tontine.frequency;
            document.getElementById('dispBalance').innerHTML = formatMoney(data.tontine.balance);

            // Action Prêt
            document.getElementById('loanAction').innerHTML = `
                <a href="{{ route('admin.loans.create') }}?client_id=${data.tontine.client_id}" 
                   class="btn-bank btn-bank-outline !text-[9px] !px-4 !py-2 bg-white/20 hover:bg-white/40 border-white/30 text-white flex items-center gap-2">
                    <i class="fas fa-hand-holding-dollar text-[10px]"></i> Demander Prêt
                </a>
            `;

            // KPIs
            document.getElementById('targetAmt').innerHTML = formatMoney(data.selected_cycle.target);
            document.getElementById('collectedAmt').innerHTML = formatMoney(data.selected_cycle.collected);
            document.getElementById('cycleDates').textContent = `${data.selected_cycle.start_date} au ${data.selected_cycle.end_date}`;

            const badge = document.getElementById('cycleStatusBadge');
            badge.innerHTML = data.selected_cycle.status === 'active'
                ? '<span class="bank-badge badge-success !text-[9px]">Ouvert / Actif</span>'
                : '<span class="bank-badge badge-primary !text-[9px]">Complété</span>';

            // Navigation cycles
            cycleNav.innerHTML = data.cycles.map(c => `
                <button onclick="changeCycle(${c.number})"
                    class="px-3 py-1.5 rounded-lg border flex items-center gap-1.5 transition-all hover:scale-105 active:scale-95
                    ${c.number === data.selected_cycle.number
                        ? 'bg-blue-600 border-blue-600 text-white shadow-md shadow-blue-200 font-black'
                        : 'bg-white border-slate-100 text-slate-600 font-bold hover:border-blue-200 shadow-sm'}">
                    <span class="text-[10px]">${c.number}C</span>
                    ${c.status === 'completed'
                        ? '<i class="fas fa-check-circle text-[8px] text-emerald-400"></i>'
                        : '<i class="fas fa-clock text-[8px] text-amber-400"></i>'}
                </button>
            `).join('');

            // Grille
            document.getElementById('gridStats').textContent = `${data.grid.filled} / ${data.grid.total} UNITÉS`;
            visualGrid.innerHTML = '';

            for (let i = 1; i <= data.grid.total; i++) {
                const isFilled = i <= data.grid.filled;
                const slot = document.createElement('div');
                slot.className = `aspect-square rounded-lg border flex items-center justify-center relative group transition-all duration-200 hover:scale-110
                    ${isFilled ? 'bg-rose-500 border-rose-600 text-white shadow-sm shadow-rose-200' : 'bg-white border-slate-100 text-slate-200'}`;

                slot.innerHTML = `
                    <span class="text-[8px] font-black z-10">${i}</span>
                    ${isFilled ? `
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="w-full h-[1.5px] bg-white/40 rotate-45 absolute rounded-full"></div>
                            <div class="w-full h-[1.5px] bg-white/40 -rotate-45 absolute rounded-full"></div>
                        </div>` : ''}
                `;
                visualGrid.appendChild(slot);
            }

            // Progress
            const prog = data.selected_cycle.target > 0
                ? (data.selected_cycle.collected / data.selected_cycle.target) * 100 : 0;
            document.getElementById('progPercent').textContent = Math.round(prog) + '%';
            document.getElementById('progBar').style.width = Math.min(prog, 100) + '%';

        } catch (e) {
            console.error(e);
        } finally {
            searchBtn.innerHTML = '<i class="fas fa-magnifying-glass text-[10px]"></i>';
        }
    }

    window.changeCycle = function (num) {
        if (currentAccountNumber) loadAuditData(currentAccountNumber, num);
    };

    searchBtn.addEventListener('click', () => {
        if (searchInput.value) loadAuditData(searchInput.value);
    });

    searchInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter' && searchInput.value) loadAuditData(searchInput.value);
    });
});
</script>
@endsection