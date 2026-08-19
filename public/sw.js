self.addEventListener('install', event => {
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  self.clients.claim();
});

self.addEventListener('push', function(event) {
  const data = event.data ? event.data.json() : {};
  const title = data.title || '⏰ Будильник';

  event.waitUntil(
    self.registration.showNotification(title, {
      body: data.body || 'Пора! ⏰',
      icon: '/icon-192.png',
      vibrate: [200, 100, 200],
      tag: 'alarm'
    })
  );
});

self.addEventListener('message', event => {
  if (event.data && event.data.type === 'ALARM') {
    self.registration.showNotification('⏰ Будильник', {
      body: event.data.title,
      icon: '/icon-192.png',
      vibrate: [200, 100, 200],
    });
  }
});
