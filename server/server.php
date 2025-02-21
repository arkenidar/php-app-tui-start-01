<?php
// Create a TCP socket
$serverSocket = stream_socket_server("tcp://0.0.0.0:8080", $errno, $errstr);
if (!$serverSocket) {
    die("Failed to create socket: $errstr ($errno)\n");
}

// Set the server socket to non-blocking mode
stream_set_blocking($serverSocket, false);

echo "Server listening on 0.0.0.0:8080...\n";

$clients = [];
$fibers = [];

// Simple event loop
while (true) {
    // Accept new client connections
    $clientSocket = @stream_socket_accept($serverSocket, 0);
    if ($clientSocket) {
        echo "New client connected.\n";
        stream_set_blocking($clientSocket, false);
        $clients[] = $clientSocket;

        // Create a new Fiber for each client
        $fiber = new Fiber(function ($socket) {
            while (true) {
                // Read data from the client
                $data = Fiber::suspend($socket);
                if ($data === false || $data === '') {
                    echo "Client disconnected.\n";
                    break;
                }

                echo "Received: $data\n";

                // Send a response back to the client
                $response = "Server response: " . trim($data) . "\n";
                fwrite($socket, $response);
            }

            // Close the client socket
            fclose($socket);
        });

        $fiber->start($clientSocket);
        $fibers[] = $fiber;
    }

    // Handle client I/O
    foreach ($clients as $key => $clientSocket) {
        $data = fread($clientSocket, 1024);
        if ($data !== false && $data !== '') {
            // Resume the Fiber with the received data
            $fibers[$key]->resume($data);
        }
    }

    // Clean up disconnected clients
    foreach ($clients as $key => $clientSocket) {
        if (feof($clientSocket)) {
            fclose($clientSocket);
            unset($clients[$key]);
            unset($fibers[$key]);
        }
    }

    // Sleep to avoid busy-waiting
    usleep(1000);
}
?>