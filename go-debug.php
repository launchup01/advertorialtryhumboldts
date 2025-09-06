<?php
// go-debug.php — debug version of go.php with detailed error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ==== CONFIG ====
$TOKENS_FILE = __DIR__ . '/tokens.txt';
$REQUIRE_FBCLID = true;        
$REQUIRE_FB_REFERRER = false;  
$TOKEN_TTL = 1800;             
$COOKIE_NAME = 'funnel_sess';
$COOKIE_TTL  = 1800;           
$REDIRECT_BLOCKED = '/blocked.html';

header('Cache-Control: no-store');
header('Content-Type: text/html; charset=utf-8');

// Debug output function
function debug($message) {
    echo "<p>🐛 DEBUG: $message</p>";
}

echo "<h1>🔧 go.php Debug Mode</h1>";

// Allow desktop testing without FB
if (isset($_GET['test']) && $_GET['test'] == '1') {
    debug("Test mode enabled");
    if (!isset($_GET['fbclid'])) {
        $_GET['fbclid'] = 'TEST_FBCLID';
        debug("Added test fbclid");
    }
    $REQUIRE_FBCLID = false; // Disable fbclid requirement for testing
    debug("Disabled fbclid requirement for testing");
}

// Show current parameters
debug("GET parameters: " . json_encode($_GET));

// Ignore Facebook crawler/prefetch
$ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
if (strpos($ua, 'facebookexternalhit') !== false || strpos($ua, 'facebookcatalog') !== false) {
    debug("Facebook crawler detected - returning 204");
    http_response_code(204);
    exit;
}

// Require fbclid on real clicks
$fbclid = $_GET['fbclid'] ?? '';
debug("fbclid: " . ($fbclid ?: 'MISSING'));

if ($REQUIRE_FBCLID && $fbclid === '') {
    debug("FBCLID required but missing - redirecting to blocked");
    echo "<p>❌ Would redirect to: $REDIRECT_BLOCKED</p>";
    echo "<p>Reason: fbclid parameter missing (required for non-test mode)</p>";
    exit;
}

// Optional referrer gate
if ($REQUIRE_FB_REFERRER) {
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    debug("Referrer check enabled. Referrer: " . ($ref ?: 'MISSING'));
    if (stripos($ref, 'facebook.com') === false && stripos($ref, 'fb.com') === false) {
        debug("Invalid referrer - redirecting to blocked");
        echo "<p>❌ Would redirect to: $REDIRECT_BLOCKED</p>";
        echo "<p>Reason: Invalid referrer</p>";
        exit;
    }
} else {
    debug("Referrer check disabled");
}

// Create/get session cookie
$sess = $_COOKIE[$COOKIE_NAME] ?? bin2hex(random_bytes(16));
debug("Session ID: " . substr($sess, 0, 8) . "...");

// Cookie settings for local vs production
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
    debug("HTTPS detected - using secure cookies");
} else {
    debug("HTTP detected - not using secure cookies");
}

setcookie($COOKIE_NAME, $sess, $cookieSettings);
debug("Cookie set: $COOKIE_NAME");

// Mint one-time token
$token = bin2hex(random_bytes(16));
$exp = time() + $TOKEN_TTL;
debug("Generated token: " . substr($token, 0, 8) . "...");
debug("Token expires: " . date('Y-m-d H:i:s', $exp));

// Write token to file
debug("Attempting to write to: $TOKENS_FILE");

$fp = @fopen($TOKENS_FILE, 'a');
if (!$fp) {
    debug("Primary tokens file failed, trying temp directory");
    $TOKENS_FILE = sys_get_temp_dir() . '/tokens.txt';
    $fp = @fopen($TOKENS_FILE, 'a');
    if (!$fp) { 
        debug("FATAL: Cannot write tokens file");
        http_response_code(500); 
        echo "<p>❌ Error: Token store not writable</p>";
        echo "<p>Tried: " . __DIR__ . "/tokens.txt</p>";
        echo "<p>Tried: $TOKENS_FILE</p>";
        exit; 
    }
    debug("Using temp directory: $TOKENS_FILE");
}

flock($fp, LOCK_EX);
$tokenLine = $token . '|' . $sess . '|' . $exp . PHP_EOL;
$written = fwrite($fp, $tokenLine);
flock($fp, LOCK_UN);
fclose($fp);

debug("Wrote $written bytes to tokens file");

// Generate redirect URL
$next = '/index.php?t=' . urlencode($token);
debug("Generated redirect URL: $next");

// In debug mode, show the redirect page instead of actually redirecting
if (isset($_GET['debug'])) {
    echo "<h2>✅ Success! Token Generated</h2>";
    echo "<p><strong>Token:</strong> $token</p>";
    echo "<p><strong>Session:</strong> $sess</p>";
    echo "<p><strong>Expires:</strong> " . date('Y-m-d H:i:s', $exp) . "</p>";
    echo "<p><strong>Next URL:</strong> <a href='$next'>$next</a></p>";
    echo "<br><p><a href='$next'>🚀 Continue to Landing Page</a></p>";
    exit;
}

// Normal redirect (JavaScript + meta refresh fallback)
?>
<!doctype html>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Loading…</title>
<meta http-equiv="Cache-Control" content="no-store">
<script>
    console.log('Redirecting to: <?= $next ?>');
    location.replace(<?= json_encode($next) ?>);
</script>
<noscript>
    <meta http-equiv="refresh" content="0;url=<?= htmlspecialchars($next, ENT_QUOTES, 'UTF-8') ?>">
    <p>Continue to <a href="<?= htmlspecialchars($next, ENT_QUOTES, 'UTF-8') ?>">your page</a>.</p>
</noscript>
<p>Redirecting...</p>
