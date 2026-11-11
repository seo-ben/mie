<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion | FSD YAYRA</title>
    
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            background-color: #6ba6ed;
            background-image: url('{{ asset('images/clouds-aerial.jpg') }}');
            background-size: cover;
            background-position: center top;
            background-repeat: no-repeat;
        }

        /* Overlay atmosphérique pour sublimer les contrastes */
        .sky-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(
                180deg, 
                rgba(96, 165, 250, 0.15) 0%, 
                rgba(255, 255, 255, 0.05) 50%, 
                rgba(255, 255, 255, 0.25) 100%
            );
            pointer-events: none;
            z-index: 1;
        }

        /* Cercles concentriques décoratifs (effet radar / onde en arrière-plan) */
        .radar-circles {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 100vw;
            height: 100vh;
            pointer-events: none;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .radar-circle {
            position: absolute;
            border-radius: 50%;
            border: 1px solid rgba(255, 255, 255, 0.28);
            box-shadow: 0 0 40px rgba(255, 255, 255, 0.15);
        }

        .radar-circle:nth-child(1) { width: 520px; height: 520px; }
        .radar-circle:nth-child(2) { width: 780px; height: 780px; border-color: rgba(255, 255, 255, 0.22); }
        .radar-circle:nth-child(3) { width: 1080px; height: 1080px; border-color: rgba(255, 255, 255, 0.16); }
        .radar-circle:nth-child(4) { width: 1420px; height: 1420px; border-color: rgba(255, 255, 255, 0.1); }

        /* Conteneur principal */
        .auth-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 410px;
            padding: 20px;
        }

        /* Carte Glassmorphism Réplique exacte */
        .glass-card {
            background: rgba(255, 255, 255, 0.42);
            backdrop-filter: blur(20px) saturate(160%);
            -webkit-backdrop-filter: blur(20px) saturate(160%);
            border: 1.5px solid rgba(255, 255, 255, 0.92);
            border-radius: 26px;
            padding: 38px 34px 30px 34px;
            box-shadow: 
                0 25px 50px -12px rgba(15, 23, 42, 0.12),
                0 0 0 1px rgba(255, 255, 255, 0.4) inset;
        }

        /* Badge Logo au sommet */
        .card-logo-badge {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #ffffff;
            margin: 0 auto 16px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.08);
            border: 1.5px solid rgba(255, 255, 255, 0.95);
        }

        .card-logo-badge i {
            font-size: 20px;
            color: #111827;
        }

        /* En-tête */
        .header-section {
            text-align: center;
            margin-bottom: 26px;
        }

        .title {
            color: #111827;
            font-size: 1.35rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 4px;
        }

        .subtitle {
            color: #475569;
            font-size: 0.80rem;
            font-weight: 500;
            letter-spacing: -0.01em;
        }

        /* Champs du Formulaire */
        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            display: block;
            color: #1e293b;
            font-size: 0.76rem;
            font-weight: 600;
            margin-bottom: 6px;
            letter-spacing: -0.01em;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .form-control {
            width: 100%;
            height: 42px;
            background: rgba(255, 255, 255, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.75);
            border-radius: 10px;
            padding: 0 14px;
            color: #0f172a;
            font-size: 0.85rem;
            font-family: inherit;
            font-weight: 500;
            outline: none;
            transition: all 0.2s ease;
        }

        .form-control.has-toggle {
            padding-right: 40px;
        }

        .form-control::placeholder {
            color: rgba(100, 116, 139, 0.65);
            font-weight: 400;
            font-size: 0.82rem;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.82);
            border-color: #0f172a;
            box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.08);
        }

        /* Bouton toggle password */
        .toggle-password-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #64748b;
            cursor: pointer;
            font-size: 0.82rem;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s ease;
        }

        .toggle-password-btn:hover {
            color: #0f172a;
        }

        /* Checkbox et Lien de récupération */
        .options-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 14px;
            margin-bottom: 22px;
            font-size: 0.78rem;
        }

        .checkbox-label {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: #334155;
            font-weight: 500;
            cursor: pointer;
            user-select: none;
        }

        .checkbox-label input[type="checkbox"] {
            width: 14px;
            height: 14px;
            border-radius: 4px;
            accent-color: #0f172a;
            cursor: pointer;
        }

        .forgot-link {
            color: #475569;
            font-weight: 500;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .forgot-link:hover {
            color: #0f172a;
            text-decoration: underline;
        }

        /* Bouton de Soumission Noir Premium */
        .btn-sign-in {
            width: 100%;
            height: 44px;
            background: #111827;
            color: #ffffff;
            border: none;
            border-radius: 12px;
            padding: 0 20px;
            font-size: 0.88rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            letter-spacing: -0.01em;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px rgba(17, 24, 39, 0.16);
        }

        .btn-sign-in:hover {
            background: #1f2937;
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(17, 24, 39, 0.24);
        }

        .btn-sign-in:active {
            transform: translateY(0);
        }

        /* Pied de page */
        .footer-prompt {
            text-align: center;
            margin-top: 18px;
            color: #475569;
            font-size: 0.76rem;
            font-weight: 500;
        }

        .footer-prompt a {
            color: #0f172a;
            font-weight: 700;
            text-decoration: none;
            margin-left: 4px;
        }

        .footer-prompt a:hover {
            text-decoration: underline;
        }

        /* Boîte d'erreur */
        .error-alert {
            background: rgba(254, 242, 242, 0.85);
            border: 1px solid rgba(248, 113, 113, 0.6);
            border-radius: 12px;
            padding: 10px 14px;
            margin-bottom: 20px;
            font-size: 0.8rem;
            color: #991b1b;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        @media (max-width: 480px) {
            .auth-container {
                padding: 16px;
            }

            .glass-card {
                padding: 30px 22px 26px 22px;
                border-radius: 24px;
            }

            .options-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Overlay de ciel lumineux -->
    <div class="sky-overlay"></div>

    <!-- Anneaux concentriques radar en arrière-plan -->
    <div class="radar-circles">
        <div class="radar-circle"></div>
        <div class="radar-circle"></div>
        <div class="radar-circle"></div>
        <div class="radar-circle"></div>
    </div>

    <!-- Conteneur Carte Login Glassmorphism -->
    <div class="auth-container">
        <div class="glass-card">
            <!-- Logo Icon Badge -->
            <div class="card-logo-badge">
                <i class="fas fa-seedling"></i>
            </div>

            <!-- Header -->
            <div class="header-section">
                <h1 class="title">Welcome back</h1>
                <p class="subtitle">Please enter your details to sign in.</p>
            </div>

            @if ($errors->any())
                <div class="error-alert">
                    <i class="fas fa-circle-exclamation text-rose-500"></i>
                    <div>
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                
                <!-- Email Field -->
                <div class="form-group">
                    <label for="email" class="form-label">E-Mail Address</label>
                    <div class="input-wrapper">
                        <input type="email" 
                               name="email" 
                               id="email" 
                               value="{{ old('email') }}" 
                               class="form-control" 
                               placeholder="Enter your email..." 
                               required 
                               autofocus 
                               autocomplete="email">
                    </div>
                </div>

                <!-- Password Field -->
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-wrapper">
                        <input type="password" 
                               name="password" 
                               id="password" 
                               class="form-control has-toggle" 
                               placeholder="••••••••••••" 
                               required 
                               autocomplete="current-password">
                        <button type="button" class="toggle-password-btn" id="togglePasswordBtn" title="Afficher/Masquer le mot de passe" tabindex="-1">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Options Row (Remember me + Forgot Password) -->
                <div class="options-row">
                    <label class="checkbox-label" for="remember">
                        <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                        <span>Remember me</span>
                    </label>
                    <a href="{{ route('password.request') }}" class="forgot-link">Forgot password?</a>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-sign-in">
                    Sign in
                </button>
            </form>
        </div>
    </div>

    <!-- Toggle Password Visibility Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggleBtn = document.getElementById('togglePasswordBtn');
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');

            if (toggleBtn && passwordInput && toggleIcon) {
                toggleBtn.addEventListener('click', function () {
                    const isPassword = passwordInput.getAttribute('type') === 'password';
                    passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
                    toggleIcon.classList.toggle('fa-eye', !isPassword);
                    toggleIcon.classList.toggle('fa-eye-slash', isPassword);
                });
            }
        });
    </script>
</body>
</html>
