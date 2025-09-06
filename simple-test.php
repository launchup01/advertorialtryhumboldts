<?php
// simple-test.php - Test the token system without cookies
$token = $_GET['t'] ?? '';

if (!$token) {
    echo "<h1>Token Test</h1>";
    echo "<p>Testing token generation and validation...</p>";
    
    // Read current tokens
    $tokensFile = __DIR__ . '/tokens.txt';
    $tokens = [];
    if (file_exists($tokensFile)) {
        $content = file_get_contents($tokensFile);
        $lines = array_filter(array_map('trim', explode("\n", $content)));
        foreach ($lines as $line) {
            $parts = explode('|', $line);
            if (count($parts) >= 3) {
                $tokens[] = [
                    'token' => $parts[0],
                    'session' => $parts[1], 
                    'expires' => (int)$parts[2],
                    'expired' => time() > (int)$parts[2]
                ];
            }
        }
    }
    
    echo "<h2>Current Tokens:</h2>";
    if (empty($tokens)) {
        echo "<p>No tokens found. <a href='go.php?test=1'>Generate one</a></p>";
    } else {
        echo "<table border='1'>";
        echo "<tr><th>Token</th><th>Status</th><th>Expires</th><th>Test Link</th></tr>";
        foreach ($tokens as $t) {
            $status = $t['expired'] ? '❌ Expired' : '✅ Valid';
            $expires = date('Y-m-d H:i:s', $t['expires']);
            echo "<tr>";
            echo "<td>" . substr($t['token'], 0, 8) . "...</td>";
            echo "<td>$status</td>";
            echo "<td>$expires</td>";
            echo "<td><a href='?t={$t['token']}'>Test</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<br><br>";
    echo "<a href='go.php?test=1'>🔄 Generate New Token</a> | ";
    echo "<a href='dashboard.html'>📊 Dashboard</a>";
} else {
    echo "<h1>Testing Token: " . substr($token, 0, 8) . "...</h1>";
    
    // Manually validate token (simplified version of index.php logic)
    $tokensFile = __DIR__ . '/tokens.txt';
    $found = false;
    $expired = false;
    
    if (file_exists($tokensFile)) {
        $lines = file($tokensFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $parts = explode('|', $line);
            if ($parts[0] === $token) {
                $found = true;
                $exp = (int)$parts[2];
                $expired = time() > $exp;
                break;
            }
        }
    }
    
    if (!$found) {
        echo "<p style='color: red;'>❌ Token not found or already used</p>";
    } elseif ($expired) {
        echo "<p style='color: red;'>❌ Token expired</p>";
    } else {
        echo "<p style='color: green;'>✅ Token is valid!</p>";
        echo "<p>In the real system, this would show your landing page.</p>";
    }
    
    echo "<br><a href='simple-test.php'>← Back to Test</a>";
}
?>
