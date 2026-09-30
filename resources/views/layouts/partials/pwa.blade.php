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
            var serviceWorkerUrl = @json(asset('service-worker.js'));
            navigator.serviceWorker.getRegistrations().then(function(registrations) {
                registrations.forEach(function(existingRegistration) {
                    var activeWorker = existingRegistration.active || existingRegistration.waiting || existingRegistration.installing;
                    if (activeWorker && new URL(activeWorker.scriptURL).pathname !== new URL(serviceWorkerUrl, window.location.href).pathname) {
                        existingRegistration.unregister();
                    }
                });
            });
            navigator.serviceWorker.register(serviceWorkerUrl, {updateViaCache: 'none'}).then(function(registration) {
                registration.update();
                @auth
                var context = {type: 'SET_POS_CACHE_CONTEXT', businessId: @json(session('user.business_id')), userId: @json(auth()->id())};
                function sendContext() {
                    var worker = navigator.serviceWorker.controller || registration.active;
                    if (worker) worker.postMessage(context);
                }
                window.purgePosUserCache = function() {
                    var worker = navigator.serviceWorker.controller || registration.active;
                    if (worker) worker.postMessage(Object.assign({}, context, {type: 'PURGE_POS_USER_CACHE'}));
                };
                sendContext();
                navigator.serviceWorker.addEventListener('controllerchange', sendContext);
                document.addEventListener('click', function(event) {
                    var link = event.target.closest && event.target.closest('a[href]');
                    if (link && new URL(link.href, window.location.href).pathname.endsWith('/logout')) {
                        window.purgePosUserCache();
                    }
                }, true);
                @endauth
            }).catch(function(error) {
                if (window.console) {
                    console.warn('PWA service worker registration failed:', error);
                }
            });
        });
    }
</script>
