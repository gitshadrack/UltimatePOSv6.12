const STATIC_CACHE = 'sysnettechs-pos-static-v22';
const USER_CACHE_PREFIX = 'pos_cache_biz_';
const APP_BASE_PATH = new URL('./', self.location.href).pathname.replace(/\/$/, '');
const appUrl = path => APP_BASE_PATH + '/' + String(path || '').replace(/^\//, '');
const POS_SHELL_URL = appUrl('sells/pos/create');
const STATIC_ASSETS = [
  '/manifest.json',
  '/pwa/icon-192.png',
  '/pwa/icon-512.png',
  '/pwa/sysnettechs-logo.png',
  '/favicon.ico',
  '/css/tailwind/app.css',
  '/css/vendor.css',
  '/css/app.css',
  '/js/vendor.js',
  '/js/lang/en.js',
  '/js/functions.js',
  '/js/common.js',
  '/js/app.js',
  '/js/pos.js',
  '/js/printer.js',
  '/js/product.js',
  '/js/opening_stock.js'
].map(appUrl);

self.addEventListener('install', event => {
  event.waitUntil(caches.open(STATIC_CACHE).then(cache => cache.addAll(STATIC_ASSETS)));
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  event.waitUntil(caches.keys().then(keys => Promise.all(
    keys.filter(key =>
      key !== STATIC_CACHE &&
      (key.startsWith('sysnettechs-pos-pwa-') || key.startsWith('sysnettechs-pos-static-'))
    ).map(key => caches.delete(key))
  )));
  self.clients.claim();
});

const clientContexts = new Map();

function userCacheName(context) {
  return USER_CACHE_PREFIX + context.businessId + '_user_' + context.userId;
}

function rememberContext(context) {
  return caches.open(STATIC_CACHE).then(cache => cache.put('/__pos_last_context__', new Response(JSON.stringify(context))));
}

function lastContext() {
  return caches.open(STATIC_CACHE).then(cache => cache.match('/__pos_last_context__')).then(response =>
    response ? response.json() : null
  );
}

function purgeContext(context) {
  if (!context) return Promise.resolve();
  for (const [clientId, clientContext] of clientContexts.entries()) {
    if (clientContext.businessId === context.businessId && clientContext.userId === context.userId) {
      clientContexts.delete(clientId);
    }
  }
  return Promise.all([
    caches.delete(userCacheName(context)),
    caches.open(STATIC_CACHE).then(cache => cache.delete('/__pos_last_context__'))
  ]);
}

self.addEventListener('message', event => {
  const data = event.data || {};
  if (data.type === 'SET_POS_CACHE_CONTEXT' && event.source && data.businessId && data.userId) {
    const context = {businessId: String(data.businessId), userId: String(data.userId)};
    clientContexts.set(event.source.id, context);
    event.waitUntil(rememberContext(context));
  }
  if (data.type === 'PURGE_POS_USER_CACHE' && data.businessId && data.userId) {
    const context = {businessId: String(data.businessId), userId: String(data.userId)};
    event.waitUntil(purgeContext(context));
  }
});

self.addEventListener('fetch', event => {
  const request = event.request;

  if (request.method !== 'GET') {
    return;
  }

  const url = new URL(request.url);
  if (url.origin !== self.location.origin) {
    return;
  }

  const context = clientContexts.get(event.clientId) || clientContexts.get(event.resultingClientId);
  const isPosShell = url.pathname === POS_SHELL_URL || url.pathname === appUrl('sells/create') || url.pathname === appUrl('pos/create');
  const isStaticAsset = /\.(?:css|js|png|jpg|jpeg|gif|svg|ico|woff2?|ttf|eot)$/i.test(url.pathname);

  if (request.mode === 'navigate' && (isPosShell || context)) {
    event.respondWith((context ? Promise.resolve(context) : lastContext()).then(activeContext =>
      fetch(request).then(response => {
        const expectedContext = activeContext
          ? 'biz_' + activeContext.businessId + '_user_' + activeContext.userId
          : '';
        if (activeContext && response.status === 200 && isPosShell &&
            response.headers.get('X-POS-Cache-Context') === expectedContext) {
          caches.open(userCacheName(activeContext)).then(cache => cache.put(POS_SHELL_URL, response.clone()));
        }
        if (activeContext && (url.pathname.endsWith('/logout') ||
            (isPosShell && response.headers.get('X-POS-Cache-Context') !== expectedContext))) {
          return purgeContext(activeContext).then(() => response, () => response);
        }
        return response;
      }).catch(() => activeContext
        ? caches.open(userCacheName(activeContext)).then(cache => cache.match(POS_SHELL_URL)).then(cached => cached || Response.error())
        : Response.error()
      )
    ));
    return;
  }

  // The IndexedDB catalogue is the user-scoped data cache. Never put
  // dynamic POS fragments or JSON into Cache Storage.
  if (url.pathname.startsWith(appUrl('sells/pos/'))) return;

  if (isStaticAsset || STATIC_ASSETS.includes(url.pathname)) {
    event.respondWith(fetch(request).then(response => {
      if (response && response.status === 200) {
        const responseToCache = response.clone();
        caches.open(STATIC_CACHE).then(cache => cache.put(request, responseToCache));
      }
      return response;
    }).catch(() => caches.match(request, {ignoreSearch: true})));
  }
});
