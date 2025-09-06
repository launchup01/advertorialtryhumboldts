<?php
// Simple test to verify the system is working
echo "Testing token system...\n\n";

// Check if tokens.txt is writable
$tokensFile = __DIR__ . '/tokens.txt';
if (is_writable(dirname($tokensFile))) {
    echo "✅ tokens.txt directory is writable\n";
} else {
    echo "❌ tokens.txt directory is NOT writable\n";
}

// Check if we can read/write tokens
if (file_exists($tokensFile)) {
    echo "✅ tokens.txt exists\n";
    $content = file_get_contents($tokensFile);
    echo "📄 Current tokens: " . (empty(trim($content)) ? "none" : "\n" . $content) . "\n";
} else {
    echo "⚠️  tokens.txt does not exist (will be created)\n";
}

// Test token generation
echo "\n🔄 Testing token generation...\n";
$testToken = bin2hex(random_bytes(16));
$testSess = bin2hex(random_bytes(16));
$testExp = time() + 300; // 5 minutes

$fp = @fopen($tokensFile, 'a');
if ($fp) {
    fwrite($fp, $testToken . '|' . $testSess . '|' . $testExp . PHP_EOL);
    fclose($fp);
    echo "✅ Test token generated: $testToken\n";
    echo "🔗 Test URL: http://localhost:8000/index.php?t=$testToken\n";
    
    // Set test cookie for this session
    setcookie('funnel_sess', $testSess, [
        'expires' => time() + 300,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    echo "🍪 Test cookie set: $testSess\n";
} else {
    echo "❌ Failed to write test token\n";
}

echo "\n✨ System appears to be working!\n";
echo "\nNext steps:\n";
echo "1. Update your Facebook ad URL to: http://yourdomain.com/go.php\n";
echo "2. Test by visiting the ad URL\n";
echo "3. Try copying and sharing the resulting index.php?t=xxx URL - it should show 'expired'\n";
?>
