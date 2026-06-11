@php($botId = chatbot_bot_id())
@php($widgetBase = rtrim((string) config('services.chatbot.base_url'), '/'))
@if($botId)
<script>
(function () {
    'use strict';

    var botId = @json($botId);
    var widgetBase = @json($widgetBase);
    var versionUrl = @json(route('embed.chatbot.version'));

    if (document.querySelector('script[data-cb-widget-loaded]')) {
        return;
    }

    function loadWidget(version) {
        var el = document.createElement('script');
        el.src = widgetBase + '/chatbot-widget.js?v=' + encodeURIComponent(version);
        el.setAttribute('data-bot-id', botId);
        el.setAttribute('data-cb-widget-loaded', '1');
        el.defer = true;
        document.head.appendChild(el);
    }

    // #region agent log
    fetch('http://127.0.0.1:7792/ingest/c8298905-a0de-43df-a1c3-eaa382f54638',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'227592'},body:JSON.stringify({sessionId:'227592',runId:'post-fix-chatbot',hypothesisId:'H1',location:'chatbot-embed.blade.php',message:'chatbot embed loader started',data:{versionUrl:versionUrl,widgetBase:widgetBase},timestamp:Date.now()})}).catch(function(){});
    // #endregion

    fetch(versionUrl, { cache: 'no-store', credentials: 'same-origin' })
        .then(function (res) {
            if (!res.ok) {
                throw new Error('version fetch failed');
            }

            return res.text();
        })
        .then(function (version) {
            loadWidget(version.trim());
        })
        .catch(function () {
            loadWidget(String(Date.now()));
        });
})();
</script>
@endif
