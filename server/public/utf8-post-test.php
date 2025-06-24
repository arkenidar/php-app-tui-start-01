<?php

/**
 * UTF-8 POST handling test
 */

// Set UTF-8 content type
if (isset($server_context)) {
    $server_context->setHeader('Content-Type', 'text/html; charset=UTF-8');
}

echo "<!DOCTYPE html>\n";
echo "<html lang=\"en\">\n";
echo "<head>\n";
echo "    <meta charset=\"UTF-8\">\n";
echo "    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n";
echo "    <title>UTF-8 POST Test</title>\n";
echo "</head>\n";
echo "<body>\n";

echo "<h2>🌐 UTF-8 POST Test</h2>\n";

if (!empty($_POST)) {
    echo "<h3>✅ POST Data Received:</h3>\n";
    echo "<div style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>\n";
    foreach ($_POST as $key => $value) {
        echo "<p><strong>" . htmlspecialchars($key) . ":</strong> " . htmlspecialchars($value) . "</p>\n";
    }
    echo "</div>\n";

    // Test raw body if available
    if (isset($server_context) && !empty($server_context->rawBody)) {
        echo "<h3>📄 Raw Body:</h3>\n";
        echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>" . htmlspecialchars($server_context->rawBody) . "</pre>\n";
    }
} else {
    echo "<form method='POST' style='background: #f9f9f9; padding: 20px; border-radius: 5px;'>\n";
    echo "<h3>📝 Test UTF-8 Form:</h3>\n";
    echo "<p>Enter text with emojis and international characters:</p>\n";
    echo "<input type='text' name='message' value='Hello 世界! 🌍 Café naïve résumé 🎉' style='width: 100%; padding: 8px; margin: 8px 0;'><br>\n";
    echo "<input type='text' name='emoji_test' value='🔥💻🎯🌟⚡🎭🚗📱💡🎨' style='width: 100%; padding: 8px; margin: 8px 0;'><br>\n";
    echo "<input type='text' name='chinese' value='你好世界' style='width: 100%; padding: 8px; margin: 8px 0;'><br>\n";
    echo "<input type='text' name='arabic' value='مرحبا بالعالم' style='width: 100%; padding: 8px; margin: 8px 0;'><br>\n";
    echo "<input type='text' name='russian' value='Привет мир' style='width: 100%; padding: 8px; margin: 8px 0;'><br>\n";
    echo "<input type='submit' value='Test UTF-8 POST 🚀' style='background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>\n";
    echo "</form>\n";
}

echo "<hr>\n";
echo "<p><a href='utf8-post-test.php'>🔄 Reset Form</a> | <a href='server-test.php'>🏠 Back to Server Test</a></p>\n";
echo "</body>\n";
echo "</html>\n";
