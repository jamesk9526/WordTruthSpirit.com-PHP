<?php
declare(strict_types=1);

require_once ROOT_PATH . '/includes/settings.php';

function subscriptionSettings(): array
{
    return siteExperience()['subscription'];
}

function subscriptionStatus(): string
{
    $status = (string) ($_GET['subscribe'] ?? '');
    return in_array($status, ['pending', 'active', 'mail-error', 'error'], true) ? $status : '';
}

function subscriptionStatusMessage(): string
{
    return match (subscriptionStatus()) {
        'pending' => 'Almost there — check your inbox and confirm your email address.',
        'active' => 'You’re already subscribed. Thank you for staying connected.',
        'mail-error' => 'Your request was saved, but the confirmation email could not be sent. Please try again later.',
        'error' => 'Please enter a valid email address.',
        default => '',
    };
}

function subscriptionReturnPath(): string
{
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? url('blog/'));
    $parts = parse_url($uri);
    $path = (string) ($parts['path'] ?? url('blog/'));
    $query = [];
    if (!empty($parts['query'])) parse_str((string) $parts['query'], $query);
    unset($query['subscribe']);
    return $path . ($query ? '?' . http_build_query($query) : '');
}

function renderSubscriptionForm(string $source, string $variant = 'default', string $anchor = 'email-signup'): void
{
    $settings = subscriptionSettings();
    $fieldId = 'subscriber-email-' . preg_replace('/[^a-z0-9-]+/', '-', strtolower($source));
    $statusMessage = subscriptionStatusMessage();
    ?>
    <form id="<?= e($anchor) ?>" class="subscriber-form subscriber-form-<?= e($variant) ?>" action="<?= url('api/subscribe.php') ?>" method="post">
      <input type="hidden" name="source" value="<?= e($source) ?>">
      <input type="hidden" name="return_to" value="<?= e(subscriptionReturnPath()) ?>">
      <input type="hidden" name="return_anchor" value="<?= e($anchor) ?>">
      <input class="honeypot" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">
      <label class="subscriber-email-label" for="<?= e($fieldId) ?>">Email address</label>
      <div class="subscriber-form-row">
        <input id="<?= e($fieldId) ?>" type="email" name="email" maxlength="190" autocomplete="email" inputmode="email" placeholder="<?= e($settings['placeholder']) ?>" aria-describedby="<?= e($fieldId) ?>-privacy" required>
        <button class="button <?= $variant === 'light' ? 'button-light' : 'button-primary' ?>" type="submit"><?= e($settings['buttonLabel']) ?></button>
      </div>
      <small id="<?= e($fieldId) ?>-privacy" class="subscriber-privacy"><?= e($settings['privacyText']) ?></small>
      <?php if ($statusMessage !== ''): ?><p class="subscribe-notice subscribe-notice-<?= e(subscriptionStatus()) ?>" role="status"><?= e($statusMessage) ?></p><?php endif; ?>
    </form>
    <?php
}
