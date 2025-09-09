<?php
// go.php — mint token, set cookie, then HTML/JS redirect (avoids 302 cookie race)

// ==== CONFIG ====
$TOKENS_FILE = __DIR__ . '/tokens.txt';
$REQUIRE_FBCLID = true;        // keep true in production
$REQUIRE_FB_REFERRER = false;  // referrer is flaky; leave false
$TOKEN_TTL = 1800;             // 30 min
$COOKIE_NAME = 'funnel_sess';
$COOKIE_TTL  = 1800;           // 30 min
$REDIRECT_BLOCKED = '/blocked.html';

header('Cache-Control: no-store');

// Allow desktop testing without FB
if (isset($_GET['test']) && $_GET['test'] == '1' && !isset($_GET['fbclid'])) {
  $_GET['fbclid'] = 'TEST_FBCLID';
  $REQUIRE_FBCLID = false; // Disable fbclid requirement for testing
}

// Ignore Facebook crawler/prefetch
$ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
if (strpos($ua, 'facebookexternalhit') !== false || strpos($ua, 'facebookcatalog') !== false) {
  http_response_code(204);
  exit;
}

// Allow Lucky Orange verification bot to pass through
if (strpos($ua, 'luckyorange') !== false || strpos($ua, 'lucky orange') !== false) {
  // Create a temporary token for Lucky Orange verification
  $sess = bin2hex(random_bytes(16));
  $token = bin2hex(random_bytes(16));
  $exp = time() + 300; // 5 minutes
  
  $fp = @fopen($TOKENS_FILE, 'a');
  if (!$fp) {
    $TOKENS_FILE = sys_get_temp_dir() . '/tokens.txt';
    $fp = @fopen($TOKENS_FILE, 'a');
  }
  if ($fp) {
    flock($fp, LOCK_EX);
    fwrite($fp, $token . '|' . $sess . '|' . $exp . PHP_EOL);
    flock($fp, LOCK_UN);
    fclose($fp);
  }
  
  // Set cookie and redirect
  setcookie($COOKIE_NAME, $sess, ['expires' => time() + 300, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
  $next = '/index.php?t=' . urlencode($token);
  header("Location: $next", true, 302);
  exit;
}

// Require fbclid on real clicks
$fbclid = $_GET['fbclid'] ?? '';
if ($REQUIRE_FBCLID && $fbclid === '') {
  header("Location: $REDIRECT_BLOCKED", true, 302);
  exit;
}

// Optional referrer gate
if ($REQUIRE_FB_REFERRER) {
  $ref = $_SERVER['HTTP_REFERER'] ?? '';
  if (stripos($ref, 'facebook.com') === false && stripos($ref, 'fb.com') === false) {
    header("Location: $REDIRECT_BLOCKED", true, 302);
    exit;
  }
}

// Create/get session cookie (bind token to this)
$sess = $_COOKIE[$COOKIE_NAME] ?? bin2hex(random_bytes(16));

// For testing, also check if we're in test mode and adjust cookie settings
$isLocalTest = isset($_GET['test']) && $_GET['test'] == '1';
$cookieSettings = [
  'expires'  => time() + $COOKIE_TTL,
  'path'     => '/',
  'httponly' => true,
  'samesite' => 'Lax',
];

// Only require secure cookies in production (HTTPS)
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
  $cookieSettings['secure'] = true;
}

setcookie($COOKIE_NAME, $sess, $cookieSettings);

// Mint one-time token: token|sess|exp
$token = bin2hex(random_bytes(16));
$exp   = time() + $TOKEN_TTL;

// Try primary location first, fallback to temp directory
$fp = @fopen($TOKENS_FILE, 'a');
if (!$fp) {
  $TOKENS_FILE = sys_get_temp_dir() . '/tokens.txt';
  $fp = @fopen($TOKENS_FILE, 'a');
  if (!$fp) { http_response_code(500); echo "Token store not writable."; exit; }
}
flock($fp, LOCK_EX);
fwrite($fp, $token . '|' . $sess . '|' . $exp . PHP_EOL);
flock($fp, LOCK_UN);
fclose($fp);

// Instead of a 302, return a tiny page that JS-redirects to ensure cookie is persisted
$next = '/index.php?t=' . urlencode($token);
?>
<!doctype html>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Loading…</title>
<meta http-equiv="Cache-Control" content="no-store">
<script>
  // JS redirect (cookie now set on this document)
  location.replace(<?= json_encode($next) ?>);
</script>
<noscript>
  <meta http-equiv="refresh" content="0;url=<?= htmlspecialchars($next, ENT_QUOTES, 'UTF-8') ?>">
  <p>Continue to <a href="<?= htmlspecialchars($next, ENT_QUOTES, 'UTF-8') ?>">your page</a>.</p>
</noscript>
