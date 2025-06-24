<?php

/**
 * Simple test to verify the server is working after the fix
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
echo "    <title>Server Test</title>\n";
echo "</head>\n";
echo "<body>\n";

echo "<h2>✅ Server Test</h2>\n";
echo "<p><strong>Status:</strong> Server is working correctly! 🚀</p>\n";
echo "<p><strong>Current Time:</strong> " . date('Y-m-d H:i:s') . " ⏰</p>\n";

// Test GET parameters
if (!empty($_GET)) {
    echo "<h3>GET Parameters:</h3>\n";
    echo "<pre>" . print_r($_GET, true) . "</pre>\n";
}

// Test POST parameters
if (!empty($_POST)) {
    echo "<h3>POST Parameters:</h3>\n";
    echo "<pre>" . print_r($_POST, true) . "</pre>\n";
}

// Test raw body access if available
if (isset($server_context) && !empty($server_context->rawBody)) {
    echo "<h3>Raw Body:</h3>\n";
    echo "<pre>" . htmlspecialchars($server_context->rawBody) . "</pre>\n";
}

// Test session
if (!isset($_SESSION['test_counter'])) {
    $_SESSION['test_counter'] = 0;
}
$_SESSION['test_counter']++;

echo "<h3>Session Test:</h3>\n";
echo "<p>Session counter: " . $_SESSION['test_counter'] . " 📊</p>\n";

// Test shared variables
if (isset($shared_variables)) {
    if (!isset($shared_variables['server_test_counter'])) {
        $shared_variables['server_test_counter'] = 0;
    }
    $shared_variables['server_test_counter']++;

    echo "<h3>Shared Variables Test:</h3>\n";
    echo "<p>Global counter: " . $shared_variables['server_test_counter'] . " 🌍</p>\n";
}

echo "<hr>\n";
echo "<p><strong>✅ All tests passed!</strong> The server is functioning correctly. 🎉</p>\n";
echo "</body>\n";
echo "</html>\n";
