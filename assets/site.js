const menu = document.querySelector('.menu-button');
const navigation = document.querySelector('#primary-nav');
menu?.addEventListener('click', () => {
  const open = navigation.classList.toggle('open');
  menu.setAttribute('aria-expanded', String(open));
});
navigation?.querySelectorAll('a').forEach(link => link.addEventListener('click', () => {
  navigation.classList.remove('open');
  menu?.setAttribute('aria-expanded', 'false');
}));

const announcement = document.querySelector('[data-announcement]');
if (sessionStorage.getItem('wts-announcement-dismissed')) announcement?.remove();
announcement?.querySelector('button')?.addEventListener('click', () => {
  sessionStorage.setItem('wts-announcement-dismissed', '1');
  announcement.remove();
});

const search = document.querySelector('[data-blog-search]');
const filters = [...document.querySelectorAll('[data-category]')];
const posts = [...document.querySelectorAll('[data-post]')];
let selectedCategory = 'all';

function filterPosts() {
  const term = (search?.value || '').trim().toLowerCase();
  let visible = 0;
  posts.forEach(post => {
    const categoryMatch = selectedCategory === 'all' || post.dataset.category === selectedCategory;
    const searchMatch = !term || post.dataset.search.includes(term);
    post.hidden = !(categoryMatch && searchMatch);
    if (!post.hidden) visible++;
  });
  const counter = document.querySelector('[data-post-count]');
  if (counter) counter.textContent = String(visible);
  const empty = document.querySelector('[data-blog-empty]');
  if (empty) empty.hidden = visible !== 0;
}
filters.forEach(button => button.addEventListener('click', () => {
  selectedCategory = button.dataset.category;
  filters.forEach(item => item.classList.toggle('active', item === button));
  filters.forEach(item => item.setAttribute('aria-pressed', String(item === button)));
  filterPosts();
}));
search?.addEventListener('input', filterPosts);

const readingProgress = document.querySelector('[data-reading-progress]');
if (readingProgress) {
  const updateReadingProgress = () => {
    const article = document.querySelector('.author-article');
    if (!article) return;
    const start = article.offsetTop;
    const length = Math.max(1, article.offsetHeight - window.innerHeight);
    const percent = Math.min(100, Math.max(0, ((window.scrollY - start) / length) * 100));
    readingProgress.style.width = `${percent}%`;
  };
  addEventListener('scroll', updateReadingProgress, { passive: true }); updateReadingProgress();
}

const engagementArticle = document.querySelector('[data-post-engagement]');
if (engagementArticle) {
  let maxScroll = 0, activeSeconds = 0, lastActiveAt = Date.now(), lastSent = 0;
  const updateEngagement = () => {
    const length = Math.max(1, engagementArticle.offsetHeight - window.innerHeight);
    const current = Math.min(100, Math.max(0, Math.round(((window.scrollY - engagementArticle.offsetTop) / length) * 100)));
    maxScroll = Math.max(maxScroll, current);
  };
  const sendEngagement = (force = false) => {
    const now = Date.now(); if (!force && now - lastSent < 15000) return; lastSent = now;
    const payload = new URLSearchParams({ slug: engagementArticle.dataset.postSlug, scroll: String(maxScroll), active_seconds: String(activeSeconds), completed: maxScroll >= 90 ? '1' : '0' });
    fetch(engagementArticle.dataset.engagementUrl, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' }, body: payload, keepalive: force, credentials: 'same-origin' }).catch(() => {});
  };
  const noteActivity = () => { const now = Date.now(); if (!document.hidden) activeSeconds += Math.min(30, Math.round((now - lastActiveAt) / 1000)); lastActiveAt = now; };
  ['scroll', 'mousemove', 'keydown', 'touchstart'].forEach(event => addEventListener(event, () => { noteActivity(); updateEngagement(); }, { passive: true }));
  addEventListener('visibilitychange', () => { noteActivity(); if (document.hidden) sendEngagement(true); });
  addEventListener('pagehide', () => { noteActivity(); sendEngagement(true); });
  setInterval(() => { noteActivity(); updateEngagement(); sendEngagement(); }, 15000);
  updateEngagement();
}
document.querySelector('[data-copy-link]')?.addEventListener('click', async (event) => {
  try { await navigator.clipboard.writeText(location.href); event.currentTarget.textContent = 'Link copied'; }
  catch (_) { event.currentTarget.textContent = 'Copy unavailable'; }
});
document.querySelector('[data-share-article]')?.addEventListener('click', async (event) => {
  const button = event.currentTarget;
  if (navigator.share) {
    try { await navigator.share({ title: button.dataset.shareTitle || document.title, url: location.href }); }
    catch (_) {}
    return;
  }
  try { await navigator.clipboard.writeText(location.href); button.textContent = 'Link copied'; }
  catch (_) { button.textContent = 'Copy unavailable'; }
});

const commentCommunity = document.querySelector('[data-comment-community]');
if (commentCommunity) {
  const commentForm = commentCommunity.querySelector('[data-comment-form]');
  const parentField = commentForm?.querySelector('[data-comment-parent]');
  const replying = commentForm?.querySelector('[data-comment-replying]');
  const replyName = commentForm?.querySelector('[data-comment-reply-name]');
  const clearReply = () => {
    if (parentField) parentField.value = '0';
    if (replying) replying.hidden = true;
    if (replyName) replyName.textContent = '';
  };
  commentCommunity.addEventListener('click', async (event) => {
    const replyButton = event.target.closest('[data-comment-reply]');
    if (replyButton && commentForm) {
      parentField.value = replyButton.dataset.commentId;
      replyName.textContent = replyButton.dataset.commentName;
      replying.hidden = false;
      commentForm.querySelector('textarea[name="body"]')?.focus();
      commentForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
      return;
    }
    if (event.target.closest('[data-comment-reply-cancel]')) {
      clearReply();
      return;
    }
    const reportButton = event.target.closest('[data-comment-report]');
    if (reportButton && !reportButton.disabled) {
      const reason = prompt('Why are you reporting this comment? Enter spam, abuse, misinformation, or other:');
      if (!reason) return;
      const normalizedReason = ['spam', 'abuse', 'misinformation'].includes(reason.trim().toLowerCase()) ? reason.trim().toLowerCase() : 'other';
      const details = normalizedReason === 'other' ? (prompt('Briefly describe the concern (optional):') || '') : '';
      reportButton.disabled = true;
      try {
        const response = await fetch(commentCommunity.dataset.reportUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
          body: new URLSearchParams({ comment_id: reportButton.dataset.commentId, comment_token: reportButton.dataset.commentToken, reason: normalizedReason, details })
        });
        const result = await response.json();
        if (!response.ok || !result.ok) throw new Error(result.message || 'Report failed');
        reportButton.textContent = 'Reported';
        reportButton.setAttribute('aria-pressed', 'true');
      } catch (error) {
        alert(error.message || 'Unable to submit this report.');
        reportButton.disabled = false;
      }
      return;
    }
    const likeButton = event.target.closest('[data-comment-like]');
    if (!likeButton || likeButton.disabled) return;
    likeButton.disabled = true;
    const payload = new URLSearchParams({ comment_id: likeButton.dataset.commentId, comment_token: likeButton.dataset.commentToken });
    try {
      const response = await fetch(commentCommunity.dataset.reactionUrl, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' }, body: payload, credentials: 'same-origin' });
      const result = await response.json();
      if (!response.ok || !result.ok) throw new Error(result.message || 'Reaction failed');
      likeButton.classList.toggle('liked', result.liked);
      likeButton.setAttribute('aria-pressed', String(result.liked));
      likeButton.querySelector('[data-like-label]').textContent = result.liked ? 'Liked' : 'Like';
      likeButton.querySelector('[data-like-count]').textContent = String(result.count);
      likeButton.closest('[data-comment-card]').dataset.commentLikes = String(result.count);
    } catch (_) {
      likeButton.querySelector('[data-like-label]').textContent = 'Try again';
    } finally {
      likeButton.disabled = false;
    }
  });
  commentCommunity.querySelector('[data-comment-sort]')?.addEventListener('change', (event) => {
    const thread = commentCommunity.querySelector('[data-comment-thread]');
    const cards = [...thread.querySelectorAll(':scope > [data-comment-card]')];
    cards.sort((left, right) => {
      if (event.target.value === 'newest') return new Date(right.dataset.commentDate) - new Date(left.dataset.commentDate);
      if (event.target.value === 'popular') return Number(right.dataset.commentLikes) - Number(left.dataset.commentLikes);
      return new Date(left.dataset.commentDate) - new Date(right.dataset.commentDate);
    });
    cards.forEach(card => thread.append(card));
  });
}

const doctrineCarousel = document.querySelector('[data-doctrine-carousel]');
if (doctrineCarousel) {
  const shiftCarousel = (direction) => doctrineCarousel.scrollBy({ left: direction * Math.min(420, doctrineCarousel.clientWidth * .82), behavior: 'smooth' });
  document.querySelector('[data-carousel-prev]')?.addEventListener('click', () => shiftCarousel(-1));
  document.querySelector('[data-carousel-next]')?.addEventListener('click', () => shiftCarousel(1));
}

const promotionPopup = document.querySelector('[data-promotion-popup]');
if (promotionPopup) {
  const popupKey = `wts-popup-dismissed:${promotionPopup.dataset.popupId || 'default'}`;
  const showPopup = () => { promotionPopup.classList.add('visible'); promotionPopup.setAttribute('aria-hidden', 'false'); };
  if (!sessionStorage.getItem(popupKey)) {
    const delay = Math.max(0, Number(promotionPopup.dataset.delay || 5)) * 1000;
    window.setTimeout(showPopup, delay);
  }
  promotionPopup.querySelector('[data-popup-close]')?.addEventListener('click', () => {
    sessionStorage.setItem(popupKey, '1'); promotionPopup.classList.remove('visible'); promotionPopup.setAttribute('aria-hidden', 'true');
  });
  promotionPopup.addEventListener('click', (event) => { if (event.target === promotionPopup) promotionPopup.querySelector('[data-popup-close]')?.click(); });
}

const subscriberBanner = document.querySelector('[data-subscriber-banner]');
if (subscriberBanner) {
  const bannerKey = `wts-subscriber-banner-dismissed:${subscriberBanner.dataset.bannerId || 'default'}`;
  const hasSubscriptionResult = new URLSearchParams(location.search).has('subscribe');
  const showSubscriberBanner = () => {
    if (sessionStorage.getItem(bannerKey)) return;
    if (promotionPopup?.classList.contains('visible') && !hasSubscriptionResult) {
      window.setTimeout(showSubscriberBanner, 1000);
      return;
    }
    subscriberBanner.classList.add('visible');
    subscriberBanner.setAttribute('aria-hidden', 'false');
  };
  if (hasSubscriptionResult) {
    if (location.hash === '#email-signup-banner') showSubscriberBanner();
    else sessionStorage.setItem(bannerKey, '1');
  } else if (!sessionStorage.getItem(bannerKey)) {
    window.setTimeout(showSubscriberBanner, Math.max(0, Number(subscriberBanner.dataset.delay || 10)) * 1000);
  }
  subscriberBanner.querySelector('[data-subscriber-banner-close]')?.addEventListener('click', () => {
    sessionStorage.setItem(bannerKey, '1');
    subscriberBanner.classList.remove('visible');
    subscriberBanner.setAttribute('aria-hidden', 'true');
  });
}

// Selecting a custom contribution field also selects the corresponding option.
document.querySelectorAll('[data-product-checkout]').forEach(form => {
  const custom = form.querySelector('input[name="custom_amount"]');
  if (!custom) return;
  custom.addEventListener('focus', () => {
    const other = form.querySelector('input[name="amount_choice"][value="custom"]');
    if (other) other.checked = true;
  });
});
