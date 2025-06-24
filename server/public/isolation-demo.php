<?php

/**
 * Simple demonstration of the output isolation problem
 * This shows how the current server can mix responses between concurrent requests
 */

echo "<h2>Output Isolation Test</h2>\n";

// Get unique request identifier
$requestId = $_GET['id'] ?? 'unknown';
echo "<p><strong>Request ID: $requestId</strong></p>\n";

// Simulate some processing time to make race conditions more visible
echo "<p>Starting processing...</p>\n";
flush(); // This won't work in the current server context, but shows the intent

// Some processing time
usleep(100000); // 100ms

echo "<p>Processing step 1 for request $requestId</p>\n";
usleep(50000); // 50ms

echo "<p>Processing step 2 for request $requestId</p>\n";
usleep(50000); // 50ms

echo "<p><strong>Completed request $requestId</strong></p>\n";

// Show session info
echo "<p>Session ID: " . (session_id() ?: 'Not available') . "</p>\n";

// Show all GET parameters to check for cross-contamination
echo "<p>All GET parameters:</p>\n";
echo "<pre>";
print_r($_GET);
echo "</pre>\n";
