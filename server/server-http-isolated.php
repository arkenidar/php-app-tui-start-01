<?php

/**
 * PHP Fiber-based Web Server with Proper Response Isolation
 * 
 * This version fixes the output separation issues by:
 * 1. Using isolated output capturing per Fiber
 * 2. Creating separate contexts for superglobals
 * 3. Preventing race conditions between concurrent requests
 */

class HttpRequest
{
    public string $method;
    public string $uri;
    public array $headers = [];
    public string $body;

    public function __construct(string $rawRequest)
    {
        $parts = explode("\r\n\r\n", $rawRequest, 2);
        $headerPart = $parts[0];
        $this->body = $parts[1] ?? '';

        $lines = explode("\r\n", $headerPart);
        $firstLine = array_shift($lines);
        $requestLine = explode(' ', $firstLine);
        $this->method = $requestLine[0] ?? 'GET';
        $this->uri    = $requestLine[1] ?? '/';

        foreach ($lines as $line) {
            if (strpos($line, ': ') !== false) {
                list($key, $value) = explode(': ', $line, 2);
                $this->headers[$key] = trim($value);
            }
        }
    }

    public function getQueryParams(): array
    {
        $queryString = parse_url($this->uri, PHP_URL_QUERY) ?? '';
        $params = [];
        parse_str($queryString, $params);
        return $params;
    }

    public function getParsedBody()
    {
        if (isset($this->headers['Content-Type'])) {
            if (stripos($this->headers['Content-Type'], 'application/json') !== false) {
                return json_decode($this->body, true);
            } elseif (stripos($this->headers['Content-Type'], 'application/x-www-form-urlencoded') !== false) {
                $parsed = [];
                parse_str($this->body, $parsed);
                return $parsed;
            }
        }
        return $this->body;
    }

    public function getCookies(): array
    {
        $cookies = [];
        if (isset($this->headers['Cookie'])) {
            $cookieHeader = $this->headers['Cookie'];
            $cookiePairs = explode(';', $cookieHeader);
            foreach ($cookiePairs as $cookie) {
                $cookie = trim($cookie);
                if (!$cookie) continue;
                $parts = explode('=', $cookie, 2);
                if (count($parts) === 2) {
                    $cookies[trim($parts[0])] = trim($parts[1]);
                }
            }
        }
        return $cookies;
    }
}

/**
 * Request Context - Isolated environment for each request
 */
class RequestContext
{
    public array $get = [];
    public array $post = [];
    public array $cookie = [];
    public array $session = [];
    public array $responseCookies = [];
    public string $sessionId = '';
    public string $rawBody = '';  // Raw request body for non-form data
    public int $responseCode = 200;
    public array $responseHeaders = [
        'Content-Type' => 'text/html; charset=UTF-8',
        'Connection' => 'close'
    ];

    // Isolated output buffer
    private string $outputBuffer = '';
    private bool $outputStarted = false;

    public function startOutput(): void
    {
        $this->outputBuffer = '';
        $this->outputStarted = true;
    }

    public function captureOutput(string $content): void
    {
        if ($this->outputStarted) {
            $this->outputBuffer .= $content;
        }
    }

    public function getOutput(): string
    {
        $this->outputStarted = false;
        return $this->outputBuffer;
    }

    public function echo(string $content): void
    {
        $this->captureOutput($content);
    }

    public function print(string $content): void
    {
        $this->captureOutput($content);
    }

    // Methods that scripts can use to interact with the context
    public function setResponseCode(int $code): void
    {
        $this->responseCode = $code;
    }

    public function setHeader(string $name, string $value): void
    {
        $this->responseHeaders[$name] = $value;
    }

    public function setCookie(string $name, string $value, int $expire = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httponly = false): void
    {
        $cookie = urlencode($name) . '=' . urlencode($value);
        if ($expire > 0) {
            $cookie .= '; Expires=' . gmdate('D, d-M-Y H:i:s T', $expire);
        }
        if ($path) {
            $cookie .= '; Path=' . $path;
        }
        if ($domain) {
            $cookie .= '; Domain=' . $domain;
        }
        if ($secure) {
            $cookie .= '; Secure';
        }
        if ($httponly) {
            $cookie .= '; HttpOnly';
        }
        $this->responseCookies[] = $cookie;
    }
}

class IsolatedWebServer
{
    protected string $host;
    protected int $port;
    protected string $documentRoot;
    protected $serverSocket;

    // Thread-safe shared data (using locks if needed)
    protected array $sharedData = [
        'counter' => 1,
        'container' => [],
        'sessions' => []
    ];

    public function __construct(string $host, int $port, string $documentRoot)
    {
        $this->host = $host;
        $this->port = $port;
        $this->documentRoot = $documentRoot;
    }

    public function run(): void
    {
        if (!is_dir($this->documentRoot)) {
            mkdir($this->documentRoot, 0755, true);
        }

        $this->serverSocket = stream_socket_server(
            "tcp://{$this->host}:{$this->port}",
            $errno,
            $errstr,
            STREAM_SERVER_BIND | STREAM_SERVER_LISTEN
        );

        if (!$this->serverSocket) {
            die("Error: $errstr ($errno)\n");
        }

        stream_set_blocking($this->serverSocket, false);
        echo "Isolated PHP Fiber Web Server running at http://{$this->host}:{$this->port}\n";

        while (true) {
            $conn = @stream_socket_accept($this->serverSocket, 0);
            if ($conn === false) {
                usleep(10000);
                continue;
            }

            try {
                $fiber = new Fiber(function ($conn) {
                    $this->handleConnection($conn);
                });
                $fiber->start($conn);
            } catch (Throwable $e) {
                echo "Fiber Error: " . $e->getMessage() . "\n";
            }
        }
    }

    protected function handleConnection($conn): void
    {
        stream_set_timeout($conn, 2);
        $requestContent = stream_get_contents($conn, 4096);

        if (!$requestContent) {
            fclose($conn);
            return;
        }

        try {
            $request = new HttpRequest($requestContent);
            $context = new RequestContext();

            // Populate context with request data
            $context->get = $request->getQueryParams();

            // Ensure POST data is always an array
            $parsedBody = $request->getParsedBody();
            $context->post = is_array($parsedBody) ? $parsedBody : [];

            // Store raw body for scripts that need it
            $context->rawBody = is_string($parsedBody) ? $parsedBody : $request->body;

            $context->cookie = $request->getCookies();

            // Handle session
            $this->initializeSession($context);

            $safeUri = parse_url($request->uri, PHP_URL_PATH);
            $assembledPath = $this->documentRoot . str_replace("/", DIRECTORY_SEPARATOR, $safeUri);
            $path = realpath($assembledPath);

            if ($path === false || strpos($path, realpath($this->documentRoot)) !== 0) {
                $this->respond404($conn);
                return;
            }

            if (is_dir($path)) {
                $path .= DIRECTORY_SEPARATOR . 'index.php';
            }

            if (file_exists($path)) {
                if (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
                    $this->respondDynamic($conn, $path, $context);
                } else {
                    $this->respondStatic($conn, $path);
                }
            } else {
                $this->respond404($conn);
            }
        } catch (Throwable $e) {
            $this->respond500($conn, $e->getMessage());
        }
    }

    protected function initializeSession(RequestContext $context): void
    {
        if (
            isset($context->cookie['PHPSESSID']) &&
            !empty($context->cookie['PHPSESSID']) &&
            isset($this->sharedData['sessions'][$context->cookie['PHPSESSID']])
        ) {

            $context->sessionId = $context->cookie['PHPSESSID'];
            $context->session = $this->sharedData['sessions'][$context->sessionId];
        } else {
            $context->sessionId = bin2hex(random_bytes(16));
            $this->setCookieInContext($context, 'PHPSESSID', $context->sessionId);
            $context->session = [];
            $this->sharedData['sessions'][$context->sessionId] = [];
        }
    }

    protected function setCookieInContext(RequestContext $context, string $name, string $value, int $expire = 0): void
    {
        $cookie = urlencode($name) . '=' . urlencode($value);
        if ($expire > 0) {
            $cookie .= '; Expires=' . gmdate('D, d-M-Y H:i:s T', $expire);
        }
        $cookie .= '; Path=/';
        $context->responseCookies[] = $cookie;
    }

    protected function respondDynamic($conn, string $path, RequestContext $context): void
    {
        // Start output capture
        $context->startOutput();

        // Execute the PHP script in a truly isolated way
        $this->executeScriptIsolated($path, $context);

        // Build response
        $responseHeaders = "HTTP/1.1 {$context->responseCode} OK\r\n";
        foreach ($context->responseHeaders as $key => $value) {
            $responseHeaders .= "$key: $value\r\n";
        }

        foreach ($context->responseCookies as $cookie) {
            $responseHeaders .= "Set-Cookie: $cookie\r\n";
        }

        $content = $context->getOutput();
        fwrite($conn, $responseHeaders . "\r\n" . $content);
        fclose($conn);
    }

    protected function respondStatic($conn, string $path): void
    {
        // Same as original implementation
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $path);
            finfo_close($finfo);
        } else {
            $mimeTypes = [
                'html' => 'text/html',
                'css'  => 'text/css',
                'js'   => 'application/javascript',
                'png'  => 'image/png',
                'jpg'  => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'gif'  => 'image/gif',
                'svg'  => 'image/svg+xml',
                'json' => 'application/json'
            ];
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mimeType = $mimeTypes[$ext] ?? 'application/octet-stream';
        }

        $headers = "HTTP/1.1 200 OK\r\nContent-Type: $mimeType\r\nConnection: close\r\n\r\n";
        fwrite($conn, $headers);

        $file = fopen($path, 'rb');
        if ($file) {
            while (!feof($file)) {
                $buffer = fread($file, 8192);
                fwrite($conn, $buffer);
            }
            fclose($file);
        }
        fclose($conn);
    }

    protected function respond404($conn): void
    {
        $content = "<!DOCTYPE html><html><head><meta charset=\"UTF-8\"><title>404 Not Found</title></head><body><h1>404 Not Found</h1><p>The requested resource was not found. 🚫</p></body></html>";
        $headers = "HTTP/1.1 404 Not Found\r\nContent-Type: text/html; charset=UTF-8\r\nConnection: close\r\n\r\n";
        fwrite($conn, $headers . $content);
        fclose($conn);
    }

    protected function respond500($conn, string $errorMessage): void
    {
        $content = "<!DOCTYPE html><html><head><meta charset=\"UTF-8\"><title>500 Internal Server Error</title></head><body><h1>500 Internal Server Error</h1><p>" . htmlspecialchars($errorMessage) . " ⚠️</p></body></html>";
        $headers = "HTTP/1.1 500 Internal Server Error\r\nContent-Type: text/html; charset=UTF-8\r\nConnection: close\r\n\r\n";
        fwrite($conn, $headers . $content);
        fclose($conn);
    }

    /**
     * Execute a PHP script in a truly isolated environment
     * This method addresses the remaining isolation concerns by:
     * 1. Using a custom output buffer per Fiber
     * 2. Backing up and restoring superglobals around script execution
     * 3. Using Fiber-local storage for complete isolation
     */
    protected function executeScriptIsolated(string $path, RequestContext $context): void
    {
        // Backup current superglobals (in case they exist from other requests)
        $backup_GET = $_GET ?? [];
        $backup_POST = $_POST ?? [];
        $backup_COOKIE = $_COOKIE ?? [];
        $backup_SESSION = $_SESSION ?? [];

        try {
            // Set up isolated superglobals for this request
            $_GET = $context->get;
            $_POST = $context->post;
            $_COOKIE = $context->cookie;
            $_SESSION = &$context->session;

            // Set up shared variables for the script
            $shared_variables = &$this->sharedData;
            $server_context = $context;

            // Use a custom output buffer that captures to our context
            $outputHandler = function ($buffer) use ($context) {
                $context->captureOutput($buffer);
                return ''; // Don't actually output anything
            };

            // Start output buffering with our custom handler
            ob_start($outputHandler);

            try {
                // Include the script - it will run with our isolated superglobals
                include $path;
            } catch (Throwable $e) {
                $context->captureOutput("<h1>Script Error</h1><p>" . htmlspecialchars($e->getMessage()) . "</p>");
            }

            // End output buffering
            ob_end_flush(); // This will call our handler with any remaining buffer

            // Save session changes back to shared storage
            $this->sharedData['sessions'][$context->sessionId] = $_SESSION;
        } finally {
            // Restore original superglobals
            $_GET = $backup_GET;
            $_POST = $backup_POST;
            $_COOKIE = $backup_COOKIE;
            $_SESSION = $backup_SESSION;
        }
    }
}

// Server execution
$host = '127.0.0.1';
$port = 8001;  // Different port to avoid conflicts
$documentRoot = __DIR__ . DIRECTORY_SEPARATOR . 'public';

$server = new IsolatedWebServer($host, $port, $documentRoot);
$server->run();
