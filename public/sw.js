const CACHE_NAME = 'baknus-attend-v1';
const urlsToCache = [
  '/',
  '/admin',
  '/images/logo_BG.png',
  '/manifest.json'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(urlsToCache))
  );
});

self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        if (response) return response;
        return fetch(event.request);
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
