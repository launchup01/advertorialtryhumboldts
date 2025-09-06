<?php
// diagnose.php - Check what's wrong with the go.php test
header('Content-Type: text/html; charset=utf-8');

echo "<h1>🩺 Diagnosis for go.php Test</h1>";

// Check basic PHP functionality
echo "<h2>Basic PHP Check</h2>";
echo "<p>✅ PHP is working</p>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";

// Check if go.php exists and is readable
echo "<h2>File Check</h2>";
$goPhp = __DIR__ . '/go.php';
if (file_exists($goPhp)) {
    echo "<p>✅ go.php exists</p>";
    if (is_readable($goPhp)) {
        echo "<p>✅ go.php is readable</p>";
    } else {
        echo "<p>❌ go.php is not readable</p>";
    }
} else {
    echo "<p>❌ go.php not found at: $goPhp</p>";
}

// Check tokens.txt
$tokensFile = __DIR__ . '/tokens.txt';
echo "<p><strong>Tokens file:</strong> $tokensFile</p>";
if (file_exists($tokensFile)) {
    echo "<p>✅ tokens.txt exists</p>";
    if (is_writable($tokensFile)) {
        echo "<p>✅ tokens.txt is writable</p>";
    } else {
        echo "<p>❌ tokens.txt is not writable</p>";
    }
    echo "<p>File size: " . filesize($tokensFile) . " bytes</p>";
} else {
    echo "<p>⚠️ tokens.txt doesn't exist (will be created)</p>";
    if (is_writable(__DIR__)) {
        echo "<p>✅ Directory is writable (can create tokens.txt)</p>";
    } else {
        echo "<p>❌ Directory is not writable</p>";
    }
}

// Test what happens when we simulate the go.php flow
echo "<h2>Simulating go.php Flow</h2>";

try {
    // Simulate the key parts of go.php
    echo "<p>1. Checking test parameter... ";
    $_GET['test'] = '1';
    $_GET['fbclid'] = 'TEST_FBCLID';
    echo "✅ Test mode enabled</p>";
    
    echo "<p>2. Testing token generation... ";
    $token = bin2hex(random_bytes(16));
    echo "✅ Token generated: " . substr($token, 0, 8) . "...</p>";
    
    echo "<p>3. Testing session generation... ";
    $sess = bin2hex(random_bytes(16));
    echo "✅ Session generated: " . substr($sess, 0, 8) . "...</p>";
    
    echo "<p>4. Testing token file write... ";
    $exp = time() + 1800;
    
    $fp = @fopen($tokensFile, 'a');
    if ($fp) {
        flock($fp, LOCK_EX);
        fwrite($fp, $token . '|' . $sess . '|' . $exp . PHP_EOL);
        flock($fp, LOCK_UN);
        fclose($fp);
        echo "✅ Token written successfully</p>";
        
        echo "<p>5. Generated test URL: <a href='/index.php?t=$token'>/index.php?t=$token</a></p>";
        
    } else {
        echo "❌ Failed to open tokens file for writing</p>";
        
        // Try temp directory fallback
        $tempFile = sys_get_temp_dir() . '/tokens.txt';
        echo "<p>Trying temp directory: $tempFile... ";
        $fp = @fopen($tempFile, 'a');
        if ($fp) {
            fwrite($fp, $token . '|' . $sess . '|' . $exp . PHP_EOL);
            fclose($fp);
            echo "✅ Temp file works</p>";
        } else {
            echo "❌ Temp file also failed</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

// Check server environment
echo "<h2>Server Environment</h2>";
echo "<p><strong>Document Root:</strong> " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Not set') . "</p>";
echo "<p><strong>Script Path:</strong> " . __DIR__ . "</p>";
echo "<p><strong>Server:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</p>";
echo "<p><strong>HTTPS:</strong> " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'Yes' : 'No') . "</p>";

// Provide direct test links
echo "<h2>Direct Test Links</h2>";
echo "<p><a href='/go.php?test=1' target='_blank'>🔗 Test go.php directly</a></p>";
echo "<p><a href='/go.php' target='_blank'>🔗 Test go.php without parameters (should show blocked)</a></p>";
echo "<p><a href='/index.php' target='_blank'>🔗 Test index.php directly (should show blocked)</a></p>";

// Show current GET parameters
echo "<h2>Current Request</h2>";
echo "<p><strong>URL:</strong> " . ($_SERVER['REQUEST_URI'] ?? 'Unknown') . "</p>";
echo "<p><strong>Method:</strong> " . ($_SERVER['REQUEST_METHOD'] ?? 'Unknown') . "</p>";

if (!empty($_GET)) {
    echo "<p><strong>GET Parameters:</strong></p><ul>";
    foreach ($_GET as $key => $value) {
        echo "<li>$key = $value</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No GET parameters</p>";
}

echo "<h2>Next Steps</h2>";
echo "<p>If you see errors above, please share them. Otherwise:</p>";
echo "<ol>";
echo "<li>Try clicking the test links above</li>";
echo "<li>Check if you get any specific error messages</li>";
echo "<li>Make sure all files are uploaded to your server</li>";
echo "<li>Ensure your server supports PHP</li>";
echo "</ol>";
?>
