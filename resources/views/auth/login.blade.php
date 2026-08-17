<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion | MIE YAYRA</title>
    
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
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0a1628 0%, #0d2137 50%, #0a1628 100%);
            overflow: hidden;
            position: relative;
        }

        /* Background technologique */
        .tech-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('{{ asset('images/auth-bg.png') }}');
            background-size: cover;
            background-position: center;
            z-index: 0;
        }

        .tech-background::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(180deg, 
                rgba(10, 22, 40, 0.85) 0%, 
                rgba(13, 33, 55, 0.75) 50%, 
                rgba(10, 22, 40, 0.9) 100%);
        }

        /* Particules flottantes */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
            overflow: hidden;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(59, 130, 246, 0.6);
            border-radius: 50%;
            animation: float 15s infinite ease-in-out;
        }

        .particle:nth-child(1) { left: 10%; top: 20%; animation-delay: 0s; }
        .particle:nth-child(2) { left: 20%; top: 80%; animation-delay: 2s; }
        .particle:nth-child(3) { left: 30%; top: 40%; animation-delay: 4s; }
        .particle:nth-child(4) { left: 40%; top: 60%; animation-delay: 6s; }
        .particle:nth-child(5) { left: 50%; top: 10%; animation-delay: 8s; }
        .particle:nth-child(6) { left: 60%; top: 90%; animation-delay: 10s; }
        .particle:nth-child(7) { left: 70%; top: 30%; animation-delay: 12s; }
        .particle:nth-child(8) { left: 80%; top: 70%; animation-delay: 14s; }
        .particle:nth-child(9) { left: 90%; top: 50%; animation-delay: 1s; }
        .particle:nth-child(10) { left: 5%; top: 55%; animation-delay: 3s; }
        .particle:nth-child(11) { left: 15%; top: 45%; animation-delay: 5s; }
        .particle:nth-child(12) { left: 85%; top: 25%; animation-delay: 7s; }

        @keyframes float {
            0%, 100% {
                transform: translateY(0) translateX(0);
                opacity: 0.3;
            }
            25% {
                transform: translateY(-30px) translateX(15px);
                opacity: 0.8;
            }
            50% {
                transform: translateY(-15px) translateX(-10px);
                opacity: 0.5;
            }
            75% {
                transform: translateY(-40px) translateX(20px);
                opacity: 0.9;
            }
        }

        /* Container principal */
        .auth-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }

        /* Carte glassmorphism */
        .glass-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 16px;
            padding: 40px 35px;
            box-shadow: 
                0 8px 32px rgba(0, 0, 0, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }

        /* Titre */
        .title {
            color: #ffffff;
            font-size: 1.75rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 2.5rem;
            letter-spacing: -0.5px;
            line-height: 1.3;
        }

        /* Groupe de formulaire */
        .form-group {
            margin-bottom: 1.75rem;
            position: relative;
        }

        /* Champs de saisie */
        .form-control {
            width: 100%;
            background: transparent;
            border: none;
            border-bottom: 1px solid rgba(255, 255, 255, 0.4);
            padding: 12px 0;
            color: #ffffff;
            font-size: 0.95rem;
            font-family: inherit;
            outline: none;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            border-bottom-color: rgba(255, 255, 255, 0.8);
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.6);
            font-style: italic;
        }

        /* Groupe checkbox et lien */
        .checkbox-group {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 0.5rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 10px;
        }

        .checkbox-label {
            color: #ffffff;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            cursor: pointer;
        }

        .checkbox-label input[type="checkbox"] {
            width: 16px;
            height: 16px;
            margin-right: 8px;
            accent-color: #3b82f6;
            cursor: pointer;
        }

        .forgot-link {
            color: #ffffff;
            font-size: 0.85rem;
            text-decoration: none;
            transition: opacity 0.3s ease;
        }

        .forgot-link:hover {
            opacity: 0.8;
            text-decoration: underline;
        }

        /* Bouton de connexion */
        .btn-submit {
            width: 100%;
            background: #ffffff;
            color: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 14px 20px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 1rem;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }

        .btn-submit:hover {
            background: #f1f5f9;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .btn-submit:active {
            transform: translateY(0);
        }


        /* Messages d'erreur */
        .error-box {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.4);
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 1.5rem;
        }

        .error-box p {
            color: #fca5a5;
            font-size: 0.875rem;
            margin: 0;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .auth-container {
                padding: 15px;
            }

            .glass-card {
                padding: 30px 25px;
            }

            .title {
                font-size: 1.5rem;
                margin-bottom: 2rem;
            }

            .checkbox-group {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <!-- Fond technologique -->
    <div class="tech-background"></div>

    <!-- Particules décoratives -->
    <div class="particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <div class="auth-container">
        <div class="glass-card">
            <h1 class="title">Connexion <br>YAYRA</h1>

            @if ($errors->any())
                <div class="error-box">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                
                <div class="form-group">
                    <input type="email" name="email" id="email" value="{{ old('email') }}" 
                           class="form-control" placeholder="Entrez votre email" required autofocus>
                </div>

                <div class="form-group">
                    <input type="password" name="password" id="password" 
                           class="form-control" placeholder="Entrez votre mot de passe" required>
                </div>

                <div class="checkbox-group">
                    <label class="checkbox-label" for="remember">
                        <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                        Se souvenir de moi
                    </label>
                    <a href="{{ route('password.request') }}" class="forgot-link">Mot de passe oublié ?</a>
                </div>

                <button type="submit" class="btn-submit">
                    Se Connecter
                </button>

            </form>
        </div>
    </div>

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
                    title: 'Bienvenue',
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

