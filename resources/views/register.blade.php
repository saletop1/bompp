<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Register - SAP Material Master Converter</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://unpkg.com/@dotlottie/player-component@latest/dist/dotlottie-player.mjs" type="module"></script>
    <style>
        :root {
            --primary-color: #0d6efd;
            --success-color: #198754;
            --danger-color: #dc3545;
            --glass-bg: rgba(75, 74, 74, 0.33);
            --glass-border: 1px solid rgba(255, 255, 255, 0.3);
            --text-light: #ffffff;
            --text-muted: #d7d7d7;
        }

        body {
            background-image: url("{{ asset('images/ainun.jpg') }}");
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }

        .register-container { width: 100%; max-width: 520px; padding: 1.5rem; }
        .register-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: var(--glass-border);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
            border-radius: 20px;
            padding: 2.25rem 2rem;
        }

        .header { text-align: center; margin-bottom: 1.5rem; }
        .title { color: var(--text-light); font-weight: 700; text-shadow: 2px 2px 4px rgba(0,0,0,0.5); }
        .subtitle { color: var(--text-muted); }

        .form-label { color: var(--text-light); font-weight: 600; text-shadow: 1px 1px 3px rgba(0,0,0,0.5); }
        .input-wrap { position: relative; }
        .input-wrap .bi { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: rgba(255,255,255,0.7); z-index: 5; }
        .form-control-glass {
            width: 100%; padding: 0.8rem 1rem 0.8rem 2.7rem; color: var(--text-light);
            background-color: rgba(255,255,255,0.15); border: 2px solid rgba(255,255,255,0.2); border-radius: 10px;
        }
        .form-control-glass::placeholder { color: rgba(255,255,255,0.6); }
        .form-control-glass:focus { background-color: rgba(255,255,255,0.25); border-color: var(--primary-color); box-shadow: 0 0 0 0.25rem rgba(13,110,253,0.25); }

        .btn-primary-glass {
            width: 100%; padding: 0.9rem; border: none; border-radius: 10px; color: var(--text-light);
            background: linear-gradient(135deg, var(--primary-color) 0%, #0b5ed7 100%);
            box-shadow: 0 4px 15px rgba(13,110,253,0.3);
        }
        .btn-primary-glass:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(13,110,253,0.4); }

        .alert { border: 0; border-radius: 10px; padding: 1rem 1.25rem; }
        .alert-danger { background: rgba(220, 53, 69, 0.4); color: var(--text-light); backdrop-filter: blur(5px); -webkit-backdrop-filter: blur(5px); }

        .footer { text-align: center; margin-top: 1rem; color: var(--text-muted); font-size: 0.85rem; text-shadow: 1px 1px 2px rgba(0,0,0,0.5); }

        .login-link { color: var(--text-light); text-decoration: none; }
        .login-link:hover { color: var(--primary-color); text-decoration: underline; }

        @media (max-width: 576px) { .register-card { padding: 1.5rem 1.25rem; } }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="header">
                <dotlottie-player src="{{ asset('animations/Bird.lottie') }}" background="transparent" speed="1" style="width: 160px; height: 160px; margin: 0 auto;" loop autoplay></dotlottie-player>
                <h2 class="title">Create Account</h2>
                <p class="subtitle">SAP Material Master Converter</p>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register.post') }}" id="registerForm">
                @csrf
                <div class="mb-3">
                    <label for="name" class="form-label">Full Name</label>
                    <div class="input-wrap">
                        <i class="bi bi-person-fill"></i>
                        <input type="text" id="name" name="name" class="form-control-glass" placeholder="Enter your full name" value="{{ old('name') }}" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-wrap">
                        <i class="bi bi-envelope-fill"></i>
                        <input type="email" id="email" name="email" class="form-control-glass" placeholder="Enter your email" value="{{ old('email') }}" required autocomplete="email">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-wrap">
                        <i class="bi bi-lock-fill"></i>
                        <input type="password" id="password" name="password" class="form-control-glass" placeholder="Create a strong password" required autocomplete="new-password">
                    </div>
                </div>
                <div class="mb-4">
                    <label for="password_confirmation" class="form-label">Confirm Password</label>
                    <div class="input-wrap">
                        <i class="bi bi-shield-lock-fill"></i>
                        <input type="password" id="password_confirmation" name="password_confirmation" class="form-control-glass" placeholder="Re-type your password" required autocomplete="new-password">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary-glass" id="registerBtn">
                    <span class="spinner-border spinner-border-sm d-none me-2" id="registerSpinner" role="status" aria-hidden="true"></span>
                    <span id="registerBtnText">Create Account</span>
                </button>
            </form>

            <div class="footer mt-3">
                <span>Already have an account? <a class="login-link" href="{{ route('login') }}">Login</a></span>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('registerForm').addEventListener('submit', function() {
            const btn = document.getElementById('registerBtn');
            const spinner = document.getElementById('registerSpinner');
            const text = document.getElementById('registerBtnText');
            btn.disabled = true; spinner.classList.remove('d-none'); text.textContent = 'Creating...';
        });
    </script>
</body>
</html>