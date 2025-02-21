<?php

// Create a TCP socket for HTTP server
$serverSocket = stream_socket_server("tcp://0.0.0.0:8080", $errno, $errstr);
if (!$serverSocket) {
    die("Failed to create socket: $errstr ($errno)\n");
}

// Set the server socket to non-blocking mode
stream_set_blocking($serverSocket, false);

echo "HTTP Server listening on 0.0.0.0:8080...\n";

$clients = [];
$fibers = [];
$clientFibers = [];

// Function to parse HTTP requests
function parseHttpRequest($rawRequest) {
    $lines = explode("\r\n", $rawRequest);
    $requestLine = array_shift($lines);
    $parts = explode(' ', $requestLine);

    if (count($parts) < 2) {
        return null; // Invalid request
    }

    $method = $parts[0];
    $path = $parts[1];

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
                $headers[$key] = $value;
            }
        }
    }

    return compact('method', 'path', 'headers', 'body');
}

// Function to generate an HTTP response
function buildHttpResponse($statusCode, $body, $contentType = "text/html") {
    $statusMessages = [
        200 => "OK",
        400 => "Bad Request",
        404 => "Not Found",
        500 => "Internal Server Error"
    ];

    $statusMessage = $statusMessages[$statusCode] ?? "Unknown";
    return "HTTP/1.1 $statusCode $statusMessage\r\n"
        . "Content-Type: $contentType\r\n"
        . "Content-Length: " . strlen($body) . "\r\n"
        . "Connection: close\r\n\r\n"
        . $body;
}

// Simple event loop
while (true) {
    // Use stream_select() to wait for activity on sockets
    $readSockets = $clients;
    $readSockets[] = $serverSocket; // Include server socket
    $writeSockets = null;
    $exceptSockets = null;

    if (stream_select($readSockets, $writeSockets, $exceptSockets, 0, 1000) > 0) {
        // Accept new client connections
        if (in_array($serverSocket, $readSockets)) {
            $clientSocket = @stream_socket_accept($serverSocket, 0);
            if ($clientSocket) {
                echo "New client connected.\n";
                stream_set_blocking($clientSocket, false);
                $clients[] = $clientSocket;

                // Create a new Fiber for each client
                $fiber = new Fiber(function ($socket) {
                    while (true) {
                        // Read data from the client
                        $data = Fiber::suspend();
                        if ($data === false || trim($data) === '') {
                            echo "Client disconnected.\n";
                            break;
                        }

                        // Parse HTTP request
                        $request = parseHttpRequest($data);
                        if (!$request) {
                            $response = buildHttpResponse(400, "Bad Request");
                        } else {
                            // Handle simple routing
                            if ($request['path'] === "/") {
                                $response = buildHttpResponse(200, "<h1>Welcome to My PHP HTTP Server</h1>");
                            } elseif ($request['path'] === "/json") {
                                $response = buildHttpResponse(200, json_encode(["message" => "Hello, world!"]), "application/json");
                            } else {
                                $response = buildHttpResponse(404, "<h1>404 Not Found</h1>");
                            }
                        }

                        // Send HTTP response
                        fwrite($socket, $response);
                    }

                    // Close the client socket
                    fclose($socket);
                });

                // Correctly pass the socket when starting the Fiber
                $fiber->start($clientSocket);
                $fibers[] = $fiber;
                $clientFibers[(int) $clientSocket] = $fiber;
            }
        }

        // Handle client I/O
        foreach ($clients as $key => $clientSocket) {
            if (in_array($clientSocket, $readSockets)) {
                $data = fread($clientSocket, 4096);
                if ($data !== false && $data !== '') {
                    // Resume the corresponding Fiber with the received data
                    if (isset($clientFibers[(int) $clientSocket])) {
                        $clientFibers[(int) $clientSocket]->resume($data);
                    }
                } else {
                    // Cleanup if client disconnected
                    fclose($clientSocket);
                    unset($clients[$key]);
                    unset($clientFibers[(int) $clientSocket]);
                }
            }
        }
    }

    // Sleep to avoid busy-waiting
    usleep(1000);
}
