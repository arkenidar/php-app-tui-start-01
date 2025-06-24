<?php

/**
 * Test script to demonstrate response isolation issues
 * Place this in server/public/test-isolation.php
 */

// Simulate some processing time to make race conditions more likely
usleep(rand(10000, 50000)); // 10-50ms delay

echo "Request ID: " . uniqid() . "<br>";
echo "Current time: " . microtime(true) . "<br>";
echo "Process ID: " . getmypid() . "<br>";

// Test shared variables
if (isset($shared_variables)) {
    $shared_variables['counter']++;
    echo "Shared counter: " . $shared_variables['counter'] . "<br>";
}

// Test session isolation
if (!isset($_SESSION['request_count'])) {
    $_SESSION['request_count'] = 0;
}
$_SESSION['request_count']++;
echo "Session request count: " . $_SESSION['request_count'] . "<br>";

// Test GET/POST parameters
if (!empty($_GET)) {
    echo "GET params: ";
    print_r($_GET);
    echo "<br>";
}

// Output some content that should be unique to this request
echo "Random number for this request: " . rand(1000, 9999) . "<br>";

// More processing time
usleep(rand(10000, 30000));

echo "Request completed at: " . microtime(true) . "<br>";
echo "<hr>";
