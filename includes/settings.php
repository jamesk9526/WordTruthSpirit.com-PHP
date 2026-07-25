<?php
declare(strict_types=1);
require_once ROOT_PATH . '/config/database.php';

function defaultSiteExperience(): array
{
    return [
        'announcement' => ['enabled'=>true,'message'=>'Celebrate 250! For a limited time get the ebook, The Spirit of Truth for only $2.50!','actionLabel'=>'Get the eBook.','actionUrl'=>'https://www.amazon.com/dp/B0GCVNK21K?dplnkId=6cf4e3b0-74cc-491f-aa07-04ab89e08491','tone'=>'navy'],
        'popup' => ['enabled'=>true,'id'=>'welcome','eyebrow'=>'Stay connected','title'=>'Scripturally grounded. Spiritually edifying.','body'=>'Choose email updates to hear about new reflections, promotions, and resources.','actionLabel'=>'Explore the journal','actionUrl'=>'/blog/','delaySeconds'=>6],
        'subscription' => [
            'enabled'=>true,
            'eyebrow'=>'Stay connected',
            'title'=>'New reflections, delivered thoughtfully.',
            'body'=>'Receive Scripture-rooted teaching, new resources, and occasional ministry news from Patrick E. Pennington.',
            'buttonLabel'=>'Join the email list',
            'placeholder'=>'you@example.com',
            'privacyText'=>'Occasional, worthwhile email. Unsubscribe anytime.',
            'placements'=>['blogPanel'=>true,'articleEnd'=>true,'footer'=>true,'bottomBanner'=>true],
            'bottomBannerTitle'=>'Take the next reflection with you.',
            'bottomBannerBody'=>'Join the Word Truth Spirit email list for new teaching and ministry resources.',
            'bottomBannerDelaySeconds'=>10,
            'bottomBannerId'=>'email-updates-v1',
        ],
    ];
}

function appSetting(string $key, ?string $default = null): ?string
{
    $db = database();
    if (!$db) return $default;
    try {
        if (databaseUsesLegacySchema() && databaseTableExists('app_settings')) {
            $statement = $db->prepare('SELECT setting_value FROM app_settings WHERE setting_key=?');
        } elseif (databaseTableExists('wts_settings')) {
            $statement = $db->prepare('SELECT setting_value FROM wts_settings WHERE setting_key=?');
        } else return $default;
        $statement->execute([$key]); $value = $statement->fetchColumn();
        return $value === false ? $default : (string) $value;
    } catch (PDOException $exception) { return $default; }
}

function setAppSetting(string $key, string $value): bool
{
    $db = database(); if (!$db) return false;
    try {
        $table = databaseUsesLegacySchema() ? 'app_settings' : 'wts_settings';
        if (!databaseTableExists($table)) return false;
        $statement = $db->prepare("INSERT INTO {$table} (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_at=CURRENT_TIMESTAMP");
        return $statement->execute([$key,$value]);
    } catch (PDOException $exception) { return false; }
}

function siteExperience(): array
{
    $default = defaultSiteExperience();
    $stored = appSetting('site.experience');
    if (!$stored) return $default;
    $decoded = json_decode($stored, true);
    if (!is_array($decoded)) return $default;
    return array_replace_recursive($default, $decoded);
}

function applicationUrl(string $path = ''): string
{
    $base = rtrim((string) (getenv('APP_URL') ?: appSetting('site.publicUrl', '')), '/');
    if ($base === '') return url($path);
    return $base . '/' . ltrim($path, '/');
}

function updateEnvironment(array $changes): bool
{
    $file = ROOT_PATH . '/.env'; $lines = is_file($file) ? file($file, FILE_IGNORE_NEW_LINES) : [];
    $remaining = [];
    foreach ($lines ?: [] as $line) {
        $matched = false;
        foreach ($changes as $key => $_) if (str_starts_with($line, $key . '=')) { $matched = true; break; }
        if (!$matched) $remaining[] = $line;
    }
    foreach ($changes as $key => $value) $remaining[] = $key . '="' . addcslashes((string) $value, "\\\"\r\n") . '"';
    $temporary = $file . '.tmp-' . bin2hex(random_bytes(4));
    if (file_put_contents($temporary, implode(PHP_EOL, $remaining) . PHP_EOL, LOCK_EX) === false) return false;
    if (!rename($temporary, $file)) { @unlink($temporary); return false; }
    @chmod($file, 0600);
    foreach ($changes as $key => $value) putenv($key . '=' . (string) $value);
    return true;
}
