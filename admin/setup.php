<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';
if (!database()) { $adminTitle='Database required'; require __DIR__.'/_header.php'; echo '<section class="admin-panel"><h1>Connect MySQL first</h1><p>Configure the DB_* environment values and run database/schema.sql, then reload this page.</p></section>'; require __DIR__.'/_footer.php'; exit; }
if (adminCount() > 0) { header('Location: ' . url('admin/login.php')); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name=trim((string)($_POST['name']??'')); $email=filter_var(trim((string)($_POST['email']??'')),FILTER_VALIDATE_EMAIL); $password=(string)($_POST['password']??'');
    if (!$name || !$email || strlen($password)<12) $error='Use a valid email and a password of at least 12 characters.';
    else {
        try { $statement=database()->prepare('INSERT INTO wts_admin_users (name,email,password_hash) VALUES (?,?,?)'); $statement->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT)]); header('Location: '.url('admin/login.php?setup=success')); exit; }
        catch(PDOException $exception){$error='Unable to create the administrator. The email may already exist.';}
    }
}
$adminTitle='First-time setup'; require __DIR__.'/_header.php';
?>
<section class="admin-auth"><h1>Create the first administrator</h1><p>This setup closes automatically after the first account is created.</p><?php if($error):?><p class="notice error"><?=e($error)?></p><?php endif;?><form method="post"><input type="hidden" name="csrf" value="<?=csrfToken()?>"><label>Name<input name="name" required></label><label>Email<input type="email" name="email" required></label><label>Password<input type="password" name="password" minlength="12" required></label><button class="button button-primary">Create administrator</button></form></section>
<?php require __DIR__.'/_footer.php'; ?>
