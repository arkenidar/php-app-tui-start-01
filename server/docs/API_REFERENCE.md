# PHP Fiber Web Server - API Reference

## Server API Documentation

### Classes

#### HttpRequest

Represents an incoming HTTP request.

```php
class HttpRequest {
    public string $method;      // HTTP method (GET, POST, etc.)
    public string $uri;         // Request URI
    public array $headers;      // Request headers
    public string $body;        // Request body
}
```

**Methods:**

- `__construct(string $rawRequest)` - Parse raw HTTP request
- `getQueryParams(): array` - Get URL query parameters
- `getParsedBody(): mixed` - Get parsed request body (JSON/form data)
- `getCookies(): array` - Get request cookies

#### RequestContext

Isolated environment for each request.

```php
class RequestContext {
    public array $get;              // GET parameters
    public array $post;             // POST data
    public array $cookie;           // Cookies
    public array $session;          // Session data
    public array $responseCookies;  // Outgoing cookies
    public string $sessionId;       // Session identifier
    public int $responseCode;       // HTTP response code
    public array $responseHeaders;  // Response headers
}
```

**Public Methods:**

```php
// Response manipulation
setResponseCode(int $code): void
setHeader(string $name, string $value): void
setCookie(string $name, string $value, int $expire = 0,
          string $path = '/', string $domain = '',
          bool $secure = false, bool $httponly = false): void

// Output methods
echo(string $content): void
print(string $content): void
```

#### IsolatedWebServer

Main server class with Fiber-based concurrency.

```php
class IsolatedWebServer {
    protected string $host;
    protected int $port;
    protected string $documentRoot;
    protected array $sharedData;
}
```

**Public Methods:**

- `__construct(string $host, int $port, string $documentRoot)` - Initialize server
- `run(): void` - Start the server and handle requests

**Protected Methods:**

- `handleConnection($conn): void` - Handle individual client connection
- `initializeSession(RequestContext $context): void` - Initialize session for request
- `respondDynamic($conn, string $path, RequestContext $context): void` - Handle PHP scripts
- `respondStatic($conn, string $path): void` - Serve static files
- `executeScriptIsolated(string $path, RequestContext $context): void` - Execute PHP script in isolation

### Script Environment

#### Available Variables

When your PHP scripts run, these variables are available:

```php
// Superglobals (isolated per request)
$_GET       // Query parameters
$_POST      // POST data
$_COOKIE    // Request cookies
$_SESSION   // Session data (isolated per session)

// Server-provided variables
$shared_variables   // Shared data across all requests
$server_context     // RequestContext instance
```

#### Shared Variables Structure

```php
$shared_variables = [
    'counter' => int,           // Auto-incrementing request counter
    'container' => array,       // General purpose storage
    'sessions' => array,        // Session storage (auto-managed)
    // Your custom shared data...
];
```

### HTTP Response Codes

The server supports standard HTTP response codes:

```php
// Success
$server_context->setResponseCode(200);  // OK
$server_context->setResponseCode(201);  // Created
$server_context->setResponseCode(204);  // No Content

// Client Errors
$server_context->setResponseCode(400);  // Bad Request
$server_context->setResponseCode(401);  // Unauthorized
$server_context->setResponseCode(403);  // Forbidden
$server_context->setResponseCode(404);  // Not Found
$server_context->setResponseCode(405);  // Method Not Allowed

// Server Errors
$server_context->setResponseCode(500);  // Internal Server Error
$server_context->setResponseCode(503);  // Service Unavailable
```

### Content Types

Set appropriate content types for different response formats:

```php
// HTML (default)
$server_context->setHeader('Content-Type', 'text/html');

// JSON API
$server_context->setHeader('Content-Type', 'application/json');

// Plain text
$server_context->setHeader('Content-Type', 'text/plain');

// XML
$server_context->setHeader('Content-Type', 'application/xml');

// File downloads
$server_context->setHeader('Content-Type', 'application/octet-stream');
$server_context->setHeader('Content-Disposition', 'attachment; filename="file.pdf"');
```

### Cookie Management

```php
// Basic cookie
$server_context->setCookie('name', 'value');

// Cookie with expiration (1 hour)
$server_context->setCookie('temp_data', 'value', time() + 3600);

// Secure HttpOnly cookie
$server_context->setCookie('session_token', $token, 0, '/', '', true, true);

// Delete cookie
$server_context->setCookie('old_cookie', '', time() - 3600);
```

### Session Management

Sessions are automatically managed by the server:

```php
// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    echo "Welcome back, user " . $_SESSION['user_id'];
} else {
    echo "Please log in";
}

// Store session data
$_SESSION['username'] = $_POST['username'];
$_SESSION['login_time'] = time();

// Remove session data
unset($_SESSION['temp_data']);

// Clear entire session
$_SESSION = [];
```

### Error Handling

```php
try {
    // Your code here
    $result = dangerousOperation();
    echo json_encode(['success' => true, 'data' => $result]);
} catch (Exception $e) {
    $server_context->setResponseCode(500);
    $server_context->setHeader('Content-Type', 'application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
```

### Request Method Handling

```php
switch ($_SERVER['REQUEST_METHOD'] ?? 'GET') {
    case 'GET':
        // Handle GET request
        $id = $_GET['id'] ?? null;
        break;

    case 'POST':
        // Handle POST request
        $data = $_POST;
        break;

    case 'PUT':
        // Handle PUT request
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        break;

    case 'DELETE':
        // Handle DELETE request
        $id = $_GET['id'] ?? null;
        break;

    default:
        $server_context->setResponseCode(405);
        echo 'Method not allowed';
        break;
}
```

### File Upload Handling

```php
// Note: $_FILES is not available in this server implementation
// Use raw POST data for file uploads
if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    strpos($_SERVER['CONTENT_TYPE'] ?? '', 'multipart/form-data') !== false) {

    $rawData = file_get_contents('php://input');
    // Parse multipart data manually or use a library
    // This is a limitation of the current implementation
}
```

### Database Integration Example

```php
// Using your RedBean ORM
require_once '../orm-redbean/red-bean-orm.php';

// Access shared database connection
if (!isset($shared_variables['db_connected'])) {
    // Initialize database connection once
    R::setup('sqlite:../database.db');
    $shared_variables['db_connected'] = true;
}

// Use ORM in your scripts
$user = R::dispense('user');
$user->name = $_POST['name'];
$user->email = $_POST['email'];
$id = R::store($user);

echo json_encode(['user_id' => $id]);
```

### Middleware Pattern Example

```php
// Create middleware using shared variables
if (!isset($shared_variables['middleware_stack'])) {
    $shared_variables['middleware_stack'] = [
        'auth' => function() {
            if (!isset($_SESSION['user_id'])) {
                global $server_context;
                $server_context->setResponseCode(401);
                echo json_encode(['error' => 'Authentication required']);
                return false;
            }
            return true;
        },
        'rate_limit' => function() {
            // Implement rate limiting
            return true;
        }
    ];
}

// Use middleware in scripts
foreach ($shared_variables['middleware_stack'] as $middleware) {
    if (!$middleware()) {
        return; // Stop execution if middleware fails
    }
}

// Your protected code here...
```

### Performance Monitoring

```php
// Track request performance
$start_time = microtime(true);

// Your application code...

$end_time = microtime(true);
$execution_time = ($end_time - $start_time) * 1000; // ms

// Log to shared variables
if (!isset($shared_variables['performance_log'])) {
    $shared_variables['performance_log'] = [];
}
$shared_variables['performance_log'][] = [
    'path' => $_SERVER['REQUEST_URI'] ?? 'unknown',
    'time' => $execution_time,
    'timestamp' => time()
];

// Add performance header
$server_context->setHeader('X-Execution-Time', $execution_time . 'ms');
```

### WebSocket Upgrade (Future Enhancement)

```php
// Note: WebSocket support would require additional implementation
// This is a placeholder for future enhancement

if (isset($_SERVER['HTTP_UPGRADE']) && $_SERVER['HTTP_UPGRADE'] === 'websocket') {
    // Handle WebSocket upgrade
    $server_context->setResponseCode(101);
    $server_context->setHeader('Upgrade', 'websocket');
    $server_context->setHeader('Connection', 'Upgrade');
    // ... WebSocket handshake logic
}
```

## Static File Handling

The server automatically serves static files with appropriate MIME types:

- **HTML**: `text/html`
- **CSS**: `text/css`
- **JavaScript**: `application/javascript`
- **Images**: `image/png`, `image/jpeg`, `image/gif`, `image/svg+xml`
- **JSON**: `application/json`
- **Default**: `application/octet-stream`

## Security Features

1. **Path Traversal Protection**: Automatically prevents `../` attacks
2. **Request Isolation**: No data leakage between concurrent requests
3. **Session Security**: Proper session ID generation and management
4. **Input Validation**: Raw request data is properly parsed

## Limitations

1. **File Uploads**: No built-in `$_FILES` support (requires manual parsing)
2. **WebSockets**: Not currently supported (future enhancement)
3. **HTTP/2**: Only HTTP/1.1 is supported
4. **SSL/TLS**: No built-in HTTPS support (use reverse proxy)

## Migration from Traditional PHP

```php
// Traditional PHP
session_start();
$_SESSION['data'] = 'value';

// Fiber Server (sessions auto-started)
$_SESSION['data'] = 'value';
```

```php
// Traditional PHP
header('Content-Type: application/json');
http_response_code(404);

// Fiber Server
$server_context->setHeader('Content-Type', 'application/json');
$server_context->setResponseCode(404);
```

This API reference covers all available functionality in the PHP Fiber Web Server. For complete examples and usage patterns, see the Developer Guide.
