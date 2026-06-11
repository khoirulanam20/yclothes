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
        <script>
        // #region agent log
        (function(){var s=@json($integrations['customBodyScripts']??'');fetch('http://127.0.0.1:7792/ingest/c8298905-a0de-43df-a1c3-eaa382f54638',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'227592'},body:JSON.stringify({sessionId:'227592',runId:'post-fix-chatbot',hypothesisId:'H1-H3',location:'app.blade.php:customBodyScripts',message:'sanitized custom body scripts',data:{hasChatbot:s.includes('chatbot'),hasChatbotJs:s.includes('chatbot.js'),scriptLength:s.length,chatbotBotId:@json($integrations['chatbotBotId']??null)},timestamp:Date.now()})}).catch(function(){});})();
        // #endregion
        </script>
        {!! $integrations['customBodyScripts'] !!}
    @endif
</body>
</html>
