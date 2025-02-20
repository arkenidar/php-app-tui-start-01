<?php

require_once 'todo-manager.php';

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
            todo_add($name);
            break;
        case 'list':
            $todos = todo_list();
            foreach ($todos as $todo) {
                echo $todo->id . ' ' . $todo->description . ' ' . $todo->state . "\n";
            }
            break;
        case 'remove':
            echo "Enter an id: ";
            $id = readline();
            todo_remove($id);
            break;
        case 'update':
            echo "Enter an id: ";
            $id = readline();
            echo "Enter a name: ";
            $name = readline();
            todo_update_description($id, $name);
            break;
        case 'state':
            echo "Enter an id: ";
            $id = readline();
            echo "Enter a state: ";
            $state = readline();
            todo_update_state($id, $state);
            break;
        case 'exit':
            exit();
        case 'help':
            echo "Available commands: add, list, remove, update, state, exit, help\n";
            break;
        default:
            echo "Unknown command. Type 'help' to see available commands.\n";
    }
}
