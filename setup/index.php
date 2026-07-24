<?php
declare(strict_types=1);

session_set_cookie_params(['httponly' => true, 'samesite' => 'Strict', 'secure' => !empty($_SERVER['HTTPS'])]);
session_start();

$root = dirname(__DIR__);
$lockFile = $root . '/.setup-complete';
$complete = is_file($lockFile);
$errors = [];
$success = false;

if (empty($_SESSION['setup_csrf'])) {
    $_SESSION['setup_csrf'] = bin2hex(random_bytes(24));
}

function setupEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function envValue(string $value): string
{
    return '"' . addcslashes($value, "\\\"\r\n") . '"';
}

function runSqlBatch(mysqli $connection, string $sql): void
{
    if (!$connection->multi_query($sql)) {
        throw new RuntimeException('SQL import failed: ' . $connection->error);
    }
    do {
        if ($result = $connection->store_result()) $result->free();
        if (!$connection->more_results()) break;
        if (!$connection->next_result()) {
            throw new RuntimeException('SQL import failed: ' . $connection->error);
        }
    } while (true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$complete) {
    if (!hash_equals((string) $_SESSION['setup_csrf'], (string) ($_POST['csrf'] ?? ''))) {
        $errors[] = 'The setup session expired. Reload the page and try again.';
    }

    $host = trim((string) ($_POST['db_host'] ?? '127.0.0.1'));
    $port = (int) ($_POST['db_port'] ?? 3306);
    $user = trim((string) ($_POST['db_user'] ?? ''));
    $password = (string) ($_POST['db_password'] ?? '');
    $basePath = rtrim(trim((string) ($_POST['base_path'] ?? '')), '/');
    $confirmed = isset($_POST['confirm_import']);

    if (!$confirmed) $errors[] = 'Confirm that the importer may create the wts database.';
    if ($host === '' || $user === '' || $port < 1 || $port > 65535) $errors[] = 'Enter valid MySQL connection details.';
    if (preg_match('/[\r\n]/', $host . $user . $password . $basePath)) $errors[] = 'Connection values cannot contain line breaks.';
    if ($basePath !== '' && !preg_match('#^/[a-zA-Z0-9/_-]+$#', $basePath)) $errors[] = 'The optional base path must begin with / and contain only URL-safe characters.';

    $upload = $_FILES['sql_file'] ?? null;
    if (!$upload || $upload['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Select the 127_0_0_1.sql export.';
    } elseif ($upload['size'] < 1000 || $upload['size'] > 25 * 1024 * 1024) {
        $errors[] = 'The SQL export must be between 1 KB and 25 MB.';
    } elseif (strtolower(pathinfo((string) $upload['name'], PATHINFO_EXTENSION)) !== 'sql') {
        $errors[] = 'The selected file must be a .sql export.';
    }

    if (!$errors) {
        mysqli_report(MYSQLI_REPORT_OFF);
        $connection = @new mysqli($host, $user, $password, '', $port);
        if ($connection->connect_errno) {
            $errors[] = 'Could not connect to MySQL. Check the host, port, username, and password.';
        } else {
            try {
                $connection->set_charset('utf8mb4');
                $check = $connection->query("SELECT COUNT(*) AS total FROM information_schema.tables WHERE table_schema='wts'");
                $existingTables = (int) ($check?->fetch_assoc()['total'] ?? 0);
                if ($existingTables > 0) {
                    throw new RuntimeException('The wts database already contains tables. Setup stopped to protect the existing database.');
                }

                $sql = file_get_contents((string) $upload['tmp_name']);
                if ($sql === false || !str_contains($sql, 'Database: `wts`') || !str_contains($sql, 'CREATE TABLE `posts`') || !str_contains($sql, 'CREATE TABLE `users`')) {
                    throw new RuntimeException('This does not appear to be the expected Word Truth Spirit SQL export.');
                }

                runSqlBatch($connection, $sql);
                $compatibilitySql = file_get_contents($root . '/database/127_0_0_1_compat.sql');
                if ($compatibilitySql === false) throw new RuntimeException('The Publications compatibility migration is missing.');
                runSqlBatch($connection, $compatibilitySql);

                $environment = implode(PHP_EOL, [
                    'APP_BASE_PATH=' . envValue($basePath),
                    'DB_HOST=' . envValue($host),
                    'DB_PORT=' . envValue((string) $port),
                    'DB_NAME="wts"',
                    'DB_USER=' . envValue($user),
                    'DB_PASS=' . envValue($password),
                    '',
                ]);
                $temporaryEnv = $root . '/.env.setup-' . bin2hex(random_bytes(5));
                if (file_put_contents($temporaryEnv, $environment, LOCK_EX) === false || !rename($temporaryEnv, $root . '/.env')) {
                    @unlink($temporaryEnv);
                    throw new RuntimeException('The database imported, but setup could not write .env. Check the project folder permissions.');
                }
                @chmod($root . '/.env', 0600);
                file_put_contents($lockFile, 'Completed ' . gmdate('c') . PHP_EOL, LOCK_EX);
                $success = true;
                $complete = true;
            } catch (Throwable $exception) {
                $errors[] = $exception->getMessage();
            } finally {
                $connection->close();
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Website Setup | Word Truth Spirit</title>
  <link rel="stylesheet" href="../assets/styles.css">
</head>
<body class="installer-body">
  <main class="installer">
    <header>
      <img src="../assets/images/logo.png" alt="Word Truth Spirit crest">
      <p class="kicker">Word Truth Spirit</p>
      <h1>Website setup</h1>
      <p>Connect MySQL and import the original Node-site database in one guarded step.</p>
    </header>

    <?php if ($success): ?>
      <section class="installer-card installer-success">
        <span>✓</span>
        <h2>Setup complete</h2>
        <p>The database, publications extension, and site connection have been configured. The installer is now locked.</p>
        <div class="button-row"><a class="button button-primary" href="../">Open website</a><a class="button button-outline" href="../admin/">Open administration</a></div>
      </section>
    <?php elseif ($complete): ?>
      <section class="installer-card">
        <h2>Setup is locked</h2>
        <p>This website has already been configured. Remove <code>.setup-complete</code> manually only when you intentionally need to run setup again.</p>
        <a class="button button-primary" href="../">Return to the website</a>
      </section>
    <?php else: ?>
      <section class="installer-card">
        <div class="installer-warning"><strong>Before continuing</strong><p>This installer will only import into an empty or nonexistent <code>wts</code> database. It refuses to overwrite an existing database.</p></div>
        <?php if ($errors): ?><div class="notice error" role="alert"><strong>Setup could not continue:</strong><ul><?php foreach ($errors as $error): ?><li><?= setupEscape($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <form method="post" enctype="multipart/form-data">
          <input type="hidden" name="csrf" value="<?= setupEscape($_SESSION['setup_csrf']) ?>">
          <fieldset>
            <legend>1. MySQL connection</legend>
            <div class="installer-grid">
              <label>Database host<input name="db_host" value="<?= setupEscape((string) ($_POST['db_host'] ?? '127.0.0.1')) ?>" required></label>
              <label>Port<input type="number" name="db_port" value="<?= setupEscape((string) ($_POST['db_port'] ?? '3306')) ?>" min="1" max="65535" required></label>
              <label>Username<input name="db_user" value="<?= setupEscape((string) ($_POST['db_user'] ?? 'root')) ?>" autocomplete="username" required></label>
              <label>Password<input type="password" name="db_password" autocomplete="current-password"></label>
            </div>
          </fieldset>
          <fieldset>
            <legend>2. Import database</legend>
            <label>Word Truth Spirit SQL export<input type="file" name="sql_file" accept=".sql,text/plain,application/sql" required><small>Select <strong>127_0_0_1.sql</strong>. Maximum size: 25 MB.</small></label>
          </fieldset>
          <fieldset>
            <legend>3. Website location</legend>
            <label>Optional subfolder path<input name="base_path" value="<?= setupEscape((string) ($_POST['base_path'] ?? '')) ?>" placeholder="/wordtruthspirit"><small>Leave blank when the site is installed at the domain root.</small></label>
          </fieldset>
          <label class="installer-confirm"><input type="checkbox" name="confirm_import" value="1" required> I understand this will create and import the <strong>wts</strong> database.</label>
          <button class="button button-primary" type="submit">Connect and import database →</button>
        </form>
      </section>
    <?php endif; ?>
  </main>
</body>
</html>
