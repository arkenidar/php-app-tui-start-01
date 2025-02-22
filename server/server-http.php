<?php

// Configuration
$host = '127.0.0.1';
$port = 8000;
$documentRoot = __DIR__ . DIRECTORY_SEPARATOR . 'public';

// Ensure document root exists
if (!is_dir($documentRoot)) {
    mkdir($documentRoot, 0755, true);
}

$server = stream_socket_server("tcp://$host:$port", $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN);

if (!$server) {
    die("Error: $errstr ($errno)\n");
}

// Set server socket to non-blocking mode
stream_set_blocking($server, false);

echo "PHP Fiber Web Server running at http://$host:$port\n";

// Infinite loop for accepting multiple connections
while (true) {
    $conn = @stream_socket_accept($server, 0); // Non-blocking accept

    if ($conn === false) {
        usleep(10000); // Sleep for 10ms to prevent CPU overuse
        continue;
    }

    // Handle connection in a Fiber
    try {
        $fiber = new Fiber(function ($conn) use ($documentRoot) {
            try {
                stream_set_timeout($conn, 2); // Prevent long hangs
                $request = stream_get_contents($conn, 4096); // Increased buffer for larger requests

                if (!$request) {
                    fclose($conn);
                    return;
                }

                // Parse HTTP request
                [$method, $uri, $headers, $body] = parseHttpRequest($request);
                parse_str(parse_url($uri, PHP_URL_QUERY), $getParams); // Parse GET params
                $postBody = parseBody($headers, $body); // Parse JSON/Form body

                // Resolve file path
                $safeUri = str_replace(['../', '..\\'], '', parse_url($uri, PHP_URL_PATH));
                $path = realpath($documentRoot . DIRECTORY_SEPARATOR . ltrim($safeUri, '/\\'));

                // Ensure path is inside document root
                if (!$path || strpos($path, realpath($documentRoot)) !== 0) {
                    respond404($conn);
                    return;
                }

                if (is_dir($path)) {
                    $path .= DIRECTORY_SEPARATOR . 'index.php';
                }

                if (file_exists($path)) {
                    if (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
                        respondDynamic($conn, $path, $method, $getParams, $postBody, $headers);
                    } else {
                        respondStatic($conn, $path);
                    }
                } else {
                    respond404($conn);
                }
            } catch (Throwable $e) {
                respond500($conn, $e->getMessage());
            }
        });

        $fiber->start($conn);
    } catch (Throwable $e) {
        echo "Fiber Error: " . $e->getMessage() . "\n";
    }
}

// Close server when script ends
fclose($server);

/**
 * Parse an HTTP request into method, URI, headers, and body.
 */
function parseHttpRequest($request)
{
    $lines = explode("\r\n", $request);
    $firstLine = explode(' ', array_shift($lines));

    $method = $firstLine[0] ?? 'GET';
    $uri = $firstLine[1] ?? '/';
    $headers = [];
    $body = '';

    $isBody = false;
    foreach ($lines as $line) {
        if ($line === '') {
            $isBody = true;
            continue;
        }
        if ($isBody) {
            $body .= $line;
        } else {
            [$key, $value] = explode(': ', $line, 2) + [null, null];
            if ($key && $value) {
                $headers[$key] = trim($value);
            }
        }
    }

    return [$method, $uri, $headers, $body];
}

/**
 * Parse request body based on headers.
 */
function parseBody($headers, $body)
{
    if (isset($headers['Content-Type'])) {
        if (stripos($headers['Content-Type'], 'application/json') !== false) {
            return json_decode($body, true);
        } elseif (stripos($headers['Content-Type'], 'application/x-www-form-urlencoded') !== false) {
            parse_str($body, $parsedBody);
            return $parsedBody;
        }
    }
    return $body;
}

/**
 * Serve static files efficiently.
 */
function respondStatic($conn, $path)
{
    $mimeTypes = [
        'html' => 'text/html',
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'svg'  => 'image/svg+xml',
        'json' => 'application/json'
    ];

    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $mimeType = $mimeTypes[$ext] ?? 'application/octet-stream';

    fwrite($conn, "HTTP/1.1 200 OK\r\nContent-Type: $mimeType\r\n\r\n");
    readfile($path);
    fclose($conn);
}

/**
 * Serve dynamic PHP scripts with request data.
 */
function respondDynamic($conn, $path, $method, $getParams, $postBody, $headers)
{
    $_GET = $getParams;
    $_POST = $postBody;

    ob_start();
    require $path;
    $content = ob_get_clean();

    fwrite($conn, "HTTP/1.1 200 OK\r\nContent-Type: text/html\r\n\r\n");
    fwrite($conn, $content);
    fclose($conn);
}

/**
 * Serve 404 response.
 */
function respond404($conn)
{
    $content = "<h1>404 Not Found</h1>";
    fwrite($conn, "HTTP/1.1 404 Not Found\r\nContent-Type: text/html\r\n\r\n$content");
    fclose($conn);
}

/**
 * Serve 500 response.
 */
function respond500($conn, $errorMessage)
{
    $content = "<h1>500 Internal Server Error</h1><p>$errorMessage</p>";
    fwrite($conn, "HTTP/1.1 500 Internal Server Error\r\nContent-Type: text/html\r\n\r\n$content");
    fclose($conn);
}
