@php $_brandName = setting('brand_name', 'yClothes'); $_brandLogo = setting('brand_logo'); @endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login — {{ $_brandName }} Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <style>
        body {
            background: #f5f7fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            width: 100%;
            max-width: 420px;
        }
    </style>
</head>
<body>
    <div class="login-card px-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <h4 class="mb-1" style="font-family: var(--font-display); color: var(--color-gold);">
                        @if($_brandLogo)
                        <img src="{{ asset('storage/' . $_brandLogo) }}" alt="{{ $_brandName }}" style="height: 36px; width: auto; display: block; margin: 0 auto 8px;">
                        @endif
                        {{ $_brandName }} Admin
                    </h4>
                    <p class="text-muted small">Masuk untuk mengelola toko</p>
                </div>

                @if($errors->any())
                <div class="alert alert-danger py-2 small">
                    <i class="bi bi-exclamation-circle me-1"></i> {{ $errors->first('email') }}
                </div>
                @endif

                <form action="{{ route('admin.login') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="email" class="form-label small fw-medium">Email</label>
                        <input type="email" name="email" id="email"
                               class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email') }}" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label small fw-medium">Password</label>
                        <input type="password" name="password" id="password"
                               class="form-control @error('password') is-invalid @enderror" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="remember" id="remember" class="form-check-input">
                        <label for="remember" class="form-check-label small">Ingat saya</label>
                    </div>
                    <button type="submit" class="btn btn-primary-gold w-100">Masuk</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
