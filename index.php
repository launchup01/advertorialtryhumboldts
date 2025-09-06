<?php
// index.php — one-time token guard with cookie binding; includes index.original.html after validation

// ==== CONFIG ====
$TOKENS_FILE = __DIR__ . '/tokens.txt';
$ALLOWED_REFERRER_SUBSTR = ''; // keep empty; referrers are unreliable
$REDIRECT_BLOCKED = '/blocked.html';
$REDIRECT_EXPIRED = '/expired.html';
$COOKIE_NAME = 'funnel_sess';

// No-cache
header('Cache-Control: no-store');

// 1) Token required
$token = $_GET['t'] ?? '';
if ($token === '') {
  header("Location: $REDIRECT_BLOCKED", true, 302);
  exit;
}

// 2) Optional referrer gate (disabled by default)
if ($ALLOWED_REFERRER_SUBSTR !== '') {
  $ref = $_SERVER['HTTP_REFERER'] ?? '';
  if ($ref === '' || stripos($ref, $ALLOWED_REFERRER_SUBSTR) === false) {
    header("Location: $REDIRECT_BLOCKED", true, 302);
    exit;
  }
}

// 3) Validate + consume token (token|sess|exp)
if (!file_exists($TOKENS_FILE)) {
  // Try temp path fallback
  $TOKENS_FILE = sys_get_temp_dir() . '/tokens.txt';
  if (!file_exists($TOKENS_FILE)) {
    header("Location: $REDIRECT_EXPIRED", true, 302);
    exit;
  }
}

$fp = @fopen($TOKENS_FILE, 'c+');
if (!$fp) {
  header("Location: $REDIRECT_EXPIRED", true, 302);
  exit;
}
flock($fp, LOCK_EX);

// Read all lines
$rows = [];
rewind($fp);
while (($line = fgets($fp)) !== false) {
  $line = trim($line);
  if ($line !== '') $rows[] = $line;
}

// Find token
$found = false;
$newRows = [];
$expectedSess = null;
$exp = 0;
$now = time();

foreach ($rows as $row) {
  $parts = explode('|', $row);
  $tok = $parts[0] ?? '';
  $sess = $parts[1] ?? '';
  $ex   = isset($parts[2]) ? (int)$parts[2] : 0;

  if ($tok === $token) {
    $found = true;
    $expectedSess = $sess;
    $exp = $ex;
    // Do not copy this row back => consume it
  } else {
    $newRows[] = $row;
  }
}

// If not found => invalid or already used
if (!$found) {
  ftruncate($fp, 0);
  rewind($fp);
  if (!empty($newRows)) fwrite($fp, implode(PHP_EOL, $newRows) . PHP_EOL);
  flock($fp, LOCK_UN);
  fclose($fp);
  header("Location: $REDIRECT_EXPIRED", true, 302);
  exit;
}

// 4) Cookie binding + TTL
$clientSess = $_COOKIE[$COOKIE_NAME] ?? '';
if ($clientSess === '' || $clientSess !== $expectedSess || $now > $exp) {
  ftruncate($fp, 0);
  rewind($fp);
  if (!empty($newRows)) fwrite($fp, implode(PHP_EOL, $newRows) . PHP_EOL);
  flock($fp, LOCK_UN);
  fclose($fp);
  header("Location: $REDIRECT_EXPIRED", true, 302);
  exit;
}

// 5) Persist consumption
ftruncate($fp, 0);
rewind($fp);
if (!empty($newRows)) fwrite($fp, implode(PHP_EOL, $newRows) . PHP_EOL);
flock($fp, LOCK_UN);
fclose($fp);

// 6) Render content
$landing = __DIR__ . '/index.original.html';
if (is_file($landing)) {
  include $landing;
} else {
  echo "<!doctype html><meta charset='utf-8'><title>Content missing</title><style>body{font:16px system-ui;padding:3rem;max-width:720px;margin:auto}</style><h1>index.original.html not found</h1><p>Add your landing HTML at <code>index.original.html</code>.</p>";
}
