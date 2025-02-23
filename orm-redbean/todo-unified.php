<?php
require_once 'todo-manager.php';

/**
 * Runs the todo command loop using the given I/O callbacks.
 *
 * @param callable $write A function that outputs text.
 * @param callable $read A function that returns the next line of input.
 */
function runTodoLoop(callable $write, callable $read) {
    $write("Available commands: add, list, remove, update, state, exit, help\n");

    while (true) {
        $write("\nEnter a command: ");
        $command = trim($read());

        switch ($command) {
            case 'add':
                handleAdd($write, $read);
                break;
            case 'list':
                handleList($write);
                break;
            case 'remove':
                handleRemove($write, $read);
                break;
            case 'update':
                handleUpdate($write, $read);
                break;
            case 'state':
                handleState($write, $read);
                break;
            case 'help':
                showHelp($write);
                break;
            case 'exit':
                $write("Goodbye!\n");
                return;
            default:
                $write("Unknown command. Type 'help' to see available commands.\n");
        }
    }
}

function handleAdd(callable $write, callable $read) {
    $write("Enter a name: ");
    $name = trim($read());
    if ($name === '') {
        $write("Name cannot be empty.\n");
        return;
    }
    if (todo_add($name)) {
        $write("Todo added successfully.\n");
    } else {
        $write("Failed to add todo.\n");
    }
}

function handleList(callable $write) {
    $todos = todo_list();
    if (empty($todos)) {
        $write("No todos found.\n");
        return;
    }
    foreach ($todos as $todo) {
        $write("{$todo->id}: {$todo->description} ({$todo->state})\n");
    }
}

function handleRemove(callable $write, callable $read) {
    $write("Enter an id: ");
    $id = trim($read());
    if (!is_numeric($id)) {
        $write("Invalid id. Must be numeric.\n");
        return;
    }
    if (todo_remove($id)) {
        $write("Todo removed successfully.\n");
    } else {
        $write("Failed to remove todo.\n");
    }
}

function handleUpdate(callable $write, callable $read) {
    $write("Enter an id: ");
    $id = trim($read());
    if (!is_numeric($id)) {
        $write("Invalid id. Must be numeric.\n");
        return;
    }
    $write("Enter a new name: ");
    $name = trim($read());
    if ($name === '') {
        $write("Name cannot be empty.\n");
        return;
    }
    if (todo_update_description($id, $name)) {
        $write("Todo updated successfully.\n");
    } else {
        $write("Failed to update todo.\n");
    }
}

function handleState(callable $write, callable $read) {
    $write("Enter an id: ");
    $id = trim($read());
    if (!is_numeric($id)) {
        $write("Invalid id. Must be numeric.\n");
        return;
    }
    $write("Enter a state: ");
    $state = trim($read());
    if ($state === '') {
        $write("State cannot be empty.\n");
        return;
    }
    if (todo_update_state($id, $state)) {
        $write("Todo state updated successfully.\n");
    } else {
        $write("Failed to update todo state.\n");
    }
}

function showHelp(callable $write) {
    $write("Available commands: add, list, remove, update, state, exit, help\n");
}

////////////////////////////////////////////////////////////////////////
// Console (STDIN/STDOUT) version
////////////////////////////////////////////////////////////////////////
function runConsoleVersion() {
    $consoleWrite = function($text) {
        echo $text;
    };

    $consoleRead = function() {
        return readline();
    };

    runTodoLoop($consoleWrite, $consoleRead);
}

////////////////////////////////////////////////////////////////////////
// TCP Socket server version using Fibers for concurrency
////////////////////////////////////////////////////////////////////////
function runTcpServerVersion() {
    $serverSocket = stream_socket_server("tcp://0.0.0.0:8080", $errno, $errstr);
    if (!$serverSocket) {
        die("Failed to create socket: $errstr ($errno)\n");
    }
    stream_set_blocking($serverSocket, false);
    echo "TCP server listening on 0.0.0.0:8080...\n";
    echo "Use 'rlwrap nc localhost 8080' to connect.\n";

    $clients = [];
    $clientFibers = [];

    while (true) {
        // Remove invalid or closed client sockets from the $clients array.
        $clients = array_filter($clients, function($socket) {
            return is_resource($socket);
        });

        // Build the read array for stream_select, including the server socket.
        $readSockets = $clients;
        $readSockets[] = $serverSocket;
        $writeSockets = null;
        $exceptSockets = null;

        if (stream_select($readSockets, $writeSockets, $exceptSockets, 0, 1000) > 0) {
            // Accept new client connections.
            if (in_array($serverSocket, $readSockets)) {
                $clientSocket = @stream_socket_accept($serverSocket, 0);
                if ($clientSocket) {
                    echo "New client connected.\n";
                    stream_set_blocking($clientSocket, false);
                    $clients[] = $clientSocket;

                    // Create a Fiber to handle each client.
                    $fiber = new Fiber(function($socket) {
                        // Define socket-based I/O callbacks.
                        $socketWrite = function($text) use ($socket) {
                            fwrite($socket, $text);
                        };
                        $socketRead = function() use ($socket) {
                            // Suspend the Fiber until data is available.
                            return Fiber::suspend();
                        };

                        runTodoLoop($socketWrite, $socketRead);
                        fclose($socket);
                    });
                    $fiber->start($clientSocket);
                    $clientFibers[(int)$clientSocket] = $fiber;
                }
            }

            // Handle I/O for each client.
            foreach ($clients as $key => $clientSocket) {
                if (in_array($clientSocket, $readSockets)) {
                    $data = fread($clientSocket, 1024);
                    if ($data !== false && $data !== '') {
                        if (isset($clientFibers[(int)$clientSocket])) {
                            $clientFibers[(int)$clientSocket]->resume($data);
                        }
                    } else {
                        // Cleanup if the client disconnected.
                        fclose($clientSocket);
                        unset($clients[$key]);
                        unset($clientFibers[(int)$clientSocket]);
                    }
                }
            }
        }
        usleep(1000);
    }
}

////////////////////////////////////////////////////////////////////////
// Main entry point - select mode at launch
////////////////////////////////////////////////////////////////////////
if (php_sapi_name() === 'cli') {
    if (isset($argv[1])) {
        $mode = strtolower($argv[1]);
        if ($mode === 'tcp') {
            runTcpServerVersion();
        } elseif ($mode === 'console') {
            runConsoleVersion();
        } else {
            echo "Unknown mode '{$argv[1]}'. Use 'tcp' or 'console'.\n";
            exit(1);
        }
    } else {
        // If no argument is provided, prompt the user.
        echo "Select mode [tcp/console] (default is console): ";
        $input = trim(fgets(STDIN));
        if (strtolower($input) === 'tcp') {
            runTcpServerVersion();
        } else {
            runConsoleVersion();
        }
    }
}
