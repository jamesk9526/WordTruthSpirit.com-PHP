<?php
declare(strict_types=1);
require dirname(__DIR__).'/includes/bootstrap.php';require ROOT_PATH.'/includes/comments.php';
$slug=trim((string)($_POST['slug']??''));$name=trim((string)($_POST['name']??''));$email=trim((string)($_POST['email']??''));$body=trim((string)($_POST['body']??''));
if(($_POST['website']??'')!==''||!$slug||!$name||!filter_var($email,FILTER_VALIDATE_EMAIL)||mb_strlen($body)<3||mb_strlen($body)>4000){header('Location:'.url('blog/post.php?slug='.urlencode($slug).'&comment=error#comments'));exit;}
if(ensureCommentsTable())try{database()->prepare('INSERT INTO wts_blog_comments (post_slug,name,email,body) VALUES (?,?,?,?)')->execute([$slug,$name,$email,$body]);header('Location:'.url('blog/post.php?slug='.urlencode($slug).'&comment=pending#comments'));exit;}catch(PDOException $e){}
header('Location:'.url('blog/post.php?slug='.urlencode($slug).'&comment=error#comments'));
