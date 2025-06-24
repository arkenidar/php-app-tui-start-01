<?php

/**
 * UTF-8 and Emoji Test Page
 */

if (isset($server_context)) {
    $server_context->setHeader('Content-Type', 'text/html; charset=UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UTF-8 & Emoji Test</title>
    <style>
        body {
            font-family: 'Segoe UI', 'Apple Color Emoji', 'Segoe UI Emoji', sans-serif;
            margin: 20px;
            line-height: 1.6;
        }

        .emoji-section {
            background: #f8f9fa;
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }

        .test-result {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
    </style>
</head>

<body>
    <h1>🧪 UTF-8 & Emoji Test</h1>

    <div class="test-result">
        <strong>✅ UTF-8 Status:</strong> If you can see all emojis properly, UTF-8 encoding is working correctly!
    </div>

    <div class="emoji-section">
        <h2>🚀 Server Information</h2>
        <p><strong>⏰ Current Time:</strong> <?= date('Y-m-d H:i:s') ?></p>
        <p><strong>🔧 PHP Version:</strong> <?= PHP_VERSION ?></p>
        <p><strong>🧵 Fiber Support:</strong> <?= class_exists('Fiber') ? '✅ Available' : '❌ Not Available' ?></p>
        <p><strong>📡 Server:</strong> PHP Fiber Web Server</p>
    </div>

    <div class="emoji-section">
        <h2>😀 Emoji Categories</h2>
        <p><strong>😊 Smileys:</strong> 😀 😃 😄 😁 😆 😅 😂 🤣 😊 😇</p>
        <p><strong>❤️ Hearts:</strong> ❤️ 🧡 💛 💚 💙 💜 🤎 🖤 🤍 💯</p>
        <p><strong>🔥 Objects:</strong> 🔥 💎 🎉 🎊 🎈 🎁 🎯 🏆 🥇 ⭐</p>
        <p><strong>🌟 Nature:</strong> 🌟 🌙 ☀️ ⭐ 🌈 ⚡ 🌊 🔥 🌸 🌺</p>
        <p><strong>🚀 Transport:</strong> 🚀 ✈️ 🚁 🚂 🚗 🚕 🚌 🚎 🏎️ 🛸</p>
        <p><strong>📱 Tech:</strong> 📱 💻 ⌨️ 🖥️ 🖨️ 📷 📹 📺 📻 ⏰</p>
    </div>

    <div class="emoji-section">
        <h2>🌍 International Text</h2>
        <p><strong>🇬🇧 English:</strong> Hello World!</p>
        <p><strong>🇪🇸 Español:</strong> ¡Hola Mundo! ñáéíóú</p>
        <p><strong>🇫🇷 Français:</strong> Bonjour le Monde! àâäçéèêëïîôùûüÿ</p>
        <p><strong>🇩🇪 Deutsch:</strong> Hallo Welt! äöüß</p>
        <p><strong>🇷🇺 Русский:</strong> Привет мир!</p>
        <p><strong>🇯🇵 日本語:</strong> こんにちは世界！</p>
        <p><strong>🇨🇳 中文:</strong> 你好世界！</p>
        <p><strong>🇰🇷 한국어:</strong> 안녕하세요 세계!</p>
        <p><strong>🇮🇳 हिंदी:</strong> नमस्ते दुनिया!</p>
    </div>

    <div class="emoji-section">
        <h2>📊 Session Test</h2>
        <?php
        if (!isset($_SESSION['emoji_visits'])) {
            $_SESSION['emoji_visits'] = 0;
        }
        $_SESSION['emoji_visits']++;
        ?>
        <p><strong>🔢 Visit Count:</strong> <?= $_SESSION['emoji_visits'] ?></p>
        <p><strong>🆔 Session ID:</strong> <?= substr($_SESSION['PHPSESSID'] ?? 'N/A', 0, 8) ?>...</p>
    </div>

    <div class="emoji-section">
        <h2>🌐 Shared Variables</h2>
        <?php
        if (isset($shared_variables)) {
            if (!isset($shared_variables['emoji_counter'])) {
                $shared_variables['emoji_counter'] = 0;
            }
            $shared_variables['emoji_counter']++;
            echo "<p><strong>🌍 Global Counter:</strong> " . $shared_variables['emoji_counter'] . "</p>";
        }
        ?>
    </div>

    <div class="emoji-section">
        <h2>🧪 Character Encoding Tests</h2>
        <p><strong>Unicode Symbols:</strong> ♠ ♣ ♥ ♦ ♪ ♫ ☀ ☁ ☂ ☃ ☄ ★ ☆ ☉ ☎ ☑ ☒ ☓</p>
        <p><strong>Mathematical:</strong> ∀ ∂ ∃ ∅ ∇ ∈ ∉ ∋ ∏ ∑ √ ∝ ∞ ∟ ∠ ∥ ∦ ∧ ∨ ∩ ∪ ∫</p>
        <p><strong>Arrows:</strong> ← ↑ → ↓ ↔ ↕ ↖ ↗ ↘ ↙ ⇐ ⇑ ⇒ ⇓ ⇔ ⇕</p>
        <p><strong>Currency:</strong> $ € £ ¥ ¢ ₹ ₽ ₩ ₪ ₫ ₡ ₵ ₦ ₨ ₭ ₮</p>
    </div>

    <div class="test-result">
        <h3>🎯 Test Results</h3>
        <p>If all characters above display correctly:</p>
        <ul>
            <li>✅ UTF-8 encoding is working properly</li>
            <li>✅ Emoji support is functional</li>
            <li>✅ International characters render correctly</li>
            <li>✅ Server headers include proper charset</li>
        </ul>
    </div>

    <div style="margin-top: 30px; padding: 20px; background: #e9ecef; border-radius: 8px;">
        <h3>🔗 Quick Links</h3>
        <p>
            <a href="/server-test.php">🧪 Basic Server Test</a> |
            <a href="/performance-monitor.php">📊 Performance Monitor</a> |
            <a href="/cart-demo.php">🛒 Shopping Cart Demo</a> |
            <a href="/api/users.php?id=1">🔌 JSON API Test</a>
        </p>
    </div>

    <footer style="margin-top: 40px; text-align: center; color: #6c757d; font-size: 0.9em;">
        <p>🚀 Powered by PHP Fiber Web Server | ⚡ Concurrent & Isolated | 🔒 Secure</p>
    </footer>
</body>

</html>