<?php

/**
 * Session demonstration with shopping cart
 */
?>
<!DOCTYPE html>
<html>

<head>
    <title>Session Demo - Shopping Cart</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        .cart-item {
            background: #f0f0f0;
            padding: 10px;
            margin: 5px 0;
            border-radius: 3px;
        }

        .form-group {
            margin: 10px 0;
        }

        input,
        button {
            padding: 8px;
            margin: 5px;
        }

        .success {
            color: green;
        }

        .info {
            color: blue;
        }
    </style>
</head>

<body>
    <h1>🛒 Shopping Cart Demo</h1>

    <?php
    // Initialize cart
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    if (!isset($_SESSION['cart_id'])) {
        $_SESSION['cart_id'] = uniqid();
    }

    // Handle form submissions
    if ($_POST['action'] ?? null) {
        switch ($_POST['action']) {
            case 'add':
                if (!empty($_POST['item']) && !empty($_POST['price'])) {
                    $_SESSION['cart'][] = [
                        'name' => $_POST['item'],
                        'price' => (float)$_POST['price'],
                        'added_at' => date('H:i:s')
                    ];
                    echo '<div class="success">✅ Item added to cart!</div>';
                }
                break;

            case 'clear':
                $_SESSION['cart'] = [];
                echo '<div class="success">🗑️ Cart cleared!</div>';
                break;
        }
    }

    // Display session info
    echo '<div class="info">';
    echo '<strong>Session ID:</strong> ' . htmlspecialchars($_SESSION['cart_id']) . '<br>';
    echo '<strong>Current Time:</strong> ' . date('Y-m-d H:i:s') . '<br>';
    echo '<strong>Items in Cart:</strong> ' . count($_SESSION['cart']);
    echo '</div>';

    // Calculate total
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total += $item['price'];
    }
    ?>

    <h2>Your Cart</h2>
    <?php if (empty($_SESSION['cart'])): ?>
        <p>Your cart is empty.</p>
    <?php else: ?>
        <?php foreach ($_SESSION['cart'] as $index => $item): ?>
            <div class="cart-item">
                <strong><?= htmlspecialchars($item['name']) ?></strong> -
                $<?= number_format($item['price'], 2) ?>
                <small>(added at <?= htmlspecialchars($item['added_at']) ?>)</small>
            </div>
        <?php endforeach; ?>

        <div style="margin-top: 15px; font-weight: bold;">
            Total: $<?= number_format($total, 2) ?>
        </div>

        <form method="post" style="margin-top: 10px;">
            <input type="hidden" name="action" value="clear">
            <button type="submit">Clear Cart</button>
        </form>
    <?php endif; ?>

    <h2>Add Item</h2>
    <form method="post">
        <input type="hidden" name="action" value="add">
        <div class="form-group">
            <label>Item Name:</label><br>
            <input type="text" name="item" placeholder="e.g., Apple iPhone" required>
        </div>
        <div class="form-group">
            <label>Price:</label><br>
            <input type="number" name="price" step="0.01" placeholder="0.00" required>
        </div>
        <button type="submit">Add to Cart</button>
    </form>

    <h2>Test Isolation</h2>
    <p>Open this page in multiple browser tabs/windows to test session isolation.</p>
    <p>Each tab should maintain its own cart based on cookies.</p>

    <hr>
    <small>
        <strong>Technical Info:</strong><br>
        - Session data is isolated per session cookie<br>
        - Multiple users can shop simultaneously without interference<br>
        - Cart persists across page refreshes within the same session<br>
        - Request processing time: <?= microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true)) ?>ms
    </small>
</body>

</html>