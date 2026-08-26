<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require ROOT_PATH . '/includes/posts.php';
require ROOT_PATH . '/includes/seo.php';

header('Content-Type: application/xml; charset=utf-8');
$pages = ['', 'word.php', 'truth.php', 'spirit.php', 'publications.php', 'blog/', 'commitments.php', 'contact.php'];
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($pages as $page) echo "  <url><loc>" . htmlspecialchars(seoAbsoluteUrl($page), ENT_XML1) . "</loc></url>\n";
foreach (allPosts() as $post) {
    $loc = seoAbsoluteUrl('blog/' . rawurlencode((string) $post['slug']) . '/');
    $lastmod = !empty($post['published_at']) ? date('Y-m-d', strtotime((string) $post['published_at'])) : '';
    echo '  <url><loc>' . htmlspecialchars($loc, ENT_XML1) . '</loc>' . ($lastmod ? '<lastmod>' . $lastmod . '</lastmod>' : '') . "</url>\n";
}
echo '</urlset>';
