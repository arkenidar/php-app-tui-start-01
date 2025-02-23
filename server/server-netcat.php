<?php
// Create a TCP socket
$serverSocket = stream_socket_server("tcp://0.0.0.0:8080", $errno, $errstr);
if (!$serverSocket) {
    die("Failed to create socket: $errstr ($errno)\n");
}

// Set the server socket to non-blocking mode
stream_set_blocking($serverSocket, false);

echo "Server listening on 0.0.0.0:8080...\n";
echo "rlwrap nc localhost 8080";

$clients = [];
$fibers = [];
$clientFibers = [];

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

                        // Send a prompt to the client
                        fwrite($socket, "enter a php formula: ");

                        // Read data from the client
                        $data = Fiber::suspend();
                        if ($data === false || trim($data) === '') {
                            echo "Client disconnected.\n";
                            break;
                        }

                        // Process the received data
                        $data = strval($data);
                        $data = trim($data);
                        echo "Received: '$data'\n";

                        // Send a response back to the client

                        //$response = "Server response: $data\n";
                        //fwrite($socket, "enter a php formula: ");

                        // Evaluate the formula
                        $formula = $data;
                        $formula_php = "return ($formula);";
                        $response = eval ($formula_php) . "\n";

                        // write the response to the client
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
                $data = fread($clientSocket, 1024);
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
