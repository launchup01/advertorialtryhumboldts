<?php
// PHP one-time token guard implementation
// CONFIG
$TOKENS_FILE = __DIR__ . '/tokens.txt';
$ALLOWED_REFERRER_SUBSTR = '';
$REDIRECT_BLOCKED = '/blocked.html';
$REDIRECT_EXPIRED = '/expired.html';

// 1) Optional referrer check
if ($ALLOWED_REFERRER_SUBSTR !== '' && (empty($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], $ALLOWED_REFERRER_SUBSTR) === false)) {
  header("Location: $REDIRECT_BLOCKED", true, 302);
  exit;
}

// 2) Token required via ?t=
$token = $_GET['t'] ?? '';
if ($token === '') {
  header("Location: $REDIRECT_BLOCKED", true, 302);
  exit;
}

// 3) Validate and consume token
if (!file_exists($TOKENS_FILE)) {
  header("Location: $REDIRECT_EXPIRED", true, 302);
  exit;
}

$fp = fopen($TOKENS_FILE, 'c+');
if (!$fp) {
  header("Location: $REDIRECT_EXPIRED", true, 302);
  exit;
}
flock($fp, LOCK_EX);

// Read all tokens
$tokens = [];
rewind($fp);
while (($line = fgets($fp)) !== false) {
  $line = trim($line);
  if ($line !== '') {
    $tokens[$line] = true;
  }
}

// Token not found => invalid or reused
if (!isset($tokens[$token])) {
  flock($fp, LOCK_UN);
  fclose($fp);
  header("Location: $REDIRECT_EXPIRED", true, 302);
  exit;
}

// Consume the token
unset($tokens[$token]);
ftruncate($fp, 0);
rewind($fp);
if (!empty($tokens)) {
  fwrite($fp, implode(PHP_EOL, array_keys($tokens)) . PHP_EOL);
}
flock($fp, LOCK_UN);
fclose($fp);

// Include the original page content
include __DIR__ . '/index.original.html';
?>