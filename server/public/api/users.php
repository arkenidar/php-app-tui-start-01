<?php

/**
 * Example JSON API endpoint demonstrating proper isolation
 */

// Set JSON content type
$server_context->setHeader('Content-Type', 'application/json');

// Handle different HTTP methods
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

switch ($method) {
    case 'GET':
        // Get user data
        $userId = $_GET['id'] ?? null;

        if (!$userId) {
            $server_context->setResponseCode(400);
            echo json_encode(['error' => 'User ID required']);
            break;
        }

        // Simulate user data (in real app, get from database)
        if (!isset($shared_variables['users'])) {
            $shared_variables['users'] = [
                '1' => ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
                '2' => ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
            ];
        }

        if (isset($shared_variables['users'][$userId])) {
            echo json_encode([
                'success' => true,
                'user' => $shared_variables['users'][$userId]
            ]);
        } else {
            $server_context->setResponseCode(404);
            echo json_encode(['error' => 'User not found']);
        }
        break;

    case 'POST':
        // Create new user
        $input = $_POST;

        if (empty($input['name']) || empty($input['email'])) {
            $server_context->setResponseCode(400);
            echo json_encode(['error' => 'Name and email required']);
            break;
        }

        // Initialize users array if not exists
        if (!isset($shared_variables['users'])) {
            $shared_variables['users'] = [];
        }

        // Generate new user ID
        $newId = (string)(count($shared_variables['users']) + 1);

        $newUser = [
            'id' => (int)$newId,
            'name' => $input['name'],
            'email' => $input['email'],
            'created_at' => date('Y-m-d H:i:s')
        ];

        $shared_variables['users'][$newId] = $newUser;

        $server_context->setResponseCode(201);
        echo json_encode([
            'success' => true,
            'user' => $newUser
        ]);
        break;

    default:
        $server_context->setResponseCode(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
