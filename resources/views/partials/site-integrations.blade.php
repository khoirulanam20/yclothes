@php($integrations = site_integrations())
@if(!empty($integrations['siteDescription']))
    <meta name="description" content="{{ $integrations['siteDescription'] }}">
@endif
@if(!empty($integrations['siteKeywords']))
    <meta name="keywords" content="{{ $integrations['siteKeywords'] }}">
@endif
@if(!empty($integrations['ogImageUrl']))
    <meta property="og:image" content="{{ $integrations['ogImageUrl'] }}">
@endif
@if(!empty($integrations['ogTitle']))
    <meta property="og:title" content="{{ $integrations['ogTitle'] }}">
@endif
@if(!empty($integrations['appName']))
    <meta property="og:site_name" content="{{ $integrations['appName'] }}">
@endif
@if(!empty($integrations['siteDescription']))
    <meta property="og:description" content="{{ $integrations['siteDescription'] }}">
@endif
@if(!empty($integrations['faviconUrl']))
    <link rel="icon" href="{{ $integrations['faviconUrl'] }}">
@endif
@if(!empty($integrations['googleTagManagerId']))
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','{{ $integrations['googleTagManagerId'] }}');</script>
@endif
@if(!empty($integrations['metaPixelId']))
    <!-- Meta Pixel -->
    <script>
    !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
    n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '{{ $integrations['metaPixelId'] }}');
    fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id={{ $integrations['metaPixelId'] }}&ev=PageView&noscript=1"/></noscript>
@endif
@if(!empty($integrations['customHeadScripts']))
    {!! $integrations['customHeadScripts'] !!}
@endif
