<?php
// go.php — single URL for Facebook/Instagram ads that mints a one-time token, binds it to a cookie, and redirects

// ==== CONFIG ====
$TOKENS_FILE = __DIR__ . '/tokens.txt';   // file-based storage (ephemeral is OK)
$REQUIRE_FBCLID = true;                   // keep true in production
$REQUIRE_FB_REFERRER = false;             // referrer is unreliable; leave false
$TOKEN_TTL = 1800;                        // token time-to-live in seconds (30 min)
$COOKIE_NAME = 'funnel_sess';
$COOKIE_TTL  = 1800;                      // cookie TTL (seconds)
$REDIRECT_BLOCKED = '/blocked.html';

// No-cache
header('Cache-Control: no-store');

// 0) Allow desktop testing with ?test=1 (simulates fbclid)
if (isset($_GET['test']) && $_GET['test'] == '1' && !isset($_GET['fbclid'])) {
  $_GET['fbclid'] = 'TEST_FBCLID';
}

// 1) Ignore Facebook prefetchers to avoid burning tokens
$ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
if (strpos($ua, 'facebookexternalhit') !== false || strpos($ua, 'facebookcatalog') !== false) {
  http_response_code(204); // No Content
  exit;
}

// 2) fbclid required for real ad clicks
$fbclid = $_GET['fbclid'] ?? '';
if ($REQUIRE_FBCLID && $fbclid === '') {
  header("Location: $REDIRECT_BLOCKED", true, 302);
  exit;
}

// 3) Optional: referrer check (OFF by default)
if ($REQUIRE_FB_REFERRER) {
  $ref = $_SERVER['HTTP_REFERER'] ?? '';
  if (stripos($ref, 'facebook.com') === false && stripos($ref, 'fb.com') === false) {
    header("Location: $REDIRECT_BLOCKED", true, 302);
    exit;
  }
}

// 4) Ensure a session cookie exists; bind token to this cookie
$sess = $_COOKIE[$COOKIE_NAME] ?? bin2hex(random_bytes(16));
setcookie($COOKIE_NAME, $sess, [
  'expires'  => time() + $COOKIE_TTL,
  'path'     => '/',
  'secure'   => true,
  'httponly' => true,
  'samesite' => 'Lax',
]);

// 5) Mint a one-time token, store as "token|sess|exp"
$token = bin2hex(random_bytes(16));
$exp   = time() + $TOKEN_TTL;

// Ensure token store is writable
$fp = @fopen($TOKENS_FILE, 'a');
if (!$fp) {
  // Try temp dir as fallback
  $TOKENS_FILE = sys_get_temp_dir() . '/tokens.txt';
  $fp = @fopen($TOKENS_FILE, 'a');
  if (!$fp) {
    http_response_code(500);
    echo "Token store not writable.";
    exit;
  }
}
flock($fp, LOCK_EX);
fwrite($fp, $token . '|' . $sess . '|' . $exp . PHP_EOL);
flock($fp, LOCK_UN);
fclose($fp);

// 6) Redirect to guarded page
header('Location: /index.php?t=' . urlencode($token), true, 302);
exit;
