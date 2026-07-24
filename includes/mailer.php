<?php
declare(strict_types=1);
require_once ROOT_PATH . '/includes/settings.php';

function smtpConfigured(): bool { return (bool) (getenv('SMTP_HOST') && getenv('SMTP_USER') && getenv('SMTP_PASS') && getenv('SMTP_FROM_EMAIL')); }
function smtpRead($socket, array $expected): string { $response=''; do { $line=fgets($socket, 515); if($line===false) break; $response.=$line; } while (isset($line[3]) && $line[3]==='-'); $code=(int)substr($response,0,3); if(!in_array($code,$expected,true)) throw new RuntimeException('SMTP response failure'); return $response; }
function smtpWrite($socket, string $command, array $expected): void { fwrite($socket, $command."\r\n"); smtpRead($socket,$expected); }
function smtpSend(string $to, string $subject, string $html, string $text = ''): bool
{
    if (!smtpConfigured() || !filter_var($to, FILTER_VALIDATE_EMAIL)) return false;
    try {
        $host=(string)getenv('SMTP_HOST'); $port=(int)(getenv('SMTP_PORT')?:465); $encryption=strtolower((string)(getenv('SMTP_ENCRYPTION')?:'ssl'));
        $target=($encryption==='ssl'?'ssl://':'').$host.':'.$port;
        $socket=stream_socket_client($target,$errno,$error,15,STREAM_CLIENT_CONNECT);
        if(!$socket) throw new RuntimeException('Unable to connect to SMTP'); stream_set_timeout($socket,15);
        smtpRead($socket,[220]); smtpWrite($socket,'EHLO '.($_SERVER['HTTP_HOST']??'localhost'),[250]);
        if($encryption==='tls'){ smtpWrite($socket,'STARTTLS',[220]); if(!stream_socket_enable_crypto($socket,true,STREAM_CRYPTO_METHOD_TLS_CLIENT)) throw new RuntimeException('TLS failed'); smtpWrite($socket,'EHLO '.($_SERVER['HTTP_HOST']??'localhost'),[250]); }
        smtpWrite($socket,'AUTH LOGIN',[334]); smtpWrite($socket,base64_encode((string)getenv('SMTP_USER')),[334]); smtpWrite($socket,base64_encode((string)getenv('SMTP_PASS')),[235]);
        $from=(string)getenv('SMTP_FROM_EMAIL'); smtpWrite($socket,'MAIL FROM:<'.$from.'>',[250]); smtpWrite($socket,'RCPT TO:<'.$to.'>',[250,251]); smtpWrite($socket,'DATA',[354]);
        $boundary='wts_'.bin2hex(random_bytes(10)); $fromName=(string)(getenv('SMTP_FROM_NAME')?:'Word Truth Spirit');
        $headers=['From: =?UTF-8?B?'.base64_encode($fromName).'?= <'.$from.'>','To: <'.$to.'>','Subject: =?UTF-8?B?'.base64_encode($subject).'?=','MIME-Version: 1.0','Content-Type: multipart/alternative; boundary="'.$boundary.'"'];
        $message=implode("\r\n",$headers)."\r\n\r\n--{$boundary}\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n".($text?:strip_tags($html))."\r\n--{$boundary}\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n".$html."\r\n--{$boundary}--\r\n.";
        fwrite($socket,$message."\r\n"); smtpRead($socket,[250]); smtpWrite($socket,'QUIT',[221]); fclose($socket); return true;
    } catch (Throwable $exception) { error_log('SMTP delivery failed: '.$exception->getMessage()); return false; }
}
