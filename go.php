<?php
// go.php — Single-URL mint-and-redirect for Facebook Ads

// CONFIG
$TOKENS_FILE = __DIR__ . '/tokens.txt';
$REQUIRE_FB_REFERRER = true;     // set false while testing on desktop
$REQUIRE_FBCLID = true;          // require fbclid param appended by FB app/browser
$REDIRECT_BLOCKED = '/blocked.html';

// Allow a manual test override: ?test=1 adds a fake fbclid if missing
if (isset($_GET['test']) && !isset($_GET['fbclid'])) {
  $_GET['fbclid'] = 'TEST';
}

// Referrer check
if ($REQUIRE_FB_REFERRER) {
  $ref = $_SERVER['HTTP_REFERER'] ?? '';
  if (stripos($ref, 'facebook.com') === false && stripos($ref, 'fb.com') === false) {
    header("Location: $REDIRECT_BLOCKED", true, 302);
    exit;
  }
}

// fbclid check
if ($REQUIRE_FBCLID && !isset($_GET['fbclid'])) {
  header("Location: $REDIRECT_BLOCKED", true, 302);
  exit;
}

// Mint a new token
$fp = fopen($TOKENS_FILE, 'a');
if (!$fp) {
  http_response_code(500);
  echo "Token store not writable.";
  exit;
}
flock($fp, LOCK_EX);
$token = bin2hex(random_bytes(16));
fwrite($fp, $token . PHP_EOL);
flock($fp, LOCK_UN);
fclose($fp);

// Redirect to guarded page
header('Location: /index.php?t=' . urlencode($token), true, 302);
exit;