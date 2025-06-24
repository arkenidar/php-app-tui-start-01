<?php

/**
 * Comprehensive test for response isolation
 * This script tests all aspects of isolation to ensure no mixing between requests
 */

// Display request identification
$requestId = $_GET['request_id'] ?? 'unknown';
$requestParam = $_GET['param'] ?? 'none';

echo "<h2>🔬 Complete Isolation Test</h2>\n";
echo "<p><strong>Request ID:</strong> $requestId</p>\n";
echo "<p><strong>Request Param:</strong> $requestParam</p>\n";
echo "<p><strong>Current Time:</strong> " . microtime(true) . "</p>\n";

// Test 1: Superglobal isolation
echo "<h3>Test 1: Superglobal Isolation</h3>\n";
echo "<p>GET parameters received:</p>\n";
echo "<pre>" . print_r($_GET, true) . "</pre>\n";

// Test 2: Session isolation
echo "<h3>Test 2: Session Isolation</h3>\n";
if (!isset($_SESSION['request_count'])) {
    $_SESSION['request_count'] = 0;
}
$_SESSION['request_count']++;
$_SESSION['last_request_id'] = $requestId;

echo "<p>Session request count: " . $_SESSION['request_count'] . "</p>\n";
echo "<p>Last request ID in session: " . $_SESSION['last_request_id'] . "</p>\n";

// Test 3: Output isolation with timing
echo "<h3>Test 3: Output Isolation</h3>\n";
echo "<p>Starting processing for request $requestId...</p>\n";

// Simulate processing time to create race conditions
usleep(rand(50000, 150000)); // 50-150ms

echo "<p>🔄 Processing step 1 for request $requestId</p>\n";
usleep(rand(25000, 75000)); // 25-75ms

echo "<p>🔄 Processing step 2 for request $requestId</p>\n";
usleep(rand(25000, 75000)); // 25-75ms

echo "<p>✅ Completed processing for request $requestId</p>\n";

// Test 4: Shared variable access
echo "<h3>Test 4: Shared Variable Access</h3>\n";
if (isset($shared_variables)) {
    $shared_variables['counter']++;
    echo "<p>Shared counter: " . $shared_variables['counter'] . "</p>\n";

    // Test container isolation
    if (!isset($shared_variables['container'][$requestId])) {
        $shared_variables['container'][$requestId] = [];
    }
    $shared_variables['container'][$requestId][] = "Request $requestId at " . microtime(true);

    echo "<p>Container entries for this request:</p>\n";
    echo "<pre>" . print_r($shared_variables['container'][$requestId], true) . "</pre>\n";
} else {
    echo "<p>❌ Shared variables not available</p>\n";
}

// Test 5: Context method access
echo "<h3>Test 5: Context Method Access</h3>\n";
if (isset($server_context)) {
    echo "<p>✅ Server context available</p>\n";

    // Test setting custom headers
    $server_context->setHeader('X-Request-ID', $requestId);
    $server_context->setHeader('X-Processing-Time', (string)microtime(true));

    // Test setting cookies
    $server_context->setCookie('last_request_id', $requestId);

    echo "<p>Custom headers and cookies set</p>\n";
} else {
    echo "<p>❌ Server context not available</p>\n";
}

// Test 6: Random data to detect cross-contamination
echo "<h3>Test 6: Random Data Test</h3>\n";
$randomNumber = rand(10000, 99999);
$_SESSION['random_number'] = $randomNumber;
echo "<p>Random number for this request: <strong>$randomNumber</strong></p>\n";
echo "<p>Random number stored in session: <strong>" . $_SESSION['random_number'] . "</strong></p>\n";

// Final verification
echo "<h3>🎯 Final Verification</h3>\n";
echo "<p>If isolation is working correctly:</p>\n";
echo "<ul>\n";
echo "<li>Request ID should be: <strong>$requestId</strong></li>\n";
echo "<li>Request param should be: <strong>$requestParam</strong></li>\n";
echo "<li>Session random number should be: <strong>" . $_SESSION['random_number'] . "</strong></li>\n";
echo "<li>No other request's data should appear above</li>\n";
echo "</ul>\n";

echo "<hr>\n";
echo "<p><small>Test completed at: " . date('Y-m-d H:i:s.') . substr(microtime(), 2, 3) . "</small></p>\n";
