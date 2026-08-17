<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="FSD-YAYRA — Mutuelle d'Épargne et de Crédit rattachée à la M.I.E. Cotise aujourd'hui, avance demain. Épargne, tontine, crédit progressif et Mobile Money au Togo.">
<title>FSD-YAYRA — Mutuelle d'Épargne et de Crédit</title>

<!-- Google Fonts (Plus Jakarta Sans - Style Yas Togo Clean Fintech) -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,600&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">

<!-- Tailwind CSS CDN -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    theme: {
      extend: {
        colors: {
          brand: {
            navy: '#002B66',       /* Bleu Royal Yas Togo */
            navyDark: '#001F4D',   /* Bleu Sombre Yas */
            blueAccent: '#00377D', /* Bleu Vif Yas */
            gold: '#FFD100',       /* Jaune Or Yas Togo */
            goldVif: '#FFC000',
            terracotta: '#B0502A',
            green: '#16A34A'
          }
        },
        fontFamily: {
          sans: ['Plus Jakarta Sans', 'sans-serif'],
          mono: ['Space Mono', 'monospace']
        }
      }
    }
  }
</script>

<style>
/* ============================================================
   ANIMATIONS DYNAMIQUES AU DÉFILEMENT (SCROLL REVEAL HAUT & BAS)
   ============================================================ */
.reveal-init {
  opacity: 0;
  transform: translateY(35px) scale(0.97);
  transition: opacity 0.75s cubic-bezier(0.16, 1, 0.3, 1), transform 0.75s cubic-bezier(0.16, 1, 0.3, 1);
  will-change: opacity, transform;
}
.reveal-init.reveal-visible {
  opacity: 1;
  transform: translateY(0) scale(1);
}

.delay-100 { transition-delay: 100ms; }
.delay-200 { transition-delay: 200ms; }
.delay-300 { transition-delay: 300ms; }
.delay-400 { transition-delay: 400ms; }
</style>
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased selection:bg-brand-gold selection:text-brand-navy">

<!-- ============================================================ -->
<!-- EN-TÊTE / NAVBAR STYLE YAS TOGO -->
<!-- ============================================================ -->
<header class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-slate-200/80 shadow-sm">
  <!-- Sub-header sombre Yas -->
  <div class="bg-brand-navy text-slate-300 text-[11px] font-mono py-1.5 border-b border-white/10">
    <div class="max-w-6xl mx-auto px-4 flex flex-wrap justify-between items-center gap-2">
      <div class="truncate">Rattachée à la <strong class="text-brand-gold font-semibold">M.I.E</strong> — Mission Internationale d'Évangélisation</div>
      <div class="flex items-center gap-3">
        <a href="tel:+22892814161" class="hover:text-white transition-colors">92 81 41 61</a>
        <span>·</span>
        <a href="tel:+22898072417" class="hover:text-white transition-colors">98 07 24 17</a>
      </div>
    </div>
  </div>

  <!-- Barre Nav principale -->
  <div class="max-w-6xl mx-auto px-4 py-3.5 flex items-center justify-between gap-3">
    <!-- Logo Style Yas -->
    <a href="/" class="flex items-center gap-3 group shrink-0">
      <div class="w-11 h-11 rounded-2xl bg-brand-gold p-2 text-brand-navy shadow-md flex items-center justify-center font-extrabold text-xl group-hover:scale-105 transition-transform">
        F
      </div>
      <div>
        <div class="font-extrabold text-xl sm:text-2xl text-brand-navy tracking-tight leading-none">
          FSD-<span class="text-brand-terracotta">YAYRA</span>
        </div>
        <div class="font-mono text-[8px] sm:text-[9.5px] uppercase tracking-widest text-slate-500 font-bold mt-0.5">
          Mutuelle d'Épargne &amp; Crédit
        </div>
      </div>
    </a>

    <!-- Navigation Liens Desktop -->
    <nav class="hidden md:flex items-center gap-8 text-sm font-bold text-slate-700">
      <a href="#accueil" class="hover:text-brand-navy transition-colors">Accueil</a>
      <a href="#services" class="hover:text-brand-navy transition-colors">Services</a>
      <a href="#paliers" class="hover:text-brand-navy transition-colors">Paliers</a>
      <a href="#temoignages" class="hover:text-brand-navy transition-colors">Témoignages</a>
      <a href="#adhesion" class="hover:text-brand-navy transition-colors">Comment Ça Marche</a>
      <a href="#simulateur" class="hover:text-brand-navy transition-colors">Simulateur</a>
    </nav>

    <!-- Actions Nav Pill Button Yas Style -->
    <div class="flex items-center gap-2 sm:gap-3">
      <a href="#simulateur" class="bg-brand-navy text-white text-[11px] sm:text-xs font-extrabold tracking-wider uppercase px-5 sm:px-7 py-3 rounded-full border-2 border-brand-gold shadow-[0_4px_0_#FFD100] hover:-translate-y-0.5 hover:shadow-[0_6px_0_#FFD100] transition-all whitespace-nowrap">
        SIMULER MON PRÊT
      </a>
      <button type="button" onclick="toggleMobileMenu()" class="md:hidden p-2 text-brand-navy focus:outline-none" aria-label="Menu Mobile">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
      </button>
    </div>
  </div>

  <!-- Menu Déroulant Mobile -->
  <div id="mobileMenu" class="hidden md:hidden bg-brand-navy text-white px-5 py-5 border-t border-slate-800 space-y-3.5">
    <a href="#accueil" onclick="toggleMobileMenu()" class="block text-sm font-bold hover:text-brand-gold">Accueil</a>
    <a href="#services" onclick="toggleMobileMenu()" class="block text-sm font-bold hover:text-brand-gold">Services</a>
    <a href="#paliers" onclick="toggleMobileMenu()" class="block text-sm font-bold hover:text-brand-gold">Paliers &amp; Crédit</a>
    <a href="#temoignages" onclick="toggleMobileMenu()" class="block text-sm font-bold hover:text-brand-gold">Témoignages</a>
    <a href="#adhesion" onclick="toggleMobileMenu()" class="block text-sm font-bold hover:text-brand-gold">Comment Ça Marche</a>
    <a href="#simulateur" onclick="toggleMobileMenu()" class="block text-sm font-bold hover:text-brand-gold">Simulateur de Crédit</a>
  </div>
</header>

<main>

<!-- ============================================================ -->
<!-- HERO SECTION — SLIDER ARRIÈRE-PLAN & TEXTE CENTRÉ -->
<!-- ============================================================ -->
<section class="relative bg-brand-navy overflow-hidden min-h-[540px] sm:min-h-[620px] flex items-center justify-center py-16 sm:py-24" id="accueil">
  
  <!-- SLIDER D'IMAGES EN ARRIÈRE-PLAN -->
  <div class="absolute inset-0 z-0">
    <div id="heroTrackCorp" class="flex w-[300%] h-full transition-transform duration-1000 ease-[cubic-bezier(0.25,1,0.5,1)] will-change-transform">
      <div class="w-1/3 h-full relative flex-shrink-0">
        <img src="{{ asset('images/hero_market.jpg') }}" alt="Collecte au marché de Lomé" class="w-full h-full object-cover">
      </div>
      <div class="w-1/3 h-full relative flex-shrink-0">
        <img src="{{ asset('images/epargne_tontine.jpg') }}" alt="Carnet d'épargne et tontine" class="w-full h-full object-cover">
      </div>
      <div class="w-1/3 h-full relative flex-shrink-0">
        <img src="{{ asset('images/caution_solidaire.jpg') }}" alt="Groupe solidaire de revendeuses" class="w-full h-full object-cover">
      </div>
    </div>

    <!-- OVERLAY SOMBRE DEGRADÉ STYLE YAS TOGO -->
    <div class="absolute inset-0 bg-gradient-to-t from-brand-navy/95 via-brand-navy/80 to-brand-navy/70 backdrop-blur-[1px]"></div>
  </div>

  <!-- CONTENU TEXTE CENTRÉ EN AVANT-PLAN -->
  <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 text-center reveal-init">
    <h1 class="font-extrabold text-3xl sm:text-5xl lg:text-6xl text-white uppercase tracking-tight leading-tight">
      DÉVELOPPEZ VOTRE ACTIVITÉ,<br class="hidden sm:block"/>
      <span class="text-brand-gold block sm:inline"> ACCÉDEZ AU CRÉDIT</span>
    </h1>

    <p class="text-slate-200 text-sm sm:text-lg mt-4 sm:mt-6 max-w-2xl mx-auto font-normal leading-relaxed">
      FSD-YAYRA accompagne les commerçantes du marché, les artisans et les familles togolaises
      avec une épargne sécurisée, la tontine de quartier et un crédit progressif sans garanties lourdes.
    </p>

    <!-- BOUTONS CENTRÉS STYLE YAS -->
    <div class="flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-4 mt-8 sm:mt-10">
      <a href="https://wa.me/22892814161" target="_blank" class="w-full sm:w-auto bg-brand-navy text-white font-extrabold text-xs uppercase tracking-wider px-8 py-4 rounded-full border-2 border-brand-gold shadow-[0_4px_0_#FFD100] hover:-translate-y-0.5 hover:shadow-[0_6px_0_#FFD100] transition-all text-center justify-center flex items-center gap-2">
        DEMANDER UN CRÉDIT
      </a>
      <a href="#paliers" class="w-full sm:w-auto text-brand-gold hover:text-white font-bold text-sm px-6 py-3 text-center transition-colors">
        Voir la grille →
      </a>
    </div>
  </div>

  <!-- SLIDER DOTS CENTRÉS EN BAS -->
  <div class="absolute bottom-6 left-1/2 -translate-x-1/2 z-20 flex items-center gap-2">
    <button type="button" onclick="goSlideCorp(0)" class="dot-item w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full bg-white/40 transition-all [&.actif]:w-8 [&.actif]:bg-brand-gold [&.actif]:rounded-xl actif" aria-label="Slide 1"></button>
    <button type="button" onclick="goSlideCorp(1)" class="dot-item w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full bg-white/40 transition-all [&.actif]:w-8 [&.actif]:bg-brand-gold [&.actif]:rounded-xl" aria-label="Slide 2"></button>
    <button type="button" onclick="goSlideCorp(2)" class="dot-item w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full bg-white/40 transition-all [&.actif]:w-8 [&.actif]:bg-brand-gold [&.actif]:rounded-xl" aria-label="Slide 3"></button>
  </div>
</section>

<!-- ============================================================ -->
<!-- SECTION SERVICES — CARDS DYNAMIQUES AU DÉFILEMENT -->
<!-- ============================================================ -->
<section class="bg-slate-50 py-16 sm:py-24" id="services">
  <div class="max-w-6xl mx-auto px-4">
    
    <!-- En-tête avec animation reveal -->
    <div class="text-center max-w-2xl mx-auto mb-12 sm:mb-16 reveal-init">
      <span class="bg-brand-gold text-brand-navy font-extrabold uppercase text-xs px-4 py-1.5 rounded-full inline-block tracking-wider mb-3 shadow-sm">
        NOS SERVICES FINANCIERS
      </span>
      <h2 class="font-extrabold text-3xl sm:text-4xl text-brand-navy">
        Quatre solutions adaptées à vos réalités
      </h2>
      <p class="text-slate-600 text-sm sm:text-base mt-3 leading-relaxed">
        Une mutuelle de proximité inspirée par la culture togolaise pour vous aider à épargner et grandir.
      </p>
    </div>

    <!-- 4 Cartes Animées au défilement -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

      <!-- Carte 1 -->
      <div class="bg-white rounded-3xl p-8 border border-slate-200/80 shadow-sm hover:shadow-2xl hover:border-brand-gold hover:-translate-y-2 transition-all duration-300 flex flex-col justify-between group reveal-init delay-100">
        <div>
          <div class="w-14 h-14 rounded-2xl bg-brand-navy text-brand-gold font-extrabold flex items-center justify-center text-xl mb-6 shadow-md group-hover:scale-110 group-hover:bg-brand-gold group-hover:text-brand-navy transition-all">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <span class="text-[10px] font-mono font-bold uppercase tracking-wider text-brand-terracotta bg-orange-50 px-2.5 py-1 rounded-md">Sécurité 100%</span>
          <h3 class="font-extrabold text-xl text-brand-navy mt-2 group-hover:text-brand-blueAccent transition-colors">
            Épargne &amp; Carnet
          </h3>
          <p class="text-slate-600 text-sm mt-3 leading-relaxed">
            Constituez votre capital quotidiennement ou hebdomadairement avec un carnet officiel et des reçus sécurisés.
          </p>
        </div>
        <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-xs font-bold text-brand-navy">
          <span>Collecte au marché</span>
          <span class="text-brand-gold text-base group-hover:translate-x-1 transition-transform">→</span>
        </div>
      </div>

      <!-- Carte 2 -->
      <div class="bg-white rounded-3xl p-8 border border-slate-200/80 shadow-sm hover:shadow-2xl hover:border-brand-gold hover:-translate-y-2 transition-all duration-300 flex flex-col justify-between group reveal-init delay-200">
        <div>
          <div class="w-14 h-14 rounded-2xl bg-brand-navy text-brand-gold font-extrabold flex items-center justify-center text-xl mb-6 shadow-md group-hover:scale-110 group-hover:bg-brand-gold group-hover:text-brand-navy transition-all">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
          </div>
          <span class="text-[10px] font-mono font-bold uppercase tracking-wider text-amber-700 bg-amber-50 px-2.5 py-1 rounded-md">Entraide Locale</span>
          <h3 class="font-extrabold text-xl text-brand-navy mt-2 group-hover:text-brand-blueAccent transition-colors">
            Tontine de Quartier
          </h3>
          <p class="text-slate-600 text-sm mt-3 leading-relaxed">
            Organisez vos cotisations de groupe entre commerçantes avec la garantie d'un versement à date fixe.
          </p>
        </div>
        <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-xs font-bold text-brand-navy">
          <span>Tour de rôle garanti</span>
          <span class="text-brand-gold text-base group-hover:translate-x-1 transition-transform">→</span>
        </div>
      </div>

      <!-- Carte 3 -->
      <div class="bg-white rounded-3xl p-8 border border-slate-200/80 shadow-sm hover:shadow-2xl hover:border-brand-gold hover:-translate-y-2 transition-all duration-300 flex flex-col justify-between group reveal-init delay-300">
        <div>
          <div class="w-14 h-14 rounded-2xl bg-brand-navy text-brand-gold font-extrabold flex items-center justify-center text-xl mb-6 shadow-md group-hover:scale-110 group-hover:bg-brand-gold group-hover:text-brand-navy transition-all">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
          </div>
          <span class="text-[10px] font-mono font-bold uppercase tracking-wider text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-md">Mixx, Moov &amp; Flooz</span>
          <h3 class="font-extrabold text-xl text-brand-navy mt-2 group-hover:text-brand-blueAccent transition-colors">
            Mobile Money 24/7
          </h3>
          <p class="text-slate-600 text-sm mt-3 leading-relaxed">
            Déposez vos cotisations et remboursez vos prêts à distance via Mixx by Yas, Moov Money et Flooz.
          </p>
        </div>
        <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-xs font-bold text-brand-navy">
          <span>Paiement à distance</span>
          <span class="text-brand-gold text-base group-hover:translate-x-1 transition-transform">→</span>
        </div>
      </div>

      <!-- Carte 4 -->
      <div class="bg-white rounded-3xl p-8 border border-slate-200/80 shadow-sm hover:shadow-2xl hover:border-brand-gold hover:-translate-y-2 transition-all duration-300 flex flex-col justify-between group reveal-init delay-400">
        <div>
          <div class="w-14 h-14 rounded-2xl bg-brand-navy text-brand-gold font-extrabold flex items-center justify-center text-xl mb-6 shadow-md group-hover:scale-110 group-hover:bg-brand-gold group-hover:text-brand-navy transition-all">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
          </div>
          <span class="text-[10px] font-mono font-bold uppercase tracking-wider text-blue-700 bg-blue-50 px-2.5 py-1 rounded-md">Plafond Évolutif</span>
          <h3 class="font-extrabold text-xl text-brand-navy mt-2 group-hover:text-brand-blueAccent transition-colors">
            Crédit Progressif
          </h3>
          <p class="text-slate-600 text-sm mt-3 leading-relaxed">
            Multipliez votre capacité d'emprunt jusqu'à 5 fois votre montant épargné au fil de votre assiduité.
          </p>
        </div>
        <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-xs font-bold text-brand-navy">
          <span>Multiplié de 1× à 5×</span>
          <span class="text-brand-gold text-base group-hover:translate-x-1 transition-transform">→</span>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ============================================================ -->
<!-- SECTION PALIERS DE CRÉDIT — ANIMATIONS DYNAMIQUES -->
<!-- ============================================================ -->
<section class="bg-white py-16 sm:py-24 border-t border-slate-200/60" id="paliers">
  <div class="max-w-6xl mx-auto px-4">
    
    <div class="text-center max-w-2xl mx-auto mb-12 sm:mb-16 reveal-init">
      <span class="bg-brand-navy text-brand-gold font-extrabold uppercase text-xs px-4 py-1.5 rounded-full inline-block tracking-wider mb-3 shadow-sm">
        GRILLE OFFICIELLE DES PALIERS
      </span>
      <h2 class="font-extrabold text-3xl sm:text-4xl text-brand-navy">
        Progression &amp; Accès au Crédit
      </h2>
      <p class="text-slate-600 text-sm sm:text-base mt-3 leading-relaxed">
        Plus votre historique d'épargne est régulier, plus votre niveau de confiance et votre prêt augmentent.
      </p>
    </div>

    <!-- 4 Cartes Paliers d'Accès -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">

      <!-- PALIER 1 -->
      <div class="bg-slate-50 rounded-3xl p-7 border-2 border-slate-200 hover:border-brand-gold transition-all duration-300 flex flex-col justify-between shadow-sm hover:shadow-xl reveal-init delay-100">
        <div>
          <div class="flex justify-between items-center mb-4">
            <span class="font-mono text-xs font-extrabold uppercase px-3 py-1 bg-slate-200 text-slate-800 rounded-full">Palier 1</span>
            <span class="text-xs font-bold text-slate-500">15 jours</span>
          </div>
          <h3 class="font-extrabold text-xl text-brand-navy">Niveau Découverte</h3>
          <p class="text-slate-600 text-xs mt-2 leading-relaxed">Accès initial rapide dès 15 jours de cotisation régulière.</p>

          <div class="mt-6 p-4 bg-white rounded-2xl border border-slate-200 text-center shadow-inner">
            <div class="text-[10px] font-mono uppercase text-slate-400 font-bold">Capacité Prêt</div>
            <div class="text-2xl font-extrabold text-emerald-600 font-mono mt-0.5">1× cotisé</div>
          </div>
        </div>
        <div class="mt-6 pt-4 border-t border-slate-200/60 text-xs font-bold text-slate-500 text-center">
          Remboursement sur 1 mois
        </div>
      </div>

      <!-- PALIER 2 -->
      <div class="bg-slate-50 rounded-3xl p-7 border-2 border-slate-200 hover:border-brand-gold transition-all duration-300 flex flex-col justify-between shadow-sm hover:shadow-xl reveal-init delay-200">
        <div>
          <div class="flex justify-between items-center mb-4">
            <span class="font-mono text-xs font-extrabold uppercase px-3 py-1 bg-amber-100 text-amber-900 rounded-full">Palier 2</span>
            <span class="text-xs font-bold text-slate-500">1 mois</span>
          </div>
          <h3 class="font-extrabold text-xl text-brand-navy">Niveau Confiance</h3>
          <p class="text-slate-600 text-xs mt-2 leading-relaxed">Capacité d'emprunt doublée après 1 mois sans incident.</p>

          <div class="mt-6 p-4 bg-white rounded-2xl border border-slate-200 text-center shadow-inner">
            <div class="text-[10px] font-mono uppercase text-slate-400 font-bold">Capacité Prêt</div>
            <div class="text-2xl font-extrabold text-emerald-600 font-mono mt-0.5">2× cotisé</div>
          </div>
        </div>
        <div class="mt-6 pt-4 border-t border-slate-200/60 text-xs font-bold text-slate-500 text-center">
          Remboursement sur 2 mois
        </div>
      </div>

      <!-- PALIER 3 -->
      <div class="bg-slate-50 rounded-3xl p-7 border-2 border-brand-gold bg-amber-50/20 shadow-md hover:shadow-xl transition-all duration-300 flex flex-col justify-between reveal-init delay-300">
        <div>
          <div class="flex justify-between items-center mb-4">
            <span class="font-mono text-xs font-extrabold uppercase px-3 py-1 bg-brand-gold text-brand-navy rounded-full">Palier 3</span>
            <span class="text-xs font-bold text-amber-800">3 mois</span>
          </div>
          <h3 class="font-extrabold text-xl text-brand-navy">Niveau Fidélité</h3>
          <p class="text-slate-600 text-xs mt-2 leading-relaxed">Crédit triplé avec taux préférentiel réduit.</p>

          <div class="mt-6 p-4 bg-white rounded-2xl border border-amber-200 text-center shadow-inner">
            <div class="text-[10px] font-mono uppercase text-slate-400 font-bold">Capacité Prêt</div>
            <div class="text-2xl font-extrabold text-emerald-600 font-mono mt-0.5">3× cotisé</div>
          </div>
        </div>
        <div class="mt-6 pt-4 border-t border-amber-200/60 text-xs font-extrabold text-amber-800 text-center">
          Remboursement sur 3 mois
        </div>
      </div>

      <!-- PALIER 4 -->
      <div class="bg-brand-navy text-white rounded-3xl p-7 border-2 border-brand-gold shadow-2xl flex flex-col justify-between relative overflow-hidden reveal-init delay-400">
        <div>
          <div class="flex justify-between items-center mb-4">
            <span class="font-mono text-xs font-extrabold uppercase px-3 py-1 bg-brand-gold text-brand-navy rounded-full">Palier 4</span>
            <span class="text-xs font-bold text-brand-gold">6 mois+</span>
          </div>
          <h3 class="font-extrabold text-xl text-brand-gold">Membre d'Honneur</h3>
          <p class="text-slate-300 text-xs mt-2 leading-relaxed">Plafond maximal accordé sans aucune garantie bancaire.</p>

          <div class="mt-6 p-4 bg-white/10 rounded-2xl border border-white/20 text-center backdrop-blur-sm shadow-inner">
            <div class="text-[10px] font-mono uppercase text-slate-300 font-bold">Capacité Prêt</div>
            <div class="text-2xl font-extrabold text-brand-gold font-mono mt-0.5">Jusqu'à 5× cotisé</div>
          </div>
        </div>
        <div class="mt-6 pt-4 border-t border-white/20 text-xs font-bold text-slate-200 text-center">
          Remboursement jusqu'à 6 mois
        </div>
      </div>

    </div>

    <!-- BANNIÈRE CAUTION SOLIDAIRE -->
    <div class="mt-12 bg-gradient-to-r from-brand-navy to-brand-navyDark text-white rounded-3xl p-8 sm:p-12 shadow-2xl border border-brand-gold/30 reveal-init delay-200">
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
        <div class="lg:col-span-8">
          <span class="font-mono text-xs uppercase tracking-widest text-brand-gold font-bold">Principe d'Entraide Locale</span>
          <h3 class="font-extrabold text-2xl sm:text-3xl text-white mt-1.5">La Caution Solidaire de Groupe</h3>
          <p class="text-slate-300 text-sm sm:text-base mt-3 leading-relaxed">
            Pour éviter les garanties financières lourdes, FSD-YAYRA s'appuie sur la force du groupe : 5 à 10 cotisants se portent garant moralement les uns des autres pour débloquer les crédits en toute sérénité.
          </p>
        </div>
        <div class="lg:col-span-4 lg:text-right">
          <a href="https://wa.me/22892814161?text=Bonjour%20FSD-YAYRA%2C%20je%20souhaite%20des%20informations%20sur%20la%20Caution%20Solidaire." target="_blank" class="inline-block bg-brand-gold text-brand-navy font-extrabold text-xs uppercase tracking-wider px-8 py-4 rounded-full hover:bg-yellow-300 transition-colors shadow-lg">
            REJOINDRE UN GROUPE →
          </a>
        </div>
      </div>
    </div>

  </div>
</section>

<!-- ============================================================ -->
<!-- NOUVELLE SECTION TÉMOIGNAGES DE MEMBRES (PREUVE SOCIALE & CONFIANCE) -->
<!-- ============================================================ -->
<section class="bg-slate-50 py-16 sm:py-24 border-t border-slate-200/60" id="temoignages">
  <div class="max-w-6xl mx-auto px-4">
    
    <div class="text-center max-w-2xl mx-auto mb-12 sm:mb-16 reveal-init">
      <span class="bg-brand-gold text-brand-navy font-extrabold uppercase text-xs px-4 py-1.5 rounded-full inline-block tracking-wider mb-3 shadow-sm">
        TÉMOIGNAGES DE NOS MEMBRES
      </span>
      <h2 class="font-extrabold text-3xl sm:text-4xl text-brand-navy">
        Ils témoignent de leur expérience
      </h2>
      <p class="text-slate-600 text-sm sm:text-base mt-3 leading-relaxed">
        Des commerçantes et artisans togolais partagent comment FSD-YAYRA a propulsé leur activité.
      </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
      
      <!-- Témoignage 1 -->
      <div class="bg-white rounded-3xl p-8 border border-slate-200/80 shadow-sm hover:shadow-2xl hover:border-brand-gold hover:-translate-y-2 transition-all duration-300 flex flex-col justify-between reveal-init delay-100">
        <div>
          <div class="flex items-center gap-1 text-amber-400 mb-4">
            ★★★★★
          </div>
          <p class="text-slate-700 text-sm italic leading-relaxed">
            « Grâce à mon carnet d'épargne FSD-YAYRA, j'ai pu passer du Palier 1 au Palier 3 en 4 mois. J'ai débloqué 300 000 FCFA pour doubler mon stock de pagnes avant les fêtes. »
          </p>
        </div>
        <div class="mt-6 pt-4 border-t border-slate-100 flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-brand-gold text-brand-navy font-extrabold flex items-center justify-center">
            AG
          </div>
          <div>
            <div class="font-extrabold text-sm text-brand-navy">Maman Ablavi G.</div>
            <div class="text-xs text-slate-500">Revendeuse · Marché Assigamé</div>
          </div>
        </div>
      </div>

      <!-- Témoignage 2 -->
      <div class="bg-white rounded-3xl p-8 border border-slate-200/80 shadow-sm hover:shadow-2xl hover:border-brand-gold hover:-translate-y-2 transition-all duration-300 flex flex-col justify-between reveal-init delay-200">
        <div>
          <div class="flex items-center gap-1 text-amber-400 mb-4">
            ★★★★★
          </div>
          <p class="text-slate-700 text-sm italic leading-relaxed">
            « Les agents passent chaque matin à mon atelier de menuiserie pour récupérer ma cotisation. Plus besoin de fermer ma boutique pour aller en banque ! »
          </p>
        </div>
        <div class="mt-6 pt-4 border-t border-slate-100 flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-brand-navy text-brand-gold font-extrabold flex items-center justify-center">
            KT
          </div>
          <div>
            <div class="font-extrabold text-sm text-brand-navy">Koffi T.</div>
            <div class="text-xs text-slate-500">Artisan Menuisier · Hanoukopé</div>
          </div>
        </div>
      </div>

      <!-- Témoignage 3 -->
      <div class="bg-white rounded-3xl p-8 border border-slate-200/80 shadow-sm hover:shadow-2xl hover:border-brand-gold hover:-translate-y-2 transition-all duration-300 flex flex-col justify-between reveal-init delay-300">
        <div>
          <div class="flex items-center gap-1 text-amber-400 mb-4">
            ★★★★★
          </div>
          <p class="text-slate-700 text-sm italic leading-relaxed">
            « Avec la caution solidaire de notre groupe de 5 commerçantes, nous avons obtenu notre financement groupé sans hypothèques ni banquiers compliqués. »
          </p>
        </div>
        <div class="mt-6 pt-4 border-t border-slate-100 flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-emerald-500 text-white font-extrabold flex items-center justify-center">
            GS
          </div>
          <div>
            <div class="font-extrabold text-sm text-brand-navy">Groupe Solidarité</div>
            <div class="text-xs text-slate-500">5 Membres · Marché Hedzranawoé</div>
          </div>
        </div>
      </div>

    </div>

    <!-- CHIFFRES DE CONFIANCE -->
    <div class="mt-16 grid grid-cols-2 lg:grid-cols-4 gap-6 text-center reveal-init delay-200">
      <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm">
        <div class="font-extrabold text-3xl sm:text-4xl text-brand-navy font-mono">15 000+</div>
        <div class="text-xs text-slate-500 font-bold uppercase tracking-wider mt-1">Cotisants Actifs</div>
      </div>
      <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm">
        <div class="font-extrabold text-3xl sm:text-4xl text-emerald-600 font-mono">98.4%</div>
        <div class="text-xs text-slate-500 font-bold uppercase tracking-wider mt-1">Taux de Satisfaction</div>
      </div>
      <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm">
        <div class="font-extrabold text-3xl sm:text-4xl text-brand-gold font-mono">4 Paliers</div>
        <div class="text-xs text-slate-500 font-bold uppercase tracking-wider mt-1">Crédit Évolutif</div>
      </div>
      <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm">
        <div class="font-extrabold text-3xl sm:text-4xl text-brand-navy font-mono">100%</div>
        <div class="text-xs text-slate-500 font-bold uppercase tracking-wider mt-1">Reçus Sécurisés</div>
      </div>
    </div>

  </div>
</section>

<!-- ============================================================ -->
<!-- SECTION COMMENT ÇA MARCHE — ANIMATIONS DYNAMIQUES -->
<!-- ============================================================ -->
<section class="bg-white py-16 sm:py-24 border-t border-slate-200/60" id="adhesion">
  <div class="max-w-6xl mx-auto px-4">
    
    <div class="text-center max-w-2xl mx-auto mb-12 sm:mb-16 reveal-init">
      <span class="bg-brand-gold text-brand-navy font-extrabold uppercase text-xs px-4 py-1.5 rounded-full inline-block tracking-wider mb-3 shadow-sm">
        COMMENT ÇA MARCHE
      </span>
      <h2 class="font-extrabold text-3xl sm:text-4xl text-brand-navy">
        Adhérez &amp; Obtenez Votre Prêt en 3 Étapes
      </h2>
      <p class="text-slate-600 text-sm sm:text-base mt-3 leading-relaxed">
        Une procédure simple et transparente sans paperasserie complexe.
      </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
      
      <!-- Étape 1 -->
      <div class="bg-slate-50 rounded-3xl p-8 border border-slate-200 shadow-sm text-center flex flex-col items-center hover:shadow-xl transition-all reveal-init delay-100">
        <div class="w-16 h-16 rounded-2xl bg-brand-gold text-brand-navy font-extrabold text-3xl flex items-center justify-center mb-6 shadow-md">
          1
        </div>
        <h3 class="font-extrabold text-xl text-brand-navy">Inscription &amp; Carnet</h3>
        <p class="text-slate-600 text-sm mt-3 leading-relaxed">
          Rencontrez un agent de zone au marché ou à l'agence pour ouvrir votre compte et recevoir votre carnet officiel.
        </p>
      </div>

      <!-- Étape 2 -->
      <div class="bg-slate-50 rounded-3xl p-8 border border-slate-200 shadow-sm text-center flex flex-col items-center hover:shadow-xl transition-all reveal-init delay-200">
        <div class="w-16 h-16 rounded-2xl bg-brand-navy text-brand-gold font-extrabold text-3xl flex items-center justify-center mb-6 shadow-md">
          2
        </div>
        <h3 class="font-extrabold text-xl text-brand-navy">Cotisation Régulière</h3>
        <p class="text-slate-600 text-sm mt-3 leading-relaxed">
          Cotisez à votre rythme chaque jour ou semaine par cash auprès de nos agents ou en ligne via Mobile Money (Mixx, Moov, Flooz).
        </p>
      </div>

      <!-- Étape 3 -->
      <div class="bg-slate-50 rounded-3xl p-8 border border-slate-200 shadow-sm text-center flex flex-col items-center hover:shadow-xl transition-all reveal-init delay-300">
        <div class="w-16 h-16 rounded-2xl bg-emerald-500 text-white font-extrabold text-3xl flex items-center justify-center mb-6 shadow-md">
          3
        </div>
        <h3 class="font-extrabold text-xl text-brand-navy">Déblocage du Prêt</h3>
        <p class="text-slate-600 text-sm mt-3 leading-relaxed">
          Dès 15 jours de régularité, faites votre demande. Le montant estimé est débloqué directement sur votre compte.
        </p>
      </div>

    </div>

  </div>
</section>

<!-- ============================================================ -->
<!-- SIMULATEUR EN DIRECT — WALLET APP STYLE YAS -->
<!-- ============================================================ -->
<section class="bg-brand-navy text-white py-16 sm:py-24" id="simulateur">
  <div class="max-w-6xl mx-auto px-4">
    
    <div class="text-center max-w-2xl mx-auto mb-12 reveal-init">
      <span class="bg-brand-gold text-brand-navy font-extrabold uppercase text-xs px-4 py-1.5 rounded-full inline-block tracking-wider mb-3 shadow-sm">
        SIMULATEUR EN DIRECT
      </span>
      <h2 class="font-extrabold text-3xl sm:text-4xl text-white">
        Estimez Votre Capacité d'Emprunt
      </h2>
      <p class="text-slate-300 text-sm sm:text-base mt-3">
        Indiquez votre montant de cotisation et votre palier pour voir votre crédit disponible instantanément.
      </p>
    </div>

    <!-- Carte Calculateur Style App Mobile Money -->
    <div class="max-w-3xl mx-auto bg-brand-navyDark border-2 border-brand-gold/40 rounded-3xl p-6 sm:p-10 shadow-2xl reveal-init delay-200">
      
      <!-- Input Epargne -->
      <div class="mb-8">
        <label for="inputEpargne" class="block font-mono text-xs font-bold uppercase tracking-wider text-slate-300 mb-2">
          Montant Total Cotisé (FCFA)
        </label>
        <input type="number" id="inputEpargne" value="50000" min="5000" step="5000" class="w-full bg-transparent border-b-2 border-brand-gold text-3xl sm:text-5xl font-extrabold text-brand-gold focus:outline-none pb-2 tracking-tight">
        
        <!-- Raccourcis -->
        <div class="flex flex-wrap gap-2 mt-4">
          <button type="button" onclick="setSim(25000)" class="bg-white/10 border border-white/20 text-white font-mono text-xs px-4 py-2 rounded-full hover:bg-brand-gold hover:text-brand-navy transition-all">+ 25 000</button>
          <button type="button" onclick="setSim(50000)" class="bg-white/10 border border-white/20 text-white font-mono text-xs px-4 py-2 rounded-full hover:bg-brand-gold hover:text-brand-navy transition-all">50 000</button>
          <button type="button" onclick="setSim(100000)" class="bg-white/10 border border-white/20 text-white font-mono text-xs px-4 py-2 rounded-full hover:bg-brand-gold hover:text-brand-navy transition-all">100 000</button>
          <button type="button" onclick="setSim(250000)" class="bg-white/10 border border-white/20 text-white font-mono text-xs px-4 py-2 rounded-full hover:bg-brand-gold hover:text-brand-navy transition-all">250 000</button>
        </div>
      </div>

      <!-- Choix Palier -->
      <div class="mb-8">
        <label class="block font-mono text-xs font-bold uppercase tracking-wider text-slate-300 mb-2">
          Sélectionnez Votre Palier d'Ancienneté
        </label>
        <div id="selecteurTiers" class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
          <button type="button" data-tier="1" class="bg-white/10 border border-white/20 text-slate-200 text-xs font-bold p-3 rounded-2xl hover:border-brand-gold transition-all [&.actif]:bg-brand-gold [&.actif]:text-brand-navy [&.actif]:font-extrabold [&.actif]:border-brand-gold">Palier 1 · ×1</button>
          <button type="button" data-tier="2" class="bg-white/10 border border-white/20 text-slate-200 text-xs font-bold p-3 rounded-2xl hover:border-brand-gold transition-all [&.actif]:bg-brand-gold [&.actif]:text-brand-navy [&.actif]:font-extrabold [&.actif]:border-brand-gold">Palier 2 · ×2</button>
          <button type="button" data-tier="3" class="bg-white/10 border border-white/20 text-slate-200 text-xs font-bold p-3 rounded-2xl hover:border-brand-gold transition-all [&.actif]:bg-brand-gold [&.actif]:text-brand-navy [&.actif]:font-extrabold [&.actif]:border-brand-gold actif">Palier 3 · ×3</button>
          <button type="button" data-tier="4" class="bg-white/10 border border-white/20 text-slate-200 text-xs font-bold p-3 rounded-2xl hover:border-brand-gold transition-all [&.actif]:bg-brand-gold [&.actif]:text-brand-navy [&.actif]:font-extrabold [&.actif]:border-brand-gold">Palier 4 · ×5</button>
        </div>
      </div>

      <!-- Résultat -->
      <div class="pt-6 border-t border-white/15 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <div class="font-mono text-xs uppercase tracking-wider text-slate-400 font-bold">Montant du prêt disponible</div>
          <div id="txtResultat" class="text-3xl sm:text-5xl font-extrabold text-brand-gold mt-1">150 000 FCFA</div>
        </div>
        <a id="linkWa" href="#" target="_blank" class="w-full sm:w-auto text-center bg-brand-navy text-white font-extrabold text-xs uppercase tracking-wider px-8 py-4 rounded-full border-2 border-brand-gold shadow-[0_4px_0_#FFD100] hover:-translate-y-0.5 hover:shadow-[0_6px_0_#FFD100] transition-all">
          ENVOYER LA DEMANDE VIA WHATSAPP
        </a>
      </div>

    </div>
  </div>
</section>

<!-- ============================================================ -->
<!-- NOUVELLE SECTION F.A.Q (TRANSPARENCE & REASSURANCE) -->
<!-- ============================================================ -->
<section class="bg-slate-50 py-16 sm:py-24 border-t border-slate-200/60" id="faq">
  <div class="max-w-4xl mx-auto px-4">
    
    <div class="text-center max-w-2xl mx-auto mb-12 sm:mb-16 reveal-init">
      <span class="bg-brand-gold text-brand-navy font-extrabold uppercase text-xs px-4 py-1.5 rounded-full inline-block tracking-wider mb-3 shadow-sm">
        TRANSPARENCE &amp; F.A.Q
      </span>
      <h2 class="font-extrabold text-3xl sm:text-4xl text-brand-navy">
        Questions Fréquentes
      </h2>
      <p class="text-slate-600 text-sm sm:text-base mt-3 leading-relaxed">
        Tout ce que vous devez savoir sur la cotisation et l’accès au crédit FSD-YAYRA.
      </p>
    </div>

    <div class="space-y-4">
      
      <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-sm reveal-init delay-100">
        <h3 class="font-extrabold text-lg text-brand-navy flex items-center justify-between gap-4">
          <span>Comment se déroule la collecte quotidienne au marché ?</span>
          <span class="text-brand-gold text-xl">?</span>
        </h3>
        <p class="text-slate-600 text-sm mt-3 leading-relaxed">
          Nos agents de zone accrédités passent chaque matin directement à votre étal ou boutique. Chaque versement est inscrit immédiatement sur votre carnet physique et enregistré dans notre système.
        </p>
      </div>

      <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-sm reveal-init delay-200">
        <h3 class="font-extrabold text-lg text-brand-navy flex items-center justify-between gap-4">
          <span>Quelles sont les pièces requises pour ouvrir mon carnet ?</span>
          <span class="text-brand-gold text-xl">?</span>
        </h3>
        <p class="text-slate-600 text-sm mt-3 leading-relaxed">
          Une pièce d’identité valide (Carte d’identité nationale, passeport ou carte d'électeur) et une photo d'identité suffisent pour créer votre dossier en quelques minutes.
        </p>
      </div>

      <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-sm reveal-init delay-300">
        <h3 class="font-extrabold text-lg text-brand-navy flex items-center justify-between gap-4">
          <span>Puis-je cotiser et rembourser par Mobile Money ?</span>
          <span class="text-brand-gold text-xl">?</span>
        </h3>
        <p class="text-slate-600 text-sm mt-3 leading-relaxed">
          Oui ! FSD-YAYRA est partenaire officiel de Mixx by Yas, Moov Money et Flooz. Vous pouvez effectuer vos opérations à toute heure du jour et de la nuit depuis votre téléphone.
        </p>
      </div>

      <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-sm reveal-init delay-400">
        <h3 class="font-extrabold text-lg text-brand-navy flex items-center justify-between gap-4">
          <span>Est-il possible d'emprunter sans garant individuel ?</span>
          <span class="text-brand-gold text-xl">?</span>
        </h3>
        <p class="text-slate-600 text-sm mt-3 leading-relaxed">
          Absolument. Grâce à notre système de Caution Solidaire de Groupe ou au Palier 4 (Membre d'Honneur), vous pouvez débloquer vos crédits sans caution financière lourde.
        </p>
      </div>

    </div>

  </div>
</section>

</main>

<!-- ============================================================ -->
<!-- PIED DE PAGE STYLE CORPORATE YAS TOGO -->
<!-- ============================================================ -->
<footer class="bg-brand-navyDark text-slate-400 py-12 sm:py-16 border-t border-white/10">
  <div class="max-w-6xl mx-auto px-4">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
      
      <!-- Brand info -->
      <div class="md:col-span-2">
        <div class="font-extrabold text-2xl text-white tracking-tight">FSD-YAYRA</div>
        <p class="mt-3 text-sm leading-relaxed max-w-sm">
          Mutuelle d'Épargne et de Crédit rattachée à la <strong>M.I.E</strong> — Mission Internationale d'Évangélisation. Accompagnement financier des populations togolaises.
        </p>
        <div class="mt-4 font-mono text-xs text-brand-gold font-bold">
          « Cotise aujourd'hui, avance demain »
        </div>
      </div>

      <!-- Contacts -->
      <div>
        <div class="font-mono text-xs uppercase tracking-wider text-brand-gold font-bold mb-3">Contacts Officiels</div>
        <p class="text-white font-bold font-mono text-sm">92 81 41 61 · 98 07 24 17</p>
        <p class="mt-1 font-mono text-sm">90 46 98 00 · 90 95 78 30</p>
        <p class="text-xs text-slate-500 mt-2">Lomé, Togo</p>
      </div>

      <!-- Mobile Money -->
      <div>
        <div class="font-mono text-xs uppercase tracking-wider text-brand-gold font-bold mb-3">Mobile Money Togo</div>
        <p class="text-xs text-slate-300 leading-relaxed">
          Versez vos cotisations et remboursez vos prêts par :
        </p>
        <div class="mt-3 space-y-1 font-mono text-xs font-bold text-white">
          <div>• Mixx by Yas</div>
          <div>• Moov Money</div>
          <div>• Flooz</div>
        </div>
      </div>

    </div>

    <div class="mt-12 pt-6 border-t border-white/10 flex justify-between items-center text-xs">
      <div>© 2026 FSD-YAYRA — Direction Générale M.I.E. Tous droits réservés.</div>
      <a href="{{ route('login') }}" class="text-slate-600 hover:text-brand-gold opacity-40 hover:opacity-100 transition-all p-1" title="Accès Administration Staff">
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
      </a>
    </div>
  </div>
</footer>

<script>
function toggleMobileMenu(){
  var m = document.getElementById('mobileMenu');
  if(m) m.classList.toggle('hidden');
}

var curIndex = 0;
var total = 3;
var track = document.getElementById('heroTrackCorp');
var dots = document.querySelectorAll('.dot-item');

function updateCorpSlider(idx){
  if(idx >= total) curIndex = 0;
  else if(idx < 0) curIndex = total - 1;
  else curIndex = idx;

  if(track){
    track.style.transform = 'translateX(-' + (curIndex * (100 / total)) + '%)';
  }
  dots.forEach(function(d, i){
    d.classList.toggle('actif', i === curIndex);
  });
}

function goSlideCorp(idx){ updateCorpSlider(idx); }

setInterval(function(){ updateCorpSlider(curIndex + 1); }, 5000);

/* Simulateur */
function setSim(val){
  var inp = document.getElementById('inputEpargne');
  if(inp){ inp.value = val; calcSim(); }
}

var mults = {1:1, 2:2, 3:3, 4:5};
var curTier = 3;
var btnsT = document.querySelectorAll('#selecteurTiers button');
btnsT.forEach(function(b){
  b.addEventListener('click', function(){
    curTier = parseInt(b.dataset.tier, 10);
    btnsT.forEach(function(x){ x.classList.remove('actif'); });
    b.classList.add('actif');
    calcSim();
  });
});

function calcSim(){
  var ep = parseFloat(document.getElementById('inputEpargne').value) || 0;
  var pr = ep * mults[curTier];
  var txt = document.getElementById('txtResultat');
  if(txt) txt.textContent = pr.toLocaleString('fr-FR') + ' FCFA';

  var msg = encodeURIComponent('Bonjour FSD-YAYRA, je souhaite faire une demande de crédit.\nÉpargne : ' + ep.toLocaleString('fr-FR') + ' FCFA\nPrêt estimé : ' + pr.toLocaleString('fr-FR') + ' FCFA');
  var lk = document.getElementById('linkWa');
  if(lk) lk.href = 'https://wa.me/22892814161?text=' + msg;
}

var inpE = document.getElementById('inputEpargne');
if(inpE) inpE.addEventListener('input', calcSim);
calcSim();

/* IntersectionObserver pour l'animation réactive au défilement */
document.addEventListener("DOMContentLoaded", function () {
  var observerOptions = {
    root: null,
    rootMargin: "0px 0px -50px 0px",
    threshold: 0.12
  };

  var revealObserver = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add("reveal-visible");
      } else {
        entry.target.classList.remove("reveal-visible");
      }
    });
  }, observerOptions);

  document.querySelectorAll(".reveal-init").forEach(function (el) {
    revealObserver.observe(el);
  });
});
</script>
</body>
</html>