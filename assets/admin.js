(() => {
  const menuButton = document.querySelector('.admin-menu-button');
  const sidebar = document.querySelector('.admin-sidebar');
  menuButton?.addEventListener('click', () => {
    const open = sidebar.classList.toggle('open');
    menuButton.setAttribute('aria-expanded', String(open));
  });

  const form = document.querySelector('[data-post-editor]');
  if (!form) return;

  const visual = form.querySelector('[data-editor-surface]');
  const source = form.querySelector('[data-editor-source]');
  const output = form.querySelector('[data-editor-output]');
  const status = form.querySelector('[data-editor-status]');
  const title = form.querySelector('[data-slug-source]');
  const slug = form.querySelector('[data-slug-field]');
  const modeButtons = [...form.querySelectorAll('[data-editor-mode]')];
  const storageKey = `wts-editor-recovery:${location.pathname}${location.search}`;
  let sourceMode = false;
  let slugManuallyChanged = Boolean(slug.value);
  let saveTimer;

  const cleanHtml = (html) => html
    .replace(/<(script|style|iframe|object|embed)[^>]*>[\s\S]*?<\/\1>/gi, '')
    .replace(/\s+on\w+\s*=\s*(["']).*?\1/gi, '')
    .replace(/\s+(href|src)\s*=\s*(["'])\s*javascript:.*?\2/gi, '');

  const currentHtml = () => sourceMode ? source.value : visual.innerHTML;
  const syncOutput = () => { output.value = cleanHtml(currentHtml()).trim(); };
  const updateInsights = () => {
    const text = (sourceMode ? source.value.replace(/<[^>]*>/g, ' ') : visual.innerText).replace(/\s+/g, ' ').trim();
    const words = text ? text.split(' ').length : 0;
    form.querySelector('[data-word-count]').textContent = words.toLocaleString();
    form.querySelector('[data-read-time]').textContent = Math.max(1, Math.ceil(words / 220));
  };
  const saveRecovery = () => {
    clearTimeout(saveTimer);
    status.textContent = 'Editing…';
    saveTimer = setTimeout(() => {
      syncOutput();
      localStorage.setItem(storageKey, JSON.stringify({ body: output.value, title: title.value, slug: slug.value, savedAt: Date.now() }));
      status.textContent = 'Recovery draft saved locally';
    }, 450);
  };
  const setMode = (nextSourceMode) => {
    if (nextSourceMode === sourceMode) return;
    if (nextSourceMode) { source.value = visual.innerHTML; visual.hidden = true; source.hidden = false; }
    else { visual.innerHTML = cleanHtml(source.value); source.hidden = true; visual.hidden = false; }
    sourceMode = nextSourceMode;
    modeButtons.forEach(button => button.classList.toggle('active', (button.dataset.editorMode === 'source') === sourceMode));
    updateInsights();
  };

  const recovery = localStorage.getItem(storageKey);
  if (recovery && !output.value.trim()) {
    try {
      const draft = JSON.parse(recovery);
      if (draft.body && confirm('Restore the locally saved recovery draft for this reflection?')) {
        visual.innerHTML = cleanHtml(draft.body); title.value = draft.title || ''; slug.value = draft.slug || ''; slugManuallyChanged = Boolean(slug.value);
        status.textContent = 'Recovery draft restored';
      }
    } catch (_) { localStorage.removeItem(storageKey); }
  }

  form.querySelectorAll('[data-command]').forEach(button => button.addEventListener('click', () => {
    if (sourceMode) return;
    visual.focus();
    document.execCommand(button.dataset.command, false);
    saveRecovery(); updateInsights();
  }));
  form.querySelectorAll('[data-block]').forEach(button => button.addEventListener('click', () => {
    if (sourceMode) return;
    visual.focus();
    document.execCommand('formatBlock', false, button.dataset.block);
    saveRecovery(); updateInsights();
  }));
  form.querySelector('[data-link]')?.addEventListener('click', () => {
    if (sourceMode) return;
    const href = prompt('Paste the full URL for this link:');
    if (!href || !/^https?:\/\//i.test(href)) return;
    visual.focus();
    document.execCommand('createLink', false, href);
    saveRecovery(); updateInsights();
  });
  modeButtons.forEach(button => button.addEventListener('click', () => setMode(button.dataset.editorMode === 'source')));
  visual.addEventListener('input', () => { syncOutput(); saveRecovery(); updateInsights(); });
  source.addEventListener('input', () => { syncOutput(); saveRecovery(); updateInsights(); });
  visual.addEventListener('paste', (event) => {
    event.preventDefault();
    const text = event.clipboardData?.getData('text/plain') || '';
    document.execCommand('insertText', false, text);
  });
  title.addEventListener('input', () => {
    if (slugManuallyChanged) return;
    slug.value = title.value.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
  });
  slug.addEventListener('input', () => { slugManuallyChanged = true; });
  form.addEventListener('submit', () => { syncOutput(); localStorage.removeItem(storageKey); status.textContent = 'Saving…'; });
  document.addEventListener('keydown', (event) => {
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') { event.preventDefault(); form.requestSubmit(); }
  });
  syncOutput(); updateInsights();
})();
