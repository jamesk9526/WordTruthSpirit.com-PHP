self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', event => event.waitUntil(self.clients.claim()));

self.addEventListener('push', event => {
  let payload = {};
  try { payload = event.data ? event.data.json() : {}; } catch (_) { payload = { body: event.data?.text() || '' }; }
  const siteRoot = new URL(self.registration.scope);
  const title = payload.title || 'Word Truth Spirit';
  const options = {
    body: payload.body || 'A new reflection is ready to read.',
    icon: payload.icon || new URL('assets/images/logo.png', siteRoot).href,
    badge: payload.badge || new URL('assets/images/logo.png', siteRoot).href,
    data: { url: payload.url || new URL('blog/', siteRoot).href },
  };
  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', event => {
  event.notification.close();
  const destination = event.notification.data?.url || '/blog/';
  event.waitUntil(self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(clients => {
    const existing = clients.find(client => 'focus' in client);
    return existing ? existing.navigate(destination).then(client => client.focus()) : self.clients.openWindow(destination);
  }));
});
