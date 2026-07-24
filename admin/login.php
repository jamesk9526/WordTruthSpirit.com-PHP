<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';
if (adminLoggedIn()) { header('Location: '.url('admin/')); exit; }
if (database() && adminCount() === 0) { header('Location: '.url('admin/setup.php')); exit; }
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verifyCsrf(); $email=trim((string)($_POST['email']??'')); $password=(string)($_POST['password']??'');
    if ($db=database()) { $statement=$db->prepare('SELECT id,name,password_hash FROM wts_admin_users WHERE email=? LIMIT 1'); $statement->execute([$email]); $user=$statement->fetch(); if($user&&password_verify($password,$user['password_hash'])){session_regenerate_id(true);$_SESSION['wts_admin_id']=$user['id'];$_SESSION['wts_admin_name']=$user['name'];header('Location:'.url('admin/'));exit;} }
    $error='The email or password was not recognized.';
}
$adminTitle='Sign in';require __DIR__.'/_header.php';
?>
<section class="admin-auth"><h1>Administration</h1><p>Manage publications and journal entries.</p><?php if(!database()):?><p class="notice error">MySQL is not configured. Follow the database instructions in README.md.</p><?php endif;?><?php if($error):?><p class="notice error"><?=e($error)?></p><?php endif;?><form method="post"><input type="hidden" name="csrf" value="<?=csrfToken()?>"><label>Email<input type="email" name="email" required></label><label>Password<input type="password" name="password" required></label><button class="button button-primary">Sign in</button></form></section>
<?php require __DIR__.'/_footer.php'; ?>
