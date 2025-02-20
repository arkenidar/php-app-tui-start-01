<?php

require_once 'todo-manager.php';

/**
 * Prompts the user for input and returns the trimmed response.
 *
 * @param string $prompt The prompt to display.
 * @return string The trimmed user input.
 */
function prompt($prompt)
{
    echo $prompt;
    return trim(readline());
}

/**
 * Handles adding a new todo.
 */
function handleAdd()
{
    $name = prompt("Enter a name: ");
    if ($name === '') {
        echo "Name cannot be empty.\n";
        return;
    }
    if (todo_add($name)) {
        echo "Todo added successfully.\n";
    } else {
        echo "Failed to add todo.\n";
    }
}

/**
 * Handles listing all todos.
 */
function handleList()
{
    $todos = todo_list();
    if (empty($todos)) {
        echo "No todos found.\n";
        return;
    }
    foreach ($todos as $todo) {
        echo "{$todo->id}: {$todo->description} ({$todo->state})\n";
    }
}

/**
 * Handles removing a todo.
 */
function handleRemove()
{
    $id = prompt("Enter an id: ");
    if (! is_numeric($id)) {
        echo "Invalid id. Must be numeric.\n";
        return;
    }
    if (todo_remove($id)) {
        echo "Todo removed successfully.\n";
    } else {
        echo "Failed to remove todo.\n";
    }
}

/**
 * Handles updating the description of a todo.
 */
function handleUpdate()
{
    $id = prompt("Enter an id: ");
    if (! is_numeric($id)) {
        echo "Invalid id. Must be numeric.\n";
        return;
    }
    $name = prompt("Enter a new name: ");
    if ($name === '') {
        echo "Name cannot be empty.\n";
        return;
    }
    if (todo_update_description($id, $name)) {
        echo "Todo updated successfully.\n";
    } else {
        echo "Failed to update todo.\n";
    }
}

/**
 * Handles updating the state of a todo.
 */
function handleState()
{
    $id = prompt("Enter an id: ");
    if (! is_numeric($id)) {
        echo "Invalid id. Must be numeric.\n";
        return;
    }
    $state = prompt("Enter a state: ");
    if ($state === '') {
        echo "State cannot be empty.\n";
        return;
    }
    if (todo_update_state($id, $state)) {
        echo "Todo state updated successfully.\n";
    } else {
        echo "Failed to update todo state.\n";
    }
}

/**
 * Displays the list of available commands.
 */
function showHelp()
{
    echo "Available commands: add, list, remove, update, state, exit, help\n";
}

// Main application loop
while (true) {
    $command = prompt("\nEnter a command (type 'help' for available commands): ");

    switch ($command) {
        case 'add':
            handleAdd();
            break;
        case 'list':
            handleList();
            break;
        case 'remove':
            handleRemove();
            break;
        case 'update':
            handleUpdate();
            break;
        case 'state':
            handleState();
            break;
        case 'help':
            showHelp();
            break;
        case 'exit':
            echo "Goodbye!\n";
            exit();
        default:
            echo "Unknown command. Type 'help' to see available commands.\n";
    }
}
