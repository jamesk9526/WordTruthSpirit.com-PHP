(() => {
  const script = document.currentScript;
  const controls = [...document.querySelectorAll('[data-push-opt-in]')];
  if (!script || !controls.length) return;
  if (!window.isSecureContext || !('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
    controls.forEach(control => {
      const status = control.querySelector('[data-push-status]');
      const button = control.querySelector('[data-push-enable]');
      if (status) status.textContent = 'Browser alerts are not supported in this browser.';
      if (button) { button.textContent = 'Alerts unavailable'; button.disabled = true; }
    });
    return;
  }

  const publicKey = script.dataset.pushPublicKey;
  const subscribeUrl = script.dataset.pushSubscribeUrl;
  const serviceWorkerUrl = script.dataset.pushServiceWorker;
  const decodeKey = value => {
    const padding = '='.repeat((4 - value.length % 4) % 4);
    const base64 = (value + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = atob(base64);
    return Uint8Array.from(raw, character => character.charCodeAt(0));
  };

  const setStatus = message => controls.forEach(control => {
    const status = control.querySelector('[data-push-status]');
    if (status) status.textContent = message;
  });

  const sendSubscription = subscription => fetch(subscribeUrl, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'same-origin',
    body: JSON.stringify({ subscription: subscription.toJSON() }),
  }).then(async response => {
    let result = {};
    try { result = await response.json(); } catch (_) {}
    if (!response.ok || !result.ok) throw new Error(result.message || 'Unable to save this subscription.');
  });

  const activeRegistration = async () => {
    const registration = await navigator.serviceWorker.register(serviceWorkerUrl, {
      scope: serviceWorkerUrl.replace(/[^/]+$/, ''),
    });
    if (registration.active) return registration;

    const readyRegistration = await navigator.serviceWorker.ready;
    if (!readyRegistration.active) throw new Error('Browser alerts are still starting. Please try again.');
    return readyRegistration;
  };

  const showEnabled = () => {
    controls.forEach(control => control.classList.add('push-enabled'));
    controls.forEach(control => {
      const action = control.querySelector('[data-push-enable]');
      if (action) { action.textContent = 'Notifications enabled'; action.disabled = true; }
    });
    setStatus('You will receive a quiet alert when a new reflection is ready.');
  };

  const enableNotifications = async button => {
    button.disabled = true;
    button.textContent = 'Connecting…';
    setStatus('Asking this browser for permission…');
    try {
      if (Notification.permission === 'denied') throw new Error('Notifications are blocked in this browser.');
      const permission = await Notification.requestPermission();
      if (permission !== 'granted') throw new Error('Notifications were not enabled.');
      const registration = await activeRegistration();
      let subscription = await registration.pushManager.getSubscription();
      if (!subscription) subscription = await registration.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: decodeKey(publicKey) });
      await sendSubscription(subscription);
      showEnabled();
    } catch (error) {
      button.disabled = false;
      button.textContent = 'Enable browser alerts';
      setStatus(error.message || 'Notifications could not be enabled.');
    }
  };

  controls.forEach(control => {
    const button = control.querySelector('[data-push-enable]');
    button?.addEventListener('click', () => enableNotifications(button));
  });

  if (Notification.permission === 'granted') {
    navigator.serviceWorker.getRegistration(serviceWorkerUrl).then(async registration => {
      if (registration && await registration.pushManager.getSubscription()) showEnabled();
    }).catch(() => {});
  }
})();
