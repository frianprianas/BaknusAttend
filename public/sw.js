const CACHE_NAME = 'baknus-attend-v4';
const urlsToCache = [
  '/images/logo_BG.png',
  '/manifest.json'
];

self.addEventListener('install', event => {
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(urlsToCache))
  );
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cache => {
          if (cache !== CACHE_NAME) {
            console.log('Menghapus PWA cache versi usang: ', cache);
            return caches.delete(cache);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', event => {
  // PWA CacheStorage HANYA mendukung request GET!
  // Request POST, PUT, DELETE (seperti /livewire/update) harus langsung dikirim ke server tanpa di-cache.
  if (event.request.method !== 'GET') {
    return;
  }

  // Strategi NETWORK FIRST untuk file navigasi (HTML)
  if (event.request.mode === 'navigate') {
    event.respondWith(
      fetch(event.request)
        .then(response => {
          if (response && response.ok) {
            const clone = response.clone();
            caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
          }
          return response;
        })
        .catch(() => caches.match(event.request))
    );
    return;
  }

  // Strategi NETWORK FIRST untuk Livewire/AJAX
  if (event.request.url.includes('/livewire/') || event.request.headers.get('X-Livewire')) {
    event.respondWith(fetch(event.request));
    return;
  }

  // Strategi CACHE FIRST untuk asset statis (gambar, CSS, JS)
  event.respondWith(
    caches.match(event.request).then(cached => {
      if (cached) return cached;
      return fetch(event.request).then(response => {
        if (response && response.ok && response.type !== 'opaque') {
          const clone = response.clone();
          caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
        }
        return response;
      }).catch(() => new Response('', { status: 408, statusText: 'Offline' }));
    })
  );
});

// Listener untuk notifikasi klik
self.addEventListener('notificationclick', event => {
  event.notification.close();
  event.waitUntil(
    clients.openWindow('/admin')
  );
});

// Listener untuk notifikasi yang dikirim via Push Server (Admin broadcast)
self.addEventListener('push', event => {
  if (event.data) {
    const data = event.data.json();
    const options = {
      body: data.body,
      icon: data.icon || '/images/logo_BG.png',
      badge: '/images/logo_BG.png',
      vibrate: [200, 100, 200, 100, 200],
      data: {
        url: data.action_url || '/admin'
      }
    };

    event.waitUntil(
      self.registration.showNotification(data.title, options)
    );
  }
});
