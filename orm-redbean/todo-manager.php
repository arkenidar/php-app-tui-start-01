<?php

require_once 'red-bean-orm-use.php';

//-----------------------------------------
// todo-manager.php
//-----------------------------------------

function todo_list()
{
    return R::findAll('todos');
}

function todo_remove(int $id)
{
    $todo = R::load('todos', $id);
    R::trash($todo);
}

function todo_add(string $description)
{
    $description = trim($description);
    if ($description == '') {
        return;
    }

    $todo              = R::dispense('todos');
    $todo->description = $description;
    $todo->state       = 0;
    R::store($todo);
}

function todo_update_description(int $id, string $description)
{
    if (trim($description) == '') {
        todo_remove($id);
        return;
    }
    $todo              = R::load('todos', $id);
    $todo->description = $description;
    R::store($todo);
}

function todo_update_state(int $id, int $state)
{
    $todo        = R::load('todos', $id);
    $todo->state = $state;
    R::store($todo);
}
