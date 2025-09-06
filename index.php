<?php
// === Option A: One-time token guard (file-based) ===
// CONFIG
$TOKENS_FILE = __DIR__ . '/tokens.txt';       // newline-separated tokens
$ALLOWED_REFERRER_SUBSTR = '';                // e.g. 'facebook.com' or leave '' to skip
$REDIRECT_BLOCKED = '/blocked.html';
$REDIRECT_EXPIRED = '/expired.html';

// 1) Referrer check (optional)
if ($ALLOWED_REFERRER_SUBSTR !== '' && (empty($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], $ALLOWED_REFERRER_SUBSTR) === false)) {
  header("Location: $REDIRECT_BLOCKED", true, 302);
  exit;
}

// 2) Token required
$token = $_GET['t'] ?? '';
if ($token === '') {
  header("Location: $REDIRECT_BLOCKED", true, 302);
  exit;
}

// 3) Validate + consume token (file-based, with lock)
if (!file_exists($TOKENS_FILE)) {
  header("Location: $REDIRECT_EXPIRED", true, 302);
  exit;
}

$fp = fopen($TOKENS_FILE, 'c+');
if (!$fp) { header("Location: $REDIRECT_EXPIRED", true, 302); exit; }
flock($fp, LOCK_EX);

// Read all tokens
$tokens = [];
rewind($fp);
while (($line = fgets($fp)) !== false) {
  $line = trim($line);
  if ($line !== '') $tokens[$line] = true;
}

// If token not present => already used or invalid
if (!isset($tokens[$token])) {
  flock($fp, LOCK_UN);
  fclose($fp);
  header("Location: $REDIRECT_EXPIRED", true, 302);
  exit;
}

// Consume (remove) token and rewrite file
unset($tokens[$token]);
ftruncate($fp, 0);
rewind($fp);
if (!empty($tokens)) {
  fwrite($fp, implode(PHP_EOL, array_keys($tokens)) . PHP_EOL);
}
flock($fp, LOCK_UN);
fclose($fp);

// If we get here, the token is valid and consumed. Continue rendering the page...
include __DIR__ . '/index.original.html';
?>