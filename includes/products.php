<?php
declare(strict_types=1);

require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/includes/settings.php';

function productDefaults(): array
{
    return [
        'id'=>0, 'slug'=>'', 'name'=>'', 'short_description'=>'',
        'description'=>'', 'image_url'=>'', 'badge'=>'',
        'pricing_mode'=>'fixed', 'price'=>'0.00',
        'suggested_amounts'=>'10,25,50', 'minimum_amount'=>'1.00',
        'maximum_amount'=>null, 'allow_custom_amount'=>1,
        'button_label'=>'Continue with PayPal', 'fulfillment_note'=>'',
        'display_order'=>10, 'status'=>'draft',
    ];
}

function productsAvailable(): bool
{
    return databaseTableExists('wts_products');
}

function allProducts(bool $publishedOnly = false): array
{
    $db = database();
    if (!$db || !productsAvailable()) return [];
    try {
        $sql = 'SELECT * FROM wts_products' . ($publishedOnly ? " WHERE status='published'" : '') . ' ORDER BY display_order,name';
        return $db->query($sql)->fetchAll();
    } catch (PDOException $exception) {
        error_log('Product catalog load failed: ' . $exception->getMessage());
        return [];
    }
}

function productBySlug(string $slug, bool $publishedOnly = true): ?array
{
    $db = database();
    if (!$db || !productsAvailable()) return null;
    try {
        $sql = 'SELECT * FROM wts_products WHERE slug=?' . ($publishedOnly ? " AND status='published'" : '') . ' LIMIT 1';
        $statement = $db->prepare($sql);
        $statement->execute([$slug]);
        $product = $statement->fetch();
        return $product ?: null;
    } catch (PDOException $exception) {
        return null;
    }
}

function productSuggestedAmounts(array $product): array
{
    $amounts = preg_split('/[\s,]+/', (string) ($product['suggested_amounts'] ?? '')) ?: [];
    $minimum = (float) ($product['minimum_amount'] ?? 1);
    $maximum = ($product['maximum_amount'] ?? '') !== '' ? (float) $product['maximum_amount'] : null;
    $valid = [];
    foreach ($amounts as $amount) {
        if (!is_numeric($amount)) continue;
        $number = round((float) $amount, 2);
        if ($number < $minimum || ($maximum !== null && $number > $maximum)) continue;
        $valid[number_format($number, 2, '.', '')] = $number;
    }
    return array_values($valid);
}

function paypalSettings(): array
{
    return [
        'businessEmail'=>trim((string) appSetting('paypal.businessEmail', '')),
        'currency'=>strtoupper(trim((string) appSetting('paypal.currency', 'USD'))),
        'donationButtonId'=>trim((string) appSetting('paypal.donationButtonId', 'RRW8F7NRZ4VDQ')),
    ];
}

function paypalIsConfigured(): bool
{
    $settings = paypalSettings();
    return filter_var($settings['businessEmail'], FILTER_VALIDATE_EMAIL) !== false || $settings['donationButtonId'] !== '';
}

function formatMoney(float $amount, ?string $currency = null): string
{
    $currency = $currency ?: paypalSettings()['currency'];
    return ($currency === 'USD' ? '$' : $currency . ' ') . number_format($amount, 2);
}

function productImageUrl(array $product): string
{
    $image = trim((string) ($product['image_url'] ?? ''));
    if ($image === '') return '';
    return preg_match('#^https://#i', $image) ? $image : url($image);
}

function validateProductAmount(array $product, mixed $submittedAmount): float
{
    if (($product['pricing_mode'] ?? 'fixed') === 'fixed') {
        $amount = round((float) $product['price'], 2);
        if ($amount <= 0) throw new InvalidArgumentException('This item does not have a valid price yet.');
        return $amount;
    }
    if (!is_scalar($submittedAmount) || !is_numeric((string) $submittedAmount)) {
        throw new InvalidArgumentException('Choose or enter a valid amount.');
    }
    $amount = round((float) $submittedAmount, 2);
    $minimum = max(0.01, (float) ($product['minimum_amount'] ?? 1));
    $maximum = ($product['maximum_amount'] ?? '') !== '' ? (float) $product['maximum_amount'] : null;
    $matchesSuggestion = in_array($amount, productSuggestedAmounts($product), true);
    if (empty($product['allow_custom_amount']) && !$matchesSuggestion) {
        throw new InvalidArgumentException('Choose one of the suggested amounts.');
    }
    if ($amount < $minimum) throw new InvalidArgumentException('The minimum amount is ' . formatMoney($minimum) . '.');
    if ($maximum !== null && $amount > $maximum) throw new InvalidArgumentException('The maximum amount is ' . formatMoney($maximum) . '.');
    return $amount;
}

function paypalCheckoutFields(array $product, float $amount): array
{
    $settings = paypalSettings();
    if (filter_var($settings['businessEmail'], FILTER_VALIDATE_EMAIL)) {
        $fields = [
            'cmd'=>($product['pricing_mode'] ?? 'fixed') === 'contribution' ? '_donations' : '_xclick',
            'business'=>$settings['businessEmail'],
            'item_name'=>(string) $product['name'],
            'item_number'=>'WTS-' . (int) $product['id'],
            'amount'=>number_format($amount, 2, '.', ''),
            'currency_code'=>$settings['currency'],
            'no_note'=>'0',
        ];
        $returnUrl = applicationUrl('completed/');
        $cancelUrl = applicationUrl('cancel/');
        if (preg_match('#^https://#i', $returnUrl) && preg_match('#^https://#i', $cancelUrl)) {
            $fields['return'] = $returnUrl;
            $fields['cancel_return'] = $cancelUrl;
        }
        return [
            'action'=>'https://www.paypal.com/cgi-bin/webscr',
            'fields'=>$fields,
        ];
    }
    if (($product['pricing_mode'] ?? '') === 'contribution' && $settings['donationButtonId'] !== '') {
        return [
            'action'=>'https://www.paypal.com/donate',
            'fields'=>[
                'hosted_button_id'=>$settings['donationButtonId'],
                'amount'=>number_format($amount, 2, '.', ''),
                'currency_code'=>$settings['currency'],
            ],
        ];
    }
    throw new RuntimeException('PayPal checkout is not configured for this item.');
}
