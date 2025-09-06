<?php
// debug.php - Debug the token and cookie system
header('Content-Type: text/html; charset=utf-8');
echo "<h1>🐛 Debug Information</h1>";

echo "<h2>Environment</h2>";
echo "<p><strong>Time:</strong> " . date('Y-m-d H:i:s') . " (Unix: " . time() . ")</p>";
echo "<p><strong>Server:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'unknown') . "</p>";
echo "<p><strong>HTTPS:</strong> " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'Yes' : 'No') . "</p>";

echo "<h2>Cookies</h2>";
if (empty($_COOKIE)) {
    echo "<p>❌ No cookies found</p>";
} else {
    echo "<ul>";
    foreach ($_COOKIE as $name => $value) {
        echo "<li><strong>$name:</strong> $value</li>";
    }
    echo "</ul>";
}

echo "<h2>GET Parameters</h2>";
if (empty($_GET)) {
    echo "<p>No GET parameters</p>";
} else {
    echo "<ul>";
    foreach ($_GET as $name => $value) {
        echo "<li><strong>$name:</strong> $value</li>";
    }
    echo "</ul>";
}

echo "<h2>Tokens File</h2>";
$tokensFile = __DIR__ . '/tokens.txt';
if (file_exists($tokensFile)) {
    $content = file_get_contents($tokensFile);
    $lines = array_filter(array_map('trim', explode("\n", $content)));
    
    echo "<p><strong>File exists:</strong> ✅</p>";
    echo "<p><strong>File size:</strong> " . filesize($tokensFile) . " bytes</p>";
    echo "<p><strong>Total tokens:</strong> " . count($lines) . "</p>";
    
    if (!empty($lines)) {
        echo "<h3>Token Details:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Token (first 8 chars)</th><th>Session (first 8 chars)</th><th>Expires</th><th>Status</th></tr>";
        
        foreach ($lines as $line) {
            $parts = explode('|', $line);
            if (count($parts) >= 3) {
                $token = $parts[0];
                $session = $parts[1];
                $expires = (int)$parts[2];
                $status = time() > $expires ? '❌ Expired' : '✅ Valid';
                $expiresFormatted = date('Y-m-d H:i:s', $expires);
                
                echo "<tr>";
                echo "<td>" . substr($token, 0, 8) . "...</td>";
                echo "<td>" . substr($session, 0, 8) . "...</td>";
                echo "<td>$expiresFormatted</td>";
                echo "<td>$status</td>";
                echo "</tr>";
            }
        }
        echo "</table>";
    }
} else {
    echo "<p>❌ File does not exist</p>";
}

echo "<h2>Quick Actions</h2>";
echo "<p>";
echo "<a href='go.php?test=1'>🔄 Generate Token (go.php)</a> | ";
echo "<a href='simple-test.php'>🧪 Simple Test</a> | ";
echo "<a href='dashboard.html'>📊 Dashboard</a>";
echo "</p>";

echo "<h2>Manual Token Test</h2>";
echo "<form method='get' action='index.php'>";
echo "<input type='text' name='t' placeholder='Enter token here' style='width: 300px;'>";
echo "<input type='submit' value='Test Token'>";
echo "</form>";
?>
