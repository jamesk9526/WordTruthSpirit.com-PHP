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

function paypalApiSettings(): array
{
    $mode = strtolower(trim((string) (getenv('PAYPAL_MODE') ?: 'live')));
    return [
        'mode'=>in_array($mode, ['live','sandbox'], true) ? $mode : 'live',
        'clientId'=>trim((string) getenv('PAYPAL_CLIENT_ID')),
        'clientSecret'=>trim((string) getenv('PAYPAL_CLIENT_SECRET')),
    ];
}

function paypalApiIsConfigured(): bool
{
    $settings = paypalApiSettings();
    return $settings['clientId'] !== '' && $settings['clientSecret'] !== '';
}

function paypalApiBaseUrl(): string
{
    return paypalApiSettings()['mode'] === 'sandbox' ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';
}

function paypalApiRequest(string $method, string $path, string $accessToken, mixed $payload = null, array $headers = []): array
{
    if (!function_exists('curl_init')) throw new RuntimeException('The server must enable cURL to process PayPal checkout.');
    $handle = curl_init(paypalApiBaseUrl() . $path);
    $requestHeaders = array_merge(['Accept: application/json', 'Authorization: Bearer ' . $accessToken], $headers);
    if ($payload !== null) $requestHeaders[] = 'Content-Type: application/json';
    curl_setopt_array($handle, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_CUSTOMREQUEST=>$method, CURLOPT_HTTPHEADER=>$requestHeaders, CURLOPT_TIMEOUT=>20, CURLOPT_CONNECTTIMEOUT=>10]);
    if ($payload !== null) curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    $response = curl_exec($handle); $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE); $curlError = curl_error($handle); curl_close($handle);
    if (!is_string($response) || $status < 200 || $status >= 300) {
        error_log('PayPal API request failed with HTTP ' . $status . ($curlError !== '' ? ' (network error).' : '.'));
        throw new RuntimeException('PayPal could not process this checkout request. Please try again in a moment.');
    }
    $decoded = json_decode($response, true);
    if (!is_array($decoded)) throw new RuntimeException('PayPal returned an unexpected checkout response.');
    return $decoded;
}

function paypalAccessToken(): string
{
    $settings = paypalApiSettings();
    if (!paypalApiIsConfigured()) throw new RuntimeException('PayPal API checkout is not configured.');
    if (!function_exists('curl_init')) throw new RuntimeException('The server must enable cURL to process PayPal checkout.');
    $handle = curl_init(paypalApiBaseUrl() . '/v1/oauth2/token');
    curl_setopt_array($handle, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_POST=>true, CURLOPT_USERPWD=>$settings['clientId'] . ':' . $settings['clientSecret'], CURLOPT_HTTPAUTH=>CURLAUTH_BASIC, CURLOPT_HTTPHEADER=>['Accept: application/json', 'Accept-Language: en_US', 'Content-Type: application/x-www-form-urlencoded'], CURLOPT_POSTFIELDS=>'grant_type=client_credentials', CURLOPT_TIMEOUT=>20, CURLOPT_CONNECTTIMEOUT=>10]);
    $response = curl_exec($handle); $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE); curl_close($handle);
    $decoded = is_string($response) ? json_decode($response, true) : null;
    if ($status < 200 || $status >= 300 || !is_array($decoded) || empty($decoded['access_token'])) throw new RuntimeException('PayPal authorization failed. Check the server-side API credentials and selected mode.');
    return (string) $decoded['access_token'];
}

function paypalCreateOrder(array $product, float $amount, ?string $purchaseReference = null): array
{
    $returnUrl = applicationUrl('completed/'); $cancelUrl = applicationUrl('cancel/');
    if (!preg_match('#^https://#i', $returnUrl) || !preg_match('#^https://#i', $cancelUrl)) throw new RuntimeException('Set APP_URL to this site’s HTTPS address before enabling PayPal API checkout.');
    $currency = paypalSettings()['currency'];
    $unit = ['reference_id'=>'WTS-' . (int) $product['id'], 'description'=>(string) $product['name'], 'amount'=>['currency_code'=>$currency, 'value'=>number_format($amount, 2, '.', '')]];
    if ($purchaseReference !== null) $unit['custom_id'] = $purchaseReference;
    if (($product['pricing_mode'] ?? 'fixed') === 'fixed') $unit['items'] = [['name'=>(string) $product['name'], 'sku'=>'WTS-' . (int) $product['id'], 'quantity'=>'1', 'unit_amount'=>['currency_code'=>$currency, 'value'=>number_format($amount, 2, '.', '')]]];
    $payload = ['intent'=>'CAPTURE', 'purchase_units'=>[$unit], 'payment_source'=>['paypal'=>['experience_context'=>['return_url'=>$returnUrl, 'cancel_url'=>$cancelUrl, 'user_action'=>'PAY_NOW', 'shipping_preference'=>'NO_SHIPPING']]]];
    $order = paypalApiRequest('POST', '/v2/checkout/orders', paypalAccessToken(), $payload, ['Prefer: return=representation', 'PayPal-Request-Id: ' . uuidV4()]);
    $approvalUrl = '';
    foreach (($order['links'] ?? []) as $link) if (($link['rel'] ?? '') === 'approve' && !empty($link['href'])) { $approvalUrl = (string) $link['href']; break; }
    if (empty($order['id']) || $approvalUrl === '') throw new RuntimeException('PayPal did not provide an approval link for this checkout.');
    return ['id'=>(string) $order['id'], 'approvalUrl'=>$approvalUrl];
}

function paypalCaptureOrder(string $orderId): array
{
    if (!preg_match('/^[A-Z0-9]+$/i', $orderId)) throw new RuntimeException('The PayPal order reference is invalid.');
    return paypalApiRequest('POST', '/v2/checkout/orders/' . rawurlencode($orderId) . '/capture', paypalAccessToken(), new stdClass(), ['Prefer: return=representation', 'PayPal-Request-Id: ' . uuidV4()]);
}

function paypalCaptureTransactionId(array $order): string
{
    return (string) ($order['purchase_units'][0]['payments']['captures'][0]['id'] ?? '');
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
