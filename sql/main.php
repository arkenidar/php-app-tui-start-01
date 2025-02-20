<?php
// pdo with sqlite
$pdo = new PDO('sqlite::memory:');
$pdo->exec('CREATE TABLE test (id INTEGER PRIMARY KEY, name TEXT)');
$pdo->exec("INSERT INTO test (name) VALUES ('Hello')");

// query
$stmt = $pdo->query('SELECT * FROM test');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}

// main loop
while (true) {
    // ask for user input
    echo "Enter some command: ";
    // read the input
    $input = readline();
    // display the input
    echo "You entered: '$input'\n";

    switch ($input) {
        case 'add':
            echo "Enter a name: ";
            $name = readline();
            $stmt = $pdo->prepare('INSERT INTO test (name) VALUES (:name)');
            $stmt->execute(['name' => $name]);
            break;
        case 'list':
            $stmt = $pdo->query('SELECT * FROM test');
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                print_r($row);
            }
            break;
        case 'exit':
            exit();
        case 'help':
            echo "Available commands: add, list, exit, help\n";
            break;
        default:
            echo "Unknown command. Type 'help' to see available commands.\n";
    }
}
