<?php
declare(strict_types=1);
require_once ROOT_PATH . '/config/database.php';

function seedPosts(): array
{
    return [
      ['slug'=>'browser-push-now-available','title'=>'Browser push now available!','category'=>'general','published_at'=>'2026-07-15','reading_minutes'=>5,'author'=>'Patrick E. Pennington','excerpt'=>'Stay connected with a short browser alert whenever a new reflection is published.','body'=>'Browser notifications are now available for the Word Truth Spirit journal. This quiet, optional update helps readers know when a new reflection is ready without adding more noise to the inbox.'],
      ['slug'=>'new-email-subscription-for-blogs','title'=>'New email subscription for blogs!','category'=>'general','published_at'=>'2026-07-14','reading_minutes'=>5,'author'=>'Patrick E. Pennington','excerpt'=>'A simple new way to receive the latest Word Truth Spirit reflections.','body'=>'Readers may now subscribe for email updates when new journal entries are published. We will only write when there is something new to read.'],
      ['slug'=>'large-print-version-of-the-spirit-of-truth-coming-soon','title'=>'Large Print version of The Spirit of Truth coming soon!','category'=>'general','published_at'=>'2026-07-11','reading_minutes'=>5,'author'=>'Patrick E. Pennington','excerpt'=>'A more readable edition of The Spirit of Truth is being prepared.','body'=>'A large-print edition is being prepared for readers who prefer a more comfortable page and type size.'],
      ['slug'=>'celebrate-250','title'=>'Celebrate 250!','category'=>'general','published_at'=>'2026-07-08','reading_minutes'=>5,'author'=>'Patrick E. Pennington','excerpt'=>'A special Independence Day offer on the ebook edition of The Spirit of Truth.','body'=>'For a limited time, the ebook edition of The Spirit of Truth is available for $2.50 as we celebrate America’s 250th anniversary.'],
      ['slug'=>'biblememorycom-promo','title'=>'BibleMemory.com Promo','category'=>'general','published_at'=>'2026-06-27','reading_minutes'=>5,'author'=>'Patrick E. Pennington','excerpt'=>'Build a lasting habit of Scripture memory and save 20 percent.','body'=>'Word Truth Spirit readers can save 20 percent on Bible Memory Unlimited through our partner link.'],
      ['slug'=>'the-church-search-update','title'=>'The Church Search Update','category'=>'general','published_at'=>'2024-04-17','reading_minutes'=>6,'author'=>'Patrick E. Pennington','excerpt'=>'It has been over a year since I posted about our church situation, and I am happy to report that we have found a church home.','body'=>'It has been over a year now since I posted about our church situation, and I am happy to report that we have found a church home, at least for now.'],
      ['slug'=>'jesus-was-both-word-and-spirit','title'=>'Jesus was both Word and Spirit','category'=>'truth','published_at'=>'2024-03-04','reading_minutes'=>8,'author'=>'Patrick E. Pennington','excerpt'=>'The primary reason we emphasize the true Word and the true Spirit is simple: Jesus himself was both Word and Spirit.','body'=>'The primary reason why we, as a ministry and as a people, emphasize the true Word and the true Spirit is simple: Jesus himself was both Word & Spirit. In Christ there is no conflict between full submission to the written Word and full dependence upon the Holy Spirit.'],
      ['slug'=>'the-church-search','title'=>'The Church Search','category'=>'general','published_at'=>'2024-02-13','reading_minutes'=>7,'author'=>'Patrick E. Pennington','excerpt'=>'A search for other followers who believe in the necessary connection of Word and Spirit doctrines.','body'=>'It has been an ongoing search of mine to find other followers who also believe in the connection of Word and Spirit doctrines.'],
      ['slug'=>'a-bridge-to-consistency','title'=>'A Bridge to Consistency','category'=>'truth','published_at'=>'2024-01-01','reading_minutes'=>7,'author'=>'Patrick E. Pennington','excerpt'=>'Building a consistent biblical bridge between conviction and practice.','body'=>'Biblical consistency requires that our practice agree with our doctrine, and that our doctrine remain subject to the whole counsel of God.'],
      ['slug'=>'marks-christmas-story-part-3','title'=>'What Do You Mean, “Mark’s Christmas Story”? – Part 3','category'=>'christmas','published_at'=>'2023-12-31','reading_minutes'=>8,'author'=>'Patrick E. Pennington','excerpt'=>'Malachi’s prophecy and the opening of Mark’s Gospel.','body'=>'Mark begins his Gospel with the prophets, showing how the coming of Christ fulfills the promised preparation of the way of the Lord.'],
      ['slug'=>'marks-christmas-story-part-2','title'=>'What Do You Mean, “Mark’s Christmas Story”? – Part 2','category'=>'christmas','published_at'=>'2023-12-17','reading_minutes'=>8,'author'=>'Patrick E. Pennington','excerpt'=>'Isaiah’s prophecy speaks words of comfort to the true people of God.','body'=>'Isaiah’s prophecy speaks words of comfort to the true people of God and points forward to the voice preparing the way of the Lord.'],
      ['slug'=>'marks-christmas-story-part-1','title'=>'What Do You Mean, “Mark’s Christmas Story”? – Part 1','category'=>'christmas','published_at'=>'2023-05-26','reading_minutes'=>8,'author'=>'Patrick E. Pennington','excerpt'=>'Have you ever wondered why Mark’s Gospel doesn’t have a Christmas narrative?','body'=>'Mark’s Christmas story does not begin with a manger. It begins with the prophetic announcement of the Son of God.'],
      ['slug'=>'importance-word-spirit-part-2','title'=>'The Importance of Word and Spirit Doctrine, Part 2','category'=>'truth','published_at'=>'2022-09-29','reading_minutes'=>10,'author'=>'Patrick E. Pennington','excerpt'=>'The necessary and unbreakable connection between Word and Spirit teaching.','body'=>'The Word and the Spirit are not competing authorities. The Spirit inspired the Word and always works in harmony with it.'],
      ['slug'=>'importance-word-spirit-part-1','title'=>'The Importance of Word and Spirit Doctrine, Part 1','category'=>'truth','published_at'=>'2022-09-19','reading_minutes'=>10,'author'=>'Patrick E. Pennington','excerpt'=>'Doctrine affects every area of our lives.','body'=>'Doctrine affects every area of our lives. What we believe about Scripture and the Holy Spirit shapes worship, witness, and Christian obedience.'],
      ['slug'=>'the-truth-fulcrum','title'=>'The Truth Fulcrum','category'=>'truth','published_at'=>'2022-08-29','reading_minutes'=>6,'author'=>'Patrick E. Pennington','excerpt'=>'It is vital that we get as close to the center of the scale as possible.','body'=>'The truth is the fulcrum that holds Word and Spirit in their proper relationship, refusing both cold formalism and unbiblical excess.'],
      ['slug'=>'thats-a-good-question','title'=>"That’s a good question.",'category'=>'truth','published_at'=>'2022-08-23','reading_minutes'=>5,'author'=>'Patrick E. Pennington','excerpt'=>'Both the Word and the Spirit are centered on the Truth.','body'=>'Good questions, asked humbly and answered from Scripture, help believers grow in truth rather than merely defend familiar assumptions.'],
    ];
}

function allPosts(): array
{
    $db = database();
    if ($db) {
        try {
            $rows = $db->query("SELECT slug,title,category,published_at,reading_minutes,author,excerpt,body FROM wts_posts WHERE status='published' ORDER BY published_at DESC")->fetchAll();
            if ($rows) return $rows;
        } catch (PDOException $error) {
            error_log('Blog query failed: ' . $error->getMessage());
        }
    }
    return seedPosts();
}

function findPost(string $slug): ?array
{
    foreach (allPosts() as $post) {
        if ($post['slug'] === $slug) return $post;
    }
    return null;
}
