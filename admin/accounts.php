<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
requireAdmin();

$db = database();
$error = '';
$success = '';
$currentId = (string) $_SESSION['wts_admin_id'];
$legacy = databaseUsesLegacySchema();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = (string) ($_POST['action'] ?? '');
    try {
        if ($action === 'create') {
            $name = trim((string) ($_POST['name'] ?? ''));
            $email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
            $password = (string) ($_POST['password'] ?? '');
            if ($name === '' || !$email || strlen($password) < 12) {
                throw new InvalidArgumentException('Enter a name, a valid email, and a password of at least 12 characters.');
            }
            $nameColumn = $legacy ? 'username' : 'name';
            if ($legacy) {
                $columns = ['id', $nameColumn, 'email', 'password_hash'];
                $parameters = [uuidV4(), $name, $email, password_hash($password, PASSWORD_DEFAULT)];
                if (in_array('role', adminUserColumns(), true)) { $columns[] = 'role'; $parameters[] = 'admin'; }
                $placeholders = implode(',', array_fill(0, count($columns), '?'));
                $statement = $db->prepare('INSERT INTO ' . adminUserTable() . ' (' . implode(',', $columns) . ") VALUES ({$placeholders})");
                $statement->execute($parameters);
            } else {
                $statement = $db->prepare("INSERT INTO " . adminUserTable() . " ({$nameColumn},email,password_hash) VALUES (?,?,?)");
                $statement->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            }
            $success = 'Administrator account created.';
        } elseif ($action === 'profile') {
            $name = trim((string) ($_POST['name'] ?? ''));
            $email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
            if ($name === '' || !$email) throw new InvalidArgumentException('Enter a name and valid email address.');
            $nameColumn = $legacy ? 'username' : 'name';
            $statement = $db->prepare('UPDATE ' . adminUserTable() . " SET {$nameColumn}=?,email=? WHERE id=?");
            $statement->execute([$name, $email, $currentId]);
            $_SESSION['wts_admin_name'] = $name;
            $_SESSION['wts_admin_email'] = $email;
            $success = 'Your profile was updated.';
        } elseif ($action === 'password') {
            $currentPassword = (string) ($_POST['current_password'] ?? '');
            $newPassword = (string) ($_POST['new_password'] ?? '');
            $statement = $db->prepare('SELECT password_hash FROM ' . adminUserTable() . ' WHERE id=?');
            $statement->execute([$currentId]);
            $hash = (string) $statement->fetchColumn();
            if (!password_verify($currentPassword, $hash)) throw new InvalidArgumentException('Your current password was not correct.');
            if (strlen($newPassword) < 12) throw new InvalidArgumentException('The new password must be at least 12 characters.');
            $db->prepare('UPDATE ' . adminUserTable() . ' SET password_hash=? WHERE id=?')->execute([password_hash($newPassword, PASSWORD_DEFAULT), $currentId]);
            $success = 'Your password was changed.';
        } elseif ($action === 'delete') {
            $deleteId = (string) ($_POST['user_id'] ?? '');
            if ($deleteId === '') throw new InvalidArgumentException('Choose an administrator to remove.');
            if ($deleteId === $currentId) throw new InvalidArgumentException('You cannot delete the account you are currently using.');
            if (adminCount() <= 1) throw new InvalidArgumentException('The last administrator cannot be deleted.');
            $db->prepare('DELETE FROM ' . adminUserTable() . ' WHERE id=?')->execute([$deleteId]);
            $success = 'Administrator account removed.';
        }
    } catch (InvalidArgumentException $exception) {
        $error = $exception->getMessage();
    } catch (PDOException $exception) {
        $error = 'The account change could not be saved. The email or name may already be in use.';
    }
}

$users = adminUsers();
$matchingUsers = array_filter($users, fn(array $user): bool => (string) $user['id'] === $currentId);
$currentUser = current($matchingUsers) ?: ['name'=>'','email'=>''];
$adminTitle = 'Accounts';
$currentAdminPage = 'accounts';
require __DIR__ . '/_header.php';
?>
<header class="admin-title admin-title-actions">
  <div><p class="kicker">Security &amp; access</p><h1>Administrator accounts</h1><p>Manage who can enter this workspace and keep your own sign-in details current.</p></div>
  <span class="admin-count-pill"><?=count($users)?> <?=count($users) === 1 ? 'administrator' : 'administrators'?></span>
</header>
<?php if ($error): ?><p class="notice error"><?=e($error)?></p><?php endif; ?>
<?php if ($success): ?><p class="notice success"><?=e($success)?></p><?php endif; ?>
<div class="admin-two-column">
  <section class="admin-panel">
    <p class="kicker">Team access</p><h2>Current administrators</h2>
    <div class="account-list">
      <?php foreach ($users as $user): ?>
        <article>
          <span class="account-avatar"><?=e(strtoupper(mb_substr((string)$user['name'], 0, 1)))?></span>
          <div><strong><?=e($user['name'])?></strong><small><?=e($user['email'])?></small><?php if ($user['last_login_at']): ?><small>Last sign-in <?=e($user['last_login_at'])?></small><?php endif; ?></div>
          <?php if ((string)$user['id'] === $currentId): ?><span class="status-pill status-published">You</span><?php else: ?>
            <form method="post" onsubmit="return confirm('Remove this administrator account?')"><input type="hidden" name="csrf" value="<?=csrfToken()?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="user_id" value="<?=e((string)$user['id'])?>"><button class="button button-small button-danger" type="submit">Remove</button></form>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
  <section class="admin-panel admin-form">
    <p class="kicker">Invite securely</p><h2>Add an administrator</h2><p>Share the initial password privately and ask the new administrator to change it after signing in.</p>
    <form method="post"><input type="hidden" name="csrf" value="<?=csrfToken()?>"><input type="hidden" name="action" value="create"><label>Name<input name="name" required></label><label>Email<input type="email" name="email" required></label><label>Temporary password<input type="password" name="password" minlength="12" autocomplete="new-password" required><small>At least 12 characters.</small></label><button class="button button-primary" type="submit">Create account</button></form>
  </section>
</div>
<div class="admin-two-column">
  <section class="admin-panel admin-form"><p class="kicker">Your details</p><h2>Profile</h2><form method="post"><input type="hidden" name="csrf" value="<?=csrfToken()?>"><input type="hidden" name="action" value="profile"><label>Name<input name="name" value="<?=e($currentUser['name'])?>" required></label><label>Email<input type="email" name="email" value="<?=e($currentUser['email'])?>" required></label><button class="button button-outline" type="submit">Save profile</button></form></section>
  <section class="admin-panel admin-form"><p class="kicker">Sign-in security</p><h2>Change password</h2><form method="post"><input type="hidden" name="csrf" value="<?=csrfToken()?>"><input type="hidden" name="action" value="password"><label>Current password<input type="password" name="current_password" autocomplete="current-password" required></label><label>New password<input type="password" name="new_password" minlength="12" autocomplete="new-password" required></label><button class="button button-outline" type="submit">Change password</button></form></section>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
