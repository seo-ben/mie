<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Client Sécurisé | MIE YAYRA</title>
    
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS Dependencies -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ asset('css/admin-corporate.css') }}">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            overflow: hidden;
        }
        .heading-font {
            font-family: 'Outfit', sans-serif;
        }
        .auth-bg {
            background-image: url('{{ asset('images/client-auth-bg.png') }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        .glass-panel {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 25px 50px -12px rgba(6, 95, 70, 0.15);
        }
        .floating-label-group {
            position: relative;
        }
        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94A3B8;
            transition: color 0.2s;
        }
        .bank-input:focus + .input-icon {
            color: #10B981; /* Emerald Green for Clien Portal */
        }
        .btn-client {
            background: #10B981;
            color: white;
            padding: 1rem;
            border-radius: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            transition: all 0.3s;
            box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.3);
        }
        .btn-client:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(16, 185, 129, 0.4);
        }
    </style>
</head>
<body class="auth-bg min-h-screen flex items-center justify-center p-4">
    
    <!-- Atmospheric Overlay -->
    <div class="fixed inset-0 bg-emerald-900/10 pointer-events-none"></div>

    <div class="relative w-full max-w-[440px] z-10 animate-fade-in-up">
        <!-- Logo / Brand Section -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-3xl shadow-xl shadow-emerald-500/10 mb-4 border border-emerald-50">
                <i class="fas fa-hand-holding-dollar text-3xl text-emerald-600"></i>
            </div>
            <h1 class="text-3xl font-black text-slate-900 heading-font tracking-tight mb-2">MIE YAYRA</h1>
            <p class="text-emerald-700 text-xs font-black uppercase tracking-[0.2em]">Votre Espace Personnel de Trésorerie</p>
        </div>

        <!-- Login Card -->
        <div class="glass-panel p-8 md:p-10 rounded-[2.5rem]">
            <div class="mb-8">
                <h2 class="text-xl font-bold text-slate-900 heading-font">Bienvenue</h2>
                <p class="text-slate-500 text-xs font-medium uppercase tracking-wider mt-1">Gérez vos avoirs en toute sécurité</p>
            </div>

            <!-- Notifications -->
            <!-- Notifications (Gérés par Toast JS) -->

            @if ($errors->any())
                <div class="mb-6 p-4 bg-rose-50 border border-rose-100 rounded-2xl">
                    <div class="flex items-center gap-3 mb-2">
                        <i class="fas fa-circle-exclamation text-rose-500"></i>
                        <p class="text-[10px] font-black text-rose-800 uppercase tracking-widest">Erreur d'Identification</p>
                    </div>
                    <ul class="space-y-1">
                        @foreach ($errors->all() as $error)
                            <li class="text-[11px] font-bold text-rose-600 uppercase tracking-tight">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('client.login') }}" class="space-y-6">
                @csrf
                
                <!-- Client Reference -->
                <div class="space-y-2">
                    <label for="client_number" class="text-[10px] font-black text-slate-400 uppercase tracking-[0.1em] ml-1">Numéro Adhérent</label>
                    <div class="floating-label-group">
                        <input type="text" name="client_number" id="client_number" value="{{ old('client_number') }}" 
                               class="bank-input !pl-12 !text-xs font-bold text-slate-700 border-slate-200" 
                               placeholder="REF-XXXX-XXXX" required autofocus>
                        <i class="fas fa-id-card-clip input-icon"></i>
                    </div>
                </div>

                <!-- Secret Key -->
                <div class="space-y-2">
                    <div class="flex justify-between items-center ml-1">
                        <label for="password" class="text-[10px] font-black text-slate-400 uppercase tracking-[0.1em]">Clef de Sécurité</label>
                        <a href="{{ route('client.password.request') }}" class="text-[9px] font-black text-emerald-600 uppercase tracking-widest hover:text-emerald-800 transition">Clef oubliée ?</a>
                    </div>
                    <div class="floating-label-group">
                        <input type="password" name="password" id="password" 
                               class="bank-input !pl-12 !text-xs font-bold text-slate-700 border-slate-200" 
                               placeholder="••••••••••••" required>
                        <i class="fas fa-user-lock input-icon"></i>
                    </div>
                </div>

                <!-- Remember Me -->
                <div class="flex items-center pl-1">
                    <label class="relative flex items-center cursor-pointer group">
                        <input type="checkbox" name="remember" id="remember" class="sr-only peer">
                        <div class="w-4 h-4 border-2 border-slate-300 rounded peer-checked:bg-emerald-500 peer-checked:border-emerald-500 transition-all flex items-center justify-center">
                            <i class="fas fa-check text-[8px] text-white opacity-0 peer-checked:opacity-100 transition-opacity"></i>
                        </div>
                        <span class="ml-2 text-[11px] font-extrabold text-slate-500 uppercase tracking-tight group-hover:text-emerald-700 transition">Mémoriser cet Accès</span>
                    </label>
                </div>

                <!-- Access Trigger -->
                <div class="pt-2">
                    <button type="submit" class="btn-client w-full group flex items-center justify-center gap-3">
                        <span>Ouvrir ma Session</span>
                        <i class="fas fa-lock-open text-[10px] group-hover:scale-110 transition-transform"></i>
                    </button>
                </div>
            </form>

            <div class="mt-8 pt-6 border-t border-slate-100 text-center">
                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-tight">
                    Nouveau membre ? 
                    <a href="{{ route('client.register') }}" class="text-emerald-600 hover:text-emerald-800 font-black ml-1 underline decoration-2 underline-offset-4 decoration-emerald-100 hover:decoration-emerald-500 transition-all">Créer un Dossier</a>
                </p>
            </div>
        </div>

        <!-- External Trust Indicators -->
        <div class="mt-10 flex flex-col items-center space-y-4">
            <div class="flex gap-8">
                <div class="flex flex-col items-center">
                    <i class="fas fa-shield-halved text-emerald-600 text-xl mb-1 opacity-40"></i>
                    <span class="text-[8px] font-black text-slate-400 uppercase tracking-[0.2em]">SSL Secured</span>
                </div>
                <div class="flex flex-col items-center">
                    <i class="fas fa-fingerprint text-emerald-600 text-xl mb-1 opacity-40"></i>
                    <span class="text-[8px] font-black text-slate-400 uppercase tracking-[0.2em]">Encrypted Data</span>
                </div>
            </div>
            <p class="text-slate-400 text-[9px] font-bold uppercase tracking-[0.2em]">
                &copy; {{ date('Y') }} MIE YAYRA • Banque de Proximité Numérique
            </p>
        </div>
    </div>

    <!-- Animation Keyframes -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    animation: {
                        'fade-in-up': 'fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards',
                    },
                    keyframes: {
                        fadeInUp: {
                            '0%': { opacity: '0', transform: 'translateY(10px) scale(0.98)' },
                            '100%': { opacity: '1', transform: 'translateY(0) scale(1)' },
                        }
                    }
                }
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 4000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });

        // Notifications
        @if (session('success'))
            document.addEventListener('DOMContentLoaded', function() {
                Toast.fire({
                    icon: 'success',
                    title: 'Accès Client',
                    text: "{{ session('success') }}"
                });
            });
        @endif

        @if (session('error'))
            document.addEventListener('DOMContentLoaded', function() {
                Toast.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: "{{ session('error') }}"
                });
            });
        @endif

        @if ($errors->any())
            document.addEventListener('DOMContentLoaded', function() {
                Toast.fire({
                    icon: 'error',
                    title: 'Erreur de Connexion',
                    text: "Veuillez vérifier vos identifiants."
                });
            });
        @endif
    </script>
</body>
</html>
