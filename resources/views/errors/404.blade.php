<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Halaman Tidak Ditemukan — {{ site_app_name() }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-page-background flex items-center justify-center p-4" style="font-family: 'DM Sans', system-ui, sans-serif;">
    <div class="text-center max-w-md">
        <p class="text-6xl font-bold text-primary mb-2">404</p>
        <h1 class="text-xl font-bold mb-2">Halaman Tidak Ditemukan</h1>
        <p class="text-muted-foreground text-sm mb-6">
            Halaman yang kamu cari mungkin telah dihapus, dipindahkan, atau tidak tersedia.
        </p>
        <a
            href="{{ route('home') }}"
            class="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90 transition-opacity"
        >
            Kembali ke Beranda
        </a>
    </div>
</body>
</html>
