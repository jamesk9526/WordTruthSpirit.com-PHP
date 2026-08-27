<?php
declare(strict_types=1);

require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/includes/mailer.php';

function memberSession(): void { if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) { session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')]); session_start(); } }
function memberCsrfToken(): string { memberSession(); return $_SESSION['wts_member_csrf'] ??= bin2hex(random_bytes(24)); }
function memberVerifyCsrf(): bool { memberSession(); return !empty($_POST['csrf']) && !empty($_SESSION['wts_member_csrf']) && hash_equals($_SESSION['wts_member_csrf'], (string)$_POST['csrf']); }
function memberLoggedIn(): bool { memberSession(); return !empty($_SESSION['wts_member_id']); }
function currentMember(): ?array { if (!memberLoggedIn() || !($db=database()) || !databaseTableExists('wts_members')) return null; $s=$db->prepare('SELECT id,display_name,profile_slug,email,email_confirmed_at,bio,created_at FROM wts_members WHERE id=? LIMIT 1'); $s->execute([(int)$_SESSION['wts_member_id']]); return $s->fetch() ?: null; }
function memberSlug(string $name): string { $base=trim(preg_replace('/-+/', '-', preg_replace('/[^a-z0-9]+/', '-', strtolower($name))) ?? '', '-'); return substr($base ?: 'reader',0,60).'-'.substr(bin2hex(random_bytes(4)),0,8); }
function memberConfirmationUrl(int $id, string $token): string { return applicationUrl('members/confirm.php?id='.$id.'&token='.rawurlencode($token)); }
function sendMemberConfirmation(array $member, string $token): bool { $link=memberConfirmationUrl((int)$member['id'],$token); $html='<h2>Confirm your Word Truth Spirit account</h2><p>Thank you for joining. Confirm your email address to activate sign-in and connect your profile to future comments.</p><p><a href="'.e($link).'">Confirm my email address</a></p><p>If you did not create this account, you can ignore this email.</p>'; return smtpSend((string)$member['email'],'Confirm your Word Truth Spirit account',$html,'Confirm your account: '.$link); }
function memberPurchases(int $memberId): array { if (!($db=database()) || !databaseTableExists('wts_purchase_intents')) return []; $s=$db->prepare('SELECT * FROM wts_purchase_intents WHERE member_id=? ORDER BY created_at DESC'); $s->execute([$memberId]); return $s->fetchAll(); }
function recordPurchaseIntent(int $memberId, array $product, float $amount): ?string { if (!($db=database()) || !databaseTableExists('wts_purchase_intents')) return null; $token=bin2hex(random_bytes(20)); $s=$db->prepare('INSERT INTO wts_purchase_intents (member_id,product_id,product_name,amount,currency,status,reference_token) VALUES (?,?,?,?,?,?,?)'); $s->execute([$memberId,(int)$product['id'],(string)$product['name'],$amount,paypalSettings()['currency'],'pending',$token]); return $token; }
function completePurchaseIntent(string $reference, string $transactionId): void { if ($reference==='' || !($db=database()) || !databaseTableExists('wts_purchase_intents')) return; try { $s=$db->prepare("UPDATE wts_purchase_intents SET status='paid',paypal_transaction_id=? WHERE reference_token=? AND status='pending'"); $s->execute([$transactionId,$reference]); } catch (PDOException $exception) { error_log('Purchase intent completion failed.'); } }
