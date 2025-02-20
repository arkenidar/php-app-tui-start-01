<?php

require_once 'red-bean-orm-use.php';

/**
 * Retrieves all todos from the database.
 *
 * @return array An array of todo objects.
 */
function todo_list(): array
{
    return R::findAll('todos');
}

/**
 * Removes a todo by its ID.
 *
 * @param int $id The ID of the todo to remove.
 * @return bool Returns true if the todo was found and removed; false otherwise.
 */
function todo_remove(int $id): bool
{
    $todo = R::load('todos', $id);
    if (! $todo || $todo->id === 0) {
        return false;
    }
    R::trash($todo);
    return true;
}

/**
 * Adds a new todo item.
 *
 * @param string $description The description of the todo.
 * @return int|null Returns the new todo's ID if added; null if the description is empty.
 */
function todo_add(string $description): ?int
{
    $description = trim($description);
    if ($description === '') {
        return null;
    }
    $todo              = R::dispense('todos');
    $todo->description = $description;
    $todo->state       = 0;
    return R::store($todo);
}

/**
 * Updates the description of a todo item.
 * If the new description is empty after trimming, the todo will be removed.
 *
 * @param int $id The ID of the todo to update.
 * @param string $description The new description for the todo.
 * @return bool Returns true if the update or removal was successful; false otherwise.
 */
function todo_update_description(int $id, string $description): bool
{
    $trimmedDesc = trim($description);
    if ($trimmedDesc === '') {
        return todo_remove($id);
    }
    $todo = R::load('todos', $id);
    if (! $todo || $todo->id === 0) {
        return false;
    }
    $todo->description = $trimmedDesc;
    R::store($todo);
    return true;
}

/**
 * Updates the state of a todo item.
 *
 * @param int $id The ID of the todo.
 * @param int $state The new state for the todo.
 * @return bool Returns true if the state was updated successfully; false otherwise.
 */
function todo_update_state(int $id, int $state): bool
{
    $todo = R::load('todos', $id);
    if (! $todo || $todo->id === 0) {
        return false;
    }
    $todo->state = $state;
    R::store($todo);
    return true;
}
