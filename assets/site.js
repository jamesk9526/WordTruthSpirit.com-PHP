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
}
filters.forEach(button => button.addEventListener('click', () => {
  selectedCategory = button.dataset.category;
  filters.forEach(item => item.classList.toggle('active', item === button));
  filterPosts();
}));
search?.addEventListener('input', filterPosts);
