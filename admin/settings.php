<?php
declare(strict_types=1);
require __DIR__ . '/auth.php'; requireAdmin(); require ROOT_PATH . '/includes/settings.php'; require ROOT_PATH . '/includes/mailer.php'; require ROOT_PATH . '/includes/push.php';
$error='';$saved=false;$testResult='';$experience=siteExperience();$pushConfigured=pushNotificationsConfigured();$pushConfiguration=pushConfiguration();$pushCount=$pushConfigured?pushSubscriptionCount():0;
if($_SERVER['REQUEST_METHOD']==='POST'){
  verifyCsrf();
  $subscriptionTitle=trim((string)($_POST['subscription_title']??''));
  $subscriptionBody=trim((string)($_POST['subscription_body']??''));
  $bottomBannerTitle=trim((string)($_POST['subscription_banner_title']??''));
  $bottomBannerBody=trim((string)($_POST['subscription_banner_body']??''));
  $experience=[
    'announcement'=>['enabled'=>isset($_POST['announcement_enabled']),'message'=>trim((string)($_POST['announcement_message']??'')),'actionLabel'=>trim((string)($_POST['announcement_label']??'')),'actionUrl'=>trim((string)($_POST['announcement_url']??'')),'tone'=>'navy'],
    'popup'=>['enabled'=>isset($_POST['popup_enabled']),'id'=>'welcome','eyebrow'=>trim((string)($_POST['popup_eyebrow']??'')),'title'=>trim((string)($_POST['popup_title']??'')),'body'=>trim((string)($_POST['popup_body']??'')),'actionLabel'=>trim((string)($_POST['popup_label']??'')),'actionUrl'=>trim((string)($_POST['popup_url']??'')),'delaySeconds'=>max(0,min(60,(int)($_POST['popup_delay']??6)))],
    'subscription'=>[
      'enabled'=>isset($_POST['subscription_enabled']),
      'eyebrow'=>trim((string)($_POST['subscription_eyebrow']??'')),
      'title'=>$subscriptionTitle,
      'body'=>$subscriptionBody,
      'buttonLabel'=>trim((string)($_POST['subscription_button_label']??'')),
      'placeholder'=>trim((string)($_POST['subscription_placeholder']??'')),
      'privacyText'=>trim((string)($_POST['subscription_privacy']??'')),
      'placements'=>[
        'blogPanel'=>isset($_POST['subscription_blog_panel']),
        'articleEnd'=>isset($_POST['subscription_article_end']),
        'footer'=>isset($_POST['subscription_footer']),
        'bottomBanner'=>isset($_POST['subscription_bottom_banner']),
      ],
      'bottomBannerTitle'=>$bottomBannerTitle,
      'bottomBannerBody'=>$bottomBannerBody,
      'bottomBannerDelaySeconds'=>max(0,min(120,(int)($_POST['subscription_banner_delay']??10))),
      'bottomBannerId'=>'email-'.substr(hash('sha256',$bottomBannerTitle.'|'.$bottomBannerBody),0,12),
    ],
  ];
  if(!$experience['announcement']['message']||!$experience['popup']['title'])$error='Enter the required promotion copy.';
  elseif($experience['subscription']['enabled']&&(!$subscriptionTitle||!$subscriptionBody||!$experience['subscription']['buttonLabel']||!$experience['subscription']['placeholder']))$error='Complete the required email signup copy.';
  elseif($experience['subscription']['enabled']&&$experience['subscription']['placements']['bottomBanner']&&(!$bottomBannerTitle||!$bottomBannerBody))$error='Complete the bottom banner title and message.';
  elseif(($paypalEmail=trim((string)($_POST['paypal_business_email']??'')))!==''&&!filter_var($paypalEmail,FILTER_VALIDATE_EMAIL))$error='Enter a valid PayPal merchant email address.';
  elseif(!in_array(($paypalCurrency=strtoupper(trim((string)($_POST['paypal_currency']??'USD')))),['USD','CAD','EUR','GBP','AUD'],true))$error='Choose a supported PayPal currency.';
  elseif(!setAppSetting('site.experience',json_encode($experience,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE))||!setAppSetting('ministry.donationUrl',trim((string)($_POST['donation_url']??'')))||!setAppSetting('ministry.donationLabel',trim((string)($_POST['donation_label']??'Support the ministry')))||!setAppSetting('paypal.businessEmail',$paypalEmail)||!setAppSetting('paypal.currency',$paypalCurrency)||!setAppSetting('paypal.donationButtonId',trim((string)($_POST['paypal_donation_button_id']??'')))||!setAppSetting('seo.googleVerification',trim((string)($_POST['google_verification']??'')))||!setAppSetting('seo.searchConsoleProperty',trim((string)($_POST['search_console_property']??''))))$error='Could not save settings. Confirm the database is connected.';
  else{
    $env=['SMTP_HOST'=>trim((string)($_POST['smtp_host']??'')),'SMTP_PORT'=>trim((string)($_POST['smtp_port']??'465')),'SMTP_USER'=>trim((string)($_POST['smtp_user']??'')),'SMTP_FROM_EMAIL'=>trim((string)($_POST['smtp_from_email']??'')),'SMTP_FROM_NAME'=>trim((string)($_POST['smtp_from_name']??'Word Truth Spirit')),'SMTP_ENCRYPTION'=>trim((string)($_POST['smtp_encryption']??'ssl')),'CONTACT_TO_EMAIL'=>trim((string)($_POST['contact_to_email']??''))];
    if(trim((string)($_POST['smtp_pass']??''))!=='')$env['SMTP_PASS']=(string)$_POST['smtp_pass'];
    if(!updateEnvironment($env))$error='Promotion settings were saved, but the SMTP configuration file could not be updated.';else {
      $saved=true;
      if(($_POST['action']??'save')==='test_smtp'){
        $testEmail=trim((string)($_POST['smtp_test_email']??''));
        if(!filter_var($testEmail,FILTER_VALIDATE_EMAIL))$error='Enter a valid test-recipient email address.';
        elseif(smtpSend($testEmail,'Word Truth Spirit SMTP test','<p>This is a successful SMTP test from the Word Truth Spirit administration workspace.</p><p>If you received this message, the current sender configuration can deliver email.</p>','This is a successful SMTP test from the Word Truth Spirit administration workspace.'))$testResult='Test email sent to '.$testEmail.'.';
        else $error='The SMTP test could not be delivered. Recheck the host, port, encryption, username, password, and sender address.';
      }
    }
  }
}
$adminTitle='Promotions & SMTP';$currentAdminPage='settings';require __DIR__.'/_header.php';
?>
<header class="admin-title"><div><p class="kicker">Site controls</p><h1>Promotions &amp; SMTP</h1><p>Control announcements, email signup placements, sender identity, and delivery settings. Custom homepage advertising is managed in Ads.</p></div></header>
<form method="post" class="settings-layout"><input type="hidden" name="csrf" value="<?=csrfToken()?>"><?php if($error):?><p class="notice error"><?=e($error)?></p><?php elseif($testResult):?><p class="notice success"><?=e($testResult)?></p><?php elseif($saved):?><p class="notice success">Settings saved. The next page request will use the new promotion and mail configuration.</p><?php endif;?>
<section class="admin-panel settings-section"><h2>Announcement banner</h2><label class="toggle-label"><input type="checkbox" name="announcement_enabled" <?=$experience['announcement']['enabled']?'checked':''?>> Show on the public website</label><label>Message<input name="announcement_message" value="<?=e($experience['announcement']['message'])?>" required></label><div class="admin-form-grid"><label>Button label<input name="announcement_label" value="<?=e($experience['announcement']['actionLabel'])?>"></label><label>Button URL<input name="announcement_url" value="<?=e($experience['announcement']['actionUrl'])?>"></label></div></section>
<section class="admin-panel settings-section"><h2>Welcome popup</h2><label class="toggle-label"><input type="checkbox" name="popup_enabled" <?=$experience['popup']['enabled']?'checked':''?>> Show once per browser session</label><div class="admin-form-grid"><label>Eyebrow<input name="popup_eyebrow" value="<?=e($experience['popup']['eyebrow'])?>"></label><label>Delay in seconds<input type="number" min="0" max="60" name="popup_delay" value="<?= (int)$experience['popup']['delaySeconds']?>"></label></div><label>Title<input name="popup_title" value="<?=e($experience['popup']['title'])?>" required></label><label>Message<textarea name="popup_body" rows="4"><?=e($experience['popup']['body'])?></textarea></label><div class="admin-form-grid"><label>Button label<input name="popup_label" value="<?=e($experience['popup']['actionLabel'])?>"></label><label>Button URL<input name="popup_url" value="<?=e($experience['popup']['actionUrl'])?>"></label></div></section>
<section class="admin-panel settings-section">
  <h2>Email subscriber signup</h2>
  <p>Choose where readers can subscribe and keep the invitation consistent across the blog, article pages, footer, and dismissible bottom-page banner.</p>
  <label class="toggle-label"><input type="checkbox" name="subscription_enabled" <?=$experience['subscription']['enabled']?'checked':''?>> Enable public email signup</label>
  <div class="placement-options">
    <label class="toggle-label"><input type="checkbox" name="subscription_blog_panel" <?=$experience['subscription']['placements']['blogPanel']?'checked':''?>> Blog signup panel</label>
    <label class="toggle-label"><input type="checkbox" name="subscription_article_end" <?=$experience['subscription']['placements']['articleEnd']?'checked':''?>> End of each reflection</label>
    <label class="toggle-label"><input type="checkbox" name="subscription_footer" <?=$experience['subscription']['placements']['footer']?'checked':''?>> Website footer</label>
    <label class="toggle-label"><input type="checkbox" name="subscription_bottom_banner" <?=$experience['subscription']['placements']['bottomBanner']?'checked':''?>> Dismissible bottom-page banner</label>
  </div>
  <div class="admin-form-grid"><label>Eyebrow<input name="subscription_eyebrow" value="<?=e($experience['subscription']['eyebrow'])?>"></label><label>Button label<input name="subscription_button_label" value="<?=e($experience['subscription']['buttonLabel'])?>" required></label></div>
  <label>Signup title<input name="subscription_title" value="<?=e($experience['subscription']['title'])?>" required></label>
  <label>Signup message<textarea name="subscription_body" rows="3" required><?=e($experience['subscription']['body'])?></textarea></label>
  <div class="admin-form-grid"><label>Email placeholder<input name="subscription_placeholder" value="<?=e($experience['subscription']['placeholder'])?>" required></label><label>Privacy reassurance<input name="subscription_privacy" value="<?=e($experience['subscription']['privacyText'])?>"></label></div>
  <div class="settings-subsection"><h3>Bottom-page banner</h3><p>This compact banner appears after a short delay and stays dismissed for the browser session.</p><label>Banner title<input name="subscription_banner_title" value="<?=e($experience['subscription']['bottomBannerTitle'])?>"></label><label>Banner message<textarea name="subscription_banner_body" rows="2"><?=e($experience['subscription']['bottomBannerBody'])?></textarea></label><label>Delay in seconds<input type="number" min="0" max="120" name="subscription_banner_delay" value="<?=(int)$experience['subscription']['bottomBannerDelaySeconds']?>"></label></div>
</section>
<section class="admin-panel settings-section" id="paypal-settings"><p class="kicker">Commerce</p><h2>PayPal checkout</h2><p>The merchant email enables both fixed-price products and contribution items. The hosted donation button remains available as a fallback for giving items.</p><div class="admin-form-grid"><label>PayPal merchant email<input type="email" name="paypal_business_email" value="<?=e((string)appSetting('paypal.businessEmail',''))?>" placeholder="payments@example.com"><small>Required for fixed-price product checkout.</small></label><label>Currency<select name="paypal_currency"><?php foreach(['USD'=>'USD — US Dollar','CAD'=>'CAD — Canadian Dollar','EUR'=>'EUR — Euro','GBP'=>'GBP — Pound Sterling','AUD'=>'AUD — Australian Dollar'] as $code=>$label):?><option value="<?=$code?>" <?=appSetting('paypal.currency','USD')===$code?'selected':''?>><?=$label?></option><?php endforeach;?></select></label></div><label>Hosted donation button ID<input name="paypal_donation_button_id" value="<?=e((string)appSetting('paypal.donationButtonId','RRW8F7NRZ4VDQ'))?>" placeholder="RRW8F7NRZ4VDQ"><small>The value after <code>hosted_button_id=</code> in a PayPal donation URL.</small></label><div class="settings-subsection"><h3>Legacy giving link</h3><p>Kept for existing buttons and external campaigns.</p><label>PayPal donation URL<input type="url" name="donation_url" value="<?=e((string)appSetting('ministry.donationUrl',''))?>" placeholder="https://www.paypal.com/donate/?hosted_button_id=..."></label><label>Button label<input name="donation_label" value="<?=e((string)appSetting('ministry.donationLabel','Support the ministry'))?>" placeholder="Support the ministry"></label></div></section>
<section class="admin-panel settings-section"><h2>Google Search Console</h2><p>Paste the verification token from Google Search Console (not the entire meta tag), then verify this site in Google. Your property URL is saved for quick access from SEO Studio.</p><label>Verification token<input name="google_verification" value="<?=e((string)appSetting('seo.googleVerification',''))?>" placeholder="Google verification token"></label><label>Search Console property URL<input type="url" name="search_console_property" value="<?=e((string)appSetting('seo.searchConsoleProperty',''))?>" placeholder="https://www.wordtruthspirit.com/"></label></section>
<section class="admin-panel settings-section push-settings-section"><h2>Browser push notifications</h2><p>VAPID credentials let readers opt into browser alerts without exposing the private signing key to the public site.</p><div class="push-settings-status"><span class="status-pill status-<?=$pushConfigured?'active':'hidden'?>"><?=$pushConfigured?'Configured':'Needs setup'?></span><strong><?=$pushConfigured?$pushCount.' browser '.($pushCount===1?'subscription':'subscriptions'):'Add the VAPID values to .env'?></strong></div><dl class="push-settings-details"><div><dt>Subject</dt><dd><?=e($pushConfiguration['subject'])?></dd></div><div><dt>Public key</dt><dd><?=e($pushConfigured?substr($pushConfiguration['publicKey'],0,16).'…':'Not configured')?></dd></div></dl><p class="settings-help">The public site only receives the public key. Keep <code>VAPID_PRIVATE_KEY</code> in the ignored server-side <code>.env</code> file and never paste it into an admin field.</p></section>
<section class="admin-panel settings-section"><h2>SMTP delivery</h2><p>Passwords are stored only in <code>.env</code> and are never displayed here. Leave the password empty to keep the existing value.</p><div class="admin-form-grid three"><label>Host<input name="smtp_host" value="<?=e((string)getenv('SMTP_HOST'))?>" placeholder="smtp.example.com"></label><label>Port<input type="number" name="smtp_port" value="<?=e((string)(getenv('SMTP_PORT')?:'465'))?>"></label><label>Encryption<select name="smtp_encryption"><option value="ssl" <?=getenv('SMTP_ENCRYPTION')==='ssl'?'selected':''?>>SSL (465)</option><option value="tls" <?=getenv('SMTP_ENCRYPTION')==='tls'?'selected':''?>>TLS (587)</option><option value="none" <?=getenv('SMTP_ENCRYPTION')==='none'?'selected':''?>>None</option></select></label></div><div class="admin-form-grid"><label>Username<input name="smtp_user" value="<?=e((string)getenv('SMTP_USER'))?>"></label><label>Password<input type="password" name="smtp_pass" placeholder="Leave blank to keep current password"></label></div><div class="admin-form-grid"><label>From email<input type="email" name="smtp_from_email" value="<?=e((string)getenv('SMTP_FROM_EMAIL'))?>"></label><label>From name<input name="smtp_from_name" value="<?=e((string)(getenv('SMTP_FROM_NAME')?:'Word Truth Spirit'))?>"></label></div><label>Contact notification recipient<input type="email" name="contact_to_email" value="<?=e((string)(getenv('CONTACT_TO_EMAIL')?:appSetting('email.contactToEmail','')))?>"></label><div class="smtp-test"><label>Test recipient<input type="email" name="smtp_test_email" placeholder="you@example.com"></label><button class="button button-outline" name="action" value="test_smtp" type="submit">Send SMTP test</button></div></section>
<div class="button-row"><button class="button button-primary" name="action" value="save">Save site controls</button><a class="button button-outline" href="<?=url('admin/')?>">Cancel</a></div></form>
<?php require __DIR__.'/_footer.php'; ?>
