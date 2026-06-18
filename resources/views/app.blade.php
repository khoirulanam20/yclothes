<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php($appName = site_app_name())
    <meta name="app-name" content="{{ $appName }}">
    <title inertia>{{ $appName }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    @include('partials.site-integrations')
    @routes
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.tsx'])
    @inertiaHead
</head>
<body class="min-h-screen">
    @php($integrations = site_integrations())
    @if(!empty($integrations['googleTagManagerId']))
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $integrations['googleTagManagerId'] }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    @endif
    @inertia
    @include('partials.chatbot-embed')
    @if(!empty($integrations['customBodyScripts']))
        {!! $integrations['customBodyScripts'] !!}
    @endif
</body>
</html>
