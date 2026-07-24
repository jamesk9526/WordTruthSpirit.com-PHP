(() => {
  const menuButton = document.querySelector('.admin-menu-button');
  const sidebar = document.querySelector('.admin-sidebar');
  menuButton?.addEventListener('click', () => {
    const open = sidebar.classList.toggle('open');
    menuButton.setAttribute('aria-expanded', String(open));
  });

  const form = document.querySelector('[data-post-editor]');
  if (!form) {
    const seoForm = document.querySelector('[data-seo-form]');
    if (!seoForm) return;
    const title = seoForm.querySelector('[data-seo-title]'); const description = seoForm.querySelector('[data-seo-description]'); const keyword = seoForm.querySelector('[data-seo-keyword]');
    const score = document.querySelector('[data-seo-score]'); const checks = document.querySelector('[data-seo-checks]');
    const refreshSeo = () => {
      const titleText = title.value.trim(), descriptionText = description.value.trim(), key = keyword.value.trim().toLowerCase();
      const titleOkay = titleText.length >= 30 && titleText.length <= 60, descriptionOkay = descriptionText.length >= 120 && descriptionText.length <= 160;
      const keyInTitle = key && titleText.toLowerCase().includes(key), keyInDescription = key && descriptionText.toLowerCase().includes(key);
      const points = (titleOkay?30:10)+(descriptionOkay?30:10)+(keyInTitle?20:0)+(keyInDescription?15:0)+(titleText.includes('Word Truth Spirit')?5:0);
      score.textContent = points; seoForm.querySelector('[data-title-count]').textContent = titleText.length; seoForm.querySelector('[data-description-count]').textContent = descriptionText.length;
      document.querySelector('[data-preview-title]').textContent = titleText || 'Page title'; document.querySelector('[data-preview-description]').textContent = descriptionText || 'Write a clear, compelling description for this page.';
      document.querySelector('[data-preview-url]').textContent = `wordtruthspirit.com${window.wtsSeoPagePath || '/'}`;
      checks.innerHTML = [[titleOkay,'Title is between 30–60 characters'],[descriptionOkay,'Description is between 120–160 characters'],[keyInTitle,'Focus keyword appears in the title'],[keyInDescription,'Focus keyword appears in the description'],[titleText.includes('Word Truth Spirit'),'Brand name included in the title']].map(([pass,text]) => `<li class="${pass?'pass':'needs-work'}">${pass?'✓':'○'} ${text}</li>`).join('');
    };
    [title,description,keyword].forEach(input => input.addEventListener('input', refreshSeo)); seoForm.querySelector('[data-seo-page]').addEventListener('change', event => { location.search = `?page=${encodeURIComponent(event.target.value)}`; }); refreshSeo(); return;
  }
  const canvas = form.querySelector('[data-editor-surface]');
  const source = form.querySelector('[data-editor-source]');
  const output = form.querySelector('[data-editor-output]');
  const status = form.querySelector('[data-editor-status]');
  const title = form.querySelector('[data-slug-source]');
  const slug = form.querySelector('[data-slug-field]');
  const modeButtons = [...form.querySelectorAll('[data-editor-mode]')];
  const storageKey = `wts-editor-recovery:${location.pathname}${location.search}`;
  let sourceMode = false;
  let slugManuallyChanged = Boolean(slug.value);
  let activeBlock = null;
  let draggedBlock = null;
  let saveTimer;

  const cleanHtml = (html) => html.replace(/<(script|style|iframe|object|embed)[^>]*>[\s\S]*?<\/\1>/gi, '').replace(/\s+on\w+\s*=\s*(["']).*?\1/gi, '').replace(/\s+(href|src)\s*=\s*(["'])\s*javascript:.*?\2/gi, '');
  const createBlock = (html = '<p></p>') => {
    const block = document.createElement('article');
    block.className = 'writer-block'; block.draggable = true;
    block.innerHTML = `<button class="block-grip" type="button" aria-label="Drag to reorder" title="Drag to reorder">⠿</button><div class="block-content" contenteditable="true" data-block-content>${cleanHtml(html)}</div><div class="block-actions"><button type="button" data-block-up aria-label="Move block up">↑</button><button type="button" data-block-down aria-label="Move block down">↓</button><button type="button" data-block-remove aria-label="Remove block">×</button></div>`;
    return block;
  };
  const blockHtml = () => [...canvas.querySelectorAll('[data-block-content]')].map(item => item.innerHTML).join('\n').trim();
  const loadBlocks = (html) => {
    const shell = document.createElement('div'); shell.innerHTML = cleanHtml(html).trim();
    const pieces = [...shell.childNodes].filter(node => node.nodeType !== Node.TEXT_NODE || node.textContent.trim()).map(node => node.nodeType === Node.TEXT_NODE ? `<p>${node.textContent}</p>` : node.outerHTML);
    canvas.replaceChildren(...(pieces.length ? pieces : ['<p></p>']).map(createBlock));
    activeBlock = canvas.querySelector('.writer-block');
  };
  const currentHtml = () => sourceMode ? source.value : blockHtml();
  const syncOutput = () => { output.value = cleanHtml(currentHtml()).trim(); };
  const updateInsights = () => {
    const text = (sourceMode ? source.value.replace(/<[^>]*>/g, ' ') : canvas.innerText).replace(/\s+/g, ' ').trim();
    const words = text ? text.split(' ').length : 0;
    form.querySelector('[data-word-count]').textContent = words.toLocaleString();
    form.querySelector('[data-read-time]').textContent = Math.max(1, Math.ceil(words / 220));
  };
  const saveRecovery = () => {
    clearTimeout(saveTimer); status.textContent = 'Editing…';
    saveTimer = setTimeout(() => { syncOutput(); localStorage.setItem(storageKey, JSON.stringify({ body: output.value, title: title.value, slug: slug.value, savedAt: Date.now() })); status.textContent = 'Recovery draft saved locally'; }, 450);
  };
  const touch = () => { syncOutput(); saveRecovery(); updateInsights(); };
  const setMode = (nextSourceMode) => {
    if (nextSourceMode === sourceMode) return;
    if (nextSourceMode) { source.value = blockHtml(); canvas.hidden = true; source.hidden = false; }
    else { loadBlocks(source.value); source.hidden = true; canvas.hidden = false; }
    sourceMode = nextSourceMode;
    modeButtons.forEach(button => button.classList.toggle('active', (button.dataset.editorMode === 'source') === sourceMode));
    updateInsights();
  };
  const focusActive = () => {
    const target = activeBlock?.querySelector('[data-block-content]') || canvas.querySelector('[data-block-content]');
    target?.focus();
  };
  const insertBlock = (type) => {
    let html = '<p>Begin writing here.</p>';
    if (type === 'h2') html = '<h2>Section heading</h2>';
    if (type === 'blockquote') html = '<blockquote>“Scripture, prayer, or a meaningful quotation.”</blockquote>';
    if (type === 'ul') html = '<ul><li>First point</li><li>Second point</li></ul>';
    if (type === 'ol') html = '<ol><li>First step</li><li>Second step</li></ol>';
    if (type === 'hr') html = '<hr>';
    if (type === 'image') { const image = prompt('Paste a public image URL or site image path:'); if (!image) return; const caption = prompt('Optional image caption:') || ''; html = `<figure><img src="${image.replace(/"/g, '&quot;')}" alt=""><figcaption>${caption}</figcaption></figure>`; }
    const block = createBlock(html); (activeBlock || canvas.lastElementChild)?.after(block); if (!canvas.children.length) canvas.append(block); activeBlock = block; block.querySelector('[data-block-content]').focus(); touch();
  };

  loadBlocks(canvas.innerHTML);
  const recovery = localStorage.getItem(storageKey);
  if (recovery && !output.value.trim()) try { const draft = JSON.parse(recovery); if (draft.body && confirm('Restore the locally saved recovery draft for this reflection?')) { loadBlocks(draft.body); title.value = draft.title || ''; slug.value = draft.slug || ''; slugManuallyChanged = Boolean(slug.value); status.textContent = 'Recovery draft restored'; } } catch (_) { localStorage.removeItem(storageKey); }

  canvas.addEventListener('focusin', event => { activeBlock = event.target.closest('.writer-block') || activeBlock; });
  canvas.addEventListener('input', touch);
  canvas.addEventListener('click', event => {
    const block = event.target.closest('.writer-block'); if (block) activeBlock = block;
    if (event.target.closest('[data-block-up]')) { const previous = block.previousElementSibling; if (previous) canvas.insertBefore(block, previous); touch(); }
    if (event.target.closest('[data-block-down]')) { const next = block.nextElementSibling; if (next) canvas.insertBefore(next, block); touch(); }
    if (event.target.closest('[data-block-remove]')) { if (canvas.children.length === 1) block.querySelector('[data-block-content]').innerHTML = '<p></p>'; else block.remove(); activeBlock = canvas.lastElementChild; touch(); }
  });
  canvas.addEventListener('dragstart', event => { draggedBlock = event.target.closest('.writer-block'); if (draggedBlock) { draggedBlock.classList.add('dragging'); event.dataTransfer.effectAllowed = 'move'; } });
  canvas.addEventListener('dragend', () => { draggedBlock?.classList.remove('dragging'); draggedBlock = null; });
  canvas.addEventListener('dragover', event => { event.preventDefault(); const over = event.target.closest('.writer-block'); if (!draggedBlock || !over || over === draggedBlock) return; const box = over.getBoundingClientRect(); if (event.clientY < box.top + box.height / 2) canvas.insertBefore(draggedBlock, over); else canvas.insertBefore(draggedBlock, over.nextSibling); });
  canvas.addEventListener('drop', event => { event.preventDefault(); touch(); });
  canvas.addEventListener('paste', event => { if (!event.target.closest('[data-block-content]')) return; event.preventDefault(); document.execCommand('insertText', false, event.clipboardData?.getData('text/plain') || ''); touch(); });
  form.querySelectorAll('[data-add-block]').forEach(button => button.addEventListener('click', () => { if (!sourceMode) insertBlock(button.dataset.addBlock); }));
  form.querySelectorAll('[data-command]').forEach(button => button.addEventListener('click', () => { if (sourceMode) return; focusActive(); document.execCommand(button.dataset.command, false); touch(); }));
  form.querySelectorAll('[data-block]').forEach(button => button.addEventListener('click', () => { if (sourceMode) return; focusActive(); document.execCommand('formatBlock', false, button.dataset.block); touch(); }));
  form.querySelector('[data-link]')?.addEventListener('click', () => { if (sourceMode) return; const href = prompt('Paste the full URL for this link:'); if (!href || !/^https?:\/\//i.test(href)) return; focusActive(); document.execCommand('createLink', false, href); touch(); });
  form.querySelector('[data-focus-mode]')?.addEventListener('click', () => document.body.classList.toggle('writer-focus-mode'));
  modeButtons.forEach(button => button.addEventListener('click', () => setMode(button.dataset.editorMode === 'source')));
  source.addEventListener('input', touch);
  title.addEventListener('input', () => { if (!slugManuallyChanged) slug.value = title.value.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, ''); });
  slug.addEventListener('input', () => { slugManuallyChanged = true; });
  form.addEventListener('submit', () => { syncOutput(); localStorage.removeItem(storageKey); status.textContent = 'Saving…'; });
  document.addEventListener('keydown', event => { if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') { event.preventDefault(); form.requestSubmit(); } });
  syncOutput(); updateInsights();
})();
