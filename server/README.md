# PHP Fiber Web Server

A high-performance, concurrent web server built with PHP Fibers that provides proper request isolation and advanced session management.

## 🚀 Quick Start

```bash
# Start the server
php server/server-http-isolated.php

# Server runs on http://127.0.0.1:8001
# Visit http://127.0.0.1:8001/performance-monitor.php to see it in action
```

## ✨ Features

- **🔥 Fiber-Based Concurrency** - Handle multiple requests simultaneously without blocking
- **🔒 Complete Request Isolation** - Zero cross-contamination between concurrent requests
- **📊 Session Management** - Proper session isolation with in-memory storage
- **🍪 Cookie Support** - Full cookie handling with security options
- **📁 Static File Serving** - Automatic MIME type detection and file serving
- **🛡️ Security Features** - Path traversal protection and input validation
- **📈 Performance Monitoring** - Built-in performance tracking and metrics
- **🧪 Comprehensive Testing** - Full test suite with isolation verification

## 📋 Requirements

- PHP 8.1+ with Fiber support
- Linux/macOS/Windows
- curl, jq, bc (for testing)

## 🏗️ Architecture

### Core Components

- **HttpRequest** - HTTP request parsing and handling
- **RequestContext** - Isolated execution environment per request
- **IsolatedWebServer** - Main server with Fiber-based concurrency

### Request Isolation

Each request runs in complete isolation:

- ✅ Separate superglobals ($\_GET, $\_POST, $\_SESSION, $\_COOKIE)
- ✅ Independent output buffering
- ✅ Isolated error handling
- ✅ Shared data access when needed

## 📚 Documentation

- **[Developer Guide](docs/DEVELOPER_GUIDE.md)** - Comprehensive development guide
- **[API Reference](docs/API_REFERENCE.md)** - Complete API documentation
- **[Testing Guide](docs/TESTING.md)** - Testing framework and procedures

## 🧪 Testing

Run the comprehensive test suite:

```bash
cd server
./run_tests.sh
```

Test categories:

- Basic functionality
- Request isolation
- Session management
- Concurrent load testing
- Error handling
- Content types
- Performance benchmarks

## 📊 Example Applications

### 1. JSON API Endpoint

```php
<?php
// api/users.php
$server_context->setHeader('Content-Type', 'application/json');

if ($_GET['id']) {
    echo json_encode(['id' => $_GET['id'], 'name' => 'John Doe']);
} else {
    $server_context->setResponseCode(400);
    echo json_encode(['error' => 'ID required']);
}
?>
```

### 2. Session-Based Shopping Cart

```php
<?php
// cart.php
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($_POST['add_item']) {
    $_SESSION['cart'][] = $_POST['item'];
}

echo "Items in cart: " . count($_SESSION['cart']);
?>
```

### 3. Performance Monitoring

```php
<?php
// monitor.php
$shared_variables['requests']++;
echo "Total requests: " . $shared_variables['requests'];
?>
```

## 🔧 Configuration

### Server Settings

```php
$host = '127.0.0.1';  // Server host
$port = 8001;         // Server port
$documentRoot = __DIR__ . '/public';  // Document root
```

### Shared Variables

```php
$shared_variables = [
    'counter' => 1,           // Request counter
    'container' => [],        // General storage
    'sessions' => [],         // Session data
    // Add your shared data here
];
```

## 🚦 Performance

- **Concurrency**: 50+ simultaneous connections
- **Response Time**: <100ms for simple requests
- **Memory Usage**: ~2MB base + ~50KB per active session
- **Throughput**: 100+ requests/second on modern hardware

## 🔒 Security Features

- **Path Traversal Protection** - Prevents directory traversal attacks
- **Request Isolation** - Complete separation of concurrent requests
- **Session Security** - Secure session ID generation and management
- **Input Validation** - Proper request parsing and validation

## 🐛 Troubleshooting

### Common Issues

1. **Mixed Responses Between Requests**

   - Ensure you're using `server-http-isolated.php`
   - Run isolation tests to verify: `./run_tests.sh isolation`

2. **Session Not Working**

   - Check browser cookie settings
   - Verify session isolation with: `./run_tests.sh session`

3. **Performance Issues**
   - Monitor with: `http://127.0.0.1:8001/performance-monitor.php`
   - Run load tests: `./run_tests.sh load`

### Debug Mode

Enable debugging by modifying the server:

```php
// Add to server constructor
$this->debug = true;
```

## 📈 Monitoring and Metrics

Visit `http://127.0.0.1:8001/performance-monitor.php` for real-time metrics:

- Request count and timing
- Memory usage
- Concurrency statistics
- Recent request history

## 🛠️ Development

### Adding New Features

1. Create PHP files in `server/public/`
2. Use available variables:
   - `$_GET`, `$_POST`, `$_SESSION`, `$_COOKIE` (isolated)
   - `$shared_variables` (shared across requests)
   - `$server_context` (request context)

### Best Practices

```php
// ✅ Good - Use isolated superglobals
$userId = $_GET['user_id'] ?? null;

// ✅ Good - Use shared variables for caching
if (!isset($shared_variables['cache'])) {
    $shared_variables['cache'] = [];
}

// ✅ Good - Set proper response codes
$server_context->setResponseCode(404);
$server_context->setHeader('Content-Type', 'application/json');
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Add tests for new functionality
4. Ensure all tests pass: `./run_tests.sh`
5. Submit a pull request

## 📄 License

This project is part of your PHP application suite. See the main project license for details.

## 🙏 Acknowledgments

- PHP Fiber implementation for concurrent programming
- Modern web server design patterns
- Community feedback and testing

---

**Ready to build high-performance PHP applications?** Start with the [Developer Guide](docs/DEVELOPER_GUIDE.md) and explore the [API Reference](docs/API_REFERENCE.md)!
