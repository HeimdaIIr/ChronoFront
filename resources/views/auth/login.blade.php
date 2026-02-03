<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChronoFront - Connexion</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #0f1117 0%, #1a1d2e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #e4e4e7;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 2rem;
        }

        .login-card {
            background: #1a1d2e;
            border: 1px solid #2a2d3e;
            border-radius: 16px;
            padding: 3rem 2.5rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }

        .logo {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .logo i {
            font-size: 3.5rem;
            color: #10B981;
            margin-bottom: 1rem;
        }

        .logo h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #e4e4e7;
            margin-bottom: 0.5rem;
        }

        .logo p {
            color: #71717a;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            color: #a1a1aa;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .form-control {
            width: 100%;
            padding: 0.875rem 1rem;
            background: #0f1117;
            border: 1px solid #2a2d3e;
            border-radius: 8px;
            color: #e4e4e7;
            font-size: 1rem;
            transition: all 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: #10B981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .form-control::placeholder {
            color: #52525b;
        }

        .error-message {
            color: #ef4444;
            font-size: 0.85rem;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .remember-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .remember-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .remember-group label {
            color: #a1a1aa;
            font-size: 0.9rem;
            cursor: pointer;
            margin: 0;
        }

        .btn-login {
            width: 100%;
            padding: 1rem;
            background: #10B981;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-login:hover {
            background: #059669;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .footer {
            text-align: center;
            margin-top: 2rem;
            color: #71717a;
            font-size: 0.85rem;
        }

        .footer a {
            color: #10B981;
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo">
                <i class="bi bi-stopwatch-fill"></i>
                <h1>ChronoFront</h1>
                <p>Système de chronométrage professionnel</p>
            </div>

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="form-group">
                    <label for="username">Nom d'utilisateur</label>
                    <input
                        type="text"
                        class="form-control"
                        id="username"
                        name="username"
                        value="{{ old('username') }}"
                        placeholder="Entrez votre nom d'utilisateur"
                        required
                        autofocus>
                    @error('username')
                        <div class="error-message">
                            <i class="bi bi-exclamation-circle"></i>
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input
                        type="password"
                        class="form-control"
                        id="password"
                        name="password"
                        placeholder="Entrez votre mot de passe"
                        required>
                    @error('password')
                        <div class="error-message">
                            <i class="bi bi-exclamation-circle"></i>
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="remember-group">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Se souvenir de moi</label>
                </div>

                <button type="submit" class="btn-login">
                    <i class="bi bi-box-arrow-in-right"></i> Se connecter
                </button>
            </form>
        </div>

        <div class="footer">
            <p>&copy; 2026 ATS Sport - ChronoFront</p>
        </div>
    </div>
</body>
</html>
