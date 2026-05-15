<meta name="theme-color" content="#081830">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Sysnettechs POS">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<link rel="manifest" href="{{ asset('manifest.json') }}">
<link rel="apple-touch-icon" href="{{ asset('pwa/icon-192.png') }}">

<script>
    if ('serviceWorker' in navigator && (window.location.protocol === 'https:' || window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1')) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register("{{ asset('service-worker.js') }}").catch(function(error) {
                if (window.console) {
                    console.warn('PWA service worker registration failed:', error);
                }
            });
        });
    }
</script>
