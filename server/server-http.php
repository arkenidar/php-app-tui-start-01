<?php
/**
 * PHP Fiber-based Web Server with Cookie and In-Memory Session Support
 *
 * This script implements a lightweight web server using PHP Fibers for concurrency.
 * It now includes basic support for cookies and sessions using a shared variable
 * to store session data in memory.
 *
 * Usage:
 *   php server.php
 *
 * @package PHPFiberWebServer
 */

/**
 * Class representing an HTTP request.
 */
class HttpRequest {
    public string $method;
    public string $uri;
    public array $headers = [];
    public string $body;

    /**
     * Construct an HttpRequest by parsing a raw HTTP request string.
     *
     * @param string $rawRequest The raw HTTP request.
     */
    public function __construct(string $rawRequest) {
        // Split the raw request into header and body parts.
        $parts = explode("\r\n\r\n", $rawRequest, 2);
        $headerPart = $parts[0];
        $this->body = $parts[1] ?? '';

        // Split header part into individual lines.
        $lines = explode("\r\n", $headerPart);
        $firstLine = array_shift($lines);
        // Parse the request line (e.g., "GET /path HTTP/1.1").
        $requestLine = explode(' ', $firstLine);
        $this->method = $requestLine[0] ?? 'GET';
        $this->uri    = $requestLine[1] ?? '/';

        // Process remaining header lines.
        foreach ($lines as $line) {
            if (strpos($line, ': ') !== false) {
                list($key, $value) = explode(': ', $line, 2);
                $this->headers[$key] = trim($value);
            }
        }
    }

    /**
     * Parse and return GET parameters from the URI.
     *
     * @return array Associative array of GET parameters.
     */
    public function getQueryParams(): array {
        $queryString = parse_url($this->uri, PHP_URL_QUERY) ?? '';
        $params = [];
        parse_str($queryString, $params);
        return $params;
    }

    /**
     * Parse the request body based on the Content-Type header.
     *
     * @return mixed Returns an associative array for JSON or URL-encoded data,
     *               or the raw body string if no parsing is applicable.
     */
    public function getParsedBody() {
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

    /**
     * Parse and return cookies from the Cookie header.
     *
     * @return array Associative array of cookies.
     */
    public function getCookies(): array {
        $cookies = [];
        if (isset($this->headers['Cookie'])) {
            $cookieHeader = $this->headers['Cookie'];
            $cookiePairs = explode(';', $cookieHeader);
            foreach ($cookiePairs as $cookie) {
                $cookie = trim($cookie);
                if (!$cookie) {
                    continue;
                }
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
 * Class representing the Fiber-based web server.
 */
class WebServer {
    protected string $host;
    protected int $port;
    protected string $documentRoot;
    protected $serverSocket;
    protected array $sharedVariables = [
        "counter"   => 1,
        "container" => [],
        // Initialize a shared variable for sessions.
        "sessions"  => []
    ];

    // Cookie support: store outgoing cookies.
    protected array $responseCookies = [];
    
    // Store the current session ID.
    protected string $sessionId;

    /**
     * Constructor to initialize server settings.
     *
     * @param string $host         Host address to bind the server.
     * @param int    $port         Port number to listen on.
     * @param string $documentRoot Path to the document root directory.
     */
    public function __construct(string $host, int $port, string $documentRoot) {
        $this->host = $host;
        $this->port = $port;
        $this->documentRoot = $documentRoot;
    }

    /**
     * Register a cookie to be sent with the response.
     *
     * @param string $name     Cookie name.
     * @param string $value    Cookie value.
     * @param int    $expire   Expiration time as a Unix timestamp (0 for session cookie).
     * @param string $path     Cookie path.
     * @param string $domain   Cookie domain.
     * @param bool   $secure   Whether the cookie should only be sent over secure connections.
     * @param bool   $httponly Whether the cookie is accessible only through the HTTP protocol.
     */
    public function setCookie(
        string $name,
        string $value,
        int $expire = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httponly = false
    ): void {
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

    /**
     * Start a session by checking for a session cookie and loading session data
     * from a shared variable. If no session exists, a new session is created.
     */
    protected function startSession(): void {
        // Use in-memory shared variable for sessions.
        if (isset($_COOKIE['PHPSESSID']) && !empty($_COOKIE['PHPSESSID'])
            && isset($this->sharedVariables['sessions'][$_COOKIE['PHPSESSID']])) {
            $this->sessionId = $_COOKIE['PHPSESSID'];
            $_SESSION = $this->sharedVariables['sessions'][$this->sessionId];
        } else {
            $this->sessionId = bin2hex(random_bytes(16));
            $this->setCookie('PHPSESSID', $this->sessionId);
            $_SESSION = [];
            $this->sharedVariables['sessions'][$this->sessionId] = $_SESSION;
        }
    }

    /**
     * Save the session data back to the shared variable.
     */
    protected function endSession(): void {
        if (isset($this->sessionId)) {
            $this->sharedVariables['sessions'][$this->sessionId] = $_SESSION;
        }
    }

    /**
     * Run the web server: create a socket, listen for connections,
     * and handle each connection using Fibers for concurrency.
     */
    public function run(): void {
        // Ensure the document root directory exists.
        if (!is_dir($this->documentRoot)) {
            mkdir($this->documentRoot, 0755, true);
        }

        // Create a non-blocking server socket.
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

        echo "PHP Fiber Web Server running at http://{$this->host}:{$this->port}\n";

        // Main loop: continuously accept and process connections.
        while (true) {
            $conn = @stream_socket_accept($this->serverSocket, 0);
            if ($conn === false) {
                usleep(10000); // Sleep for 10ms to reduce CPU usage.
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

    /**
     * Handle an individual client connection.
     *
     * @param resource $conn The client connection resource.
     */
    protected function handleConnection($conn): void {
        stream_set_timeout($conn, 2);
        $requestContent = stream_get_contents($conn, 4096);
        if (!$requestContent) {
            fclose($conn);
            return;
        }

        try {
            $request = new HttpRequest($requestContent);

            // Parse and set cookies for dynamic scripts.
            $_COOKIE = $request->getCookies();

            $getParams = $request->getQueryParams();
            $postBody = $request->getParsedBody();

            // Sanitize the URI to prevent directory traversal.
            $safeUri = parse_url($request->uri, PHP_URL_PATH);
            $assembledPath = $this->documentRoot . str_replace("/", DIRECTORY_SEPARATOR, $safeUri);
            $path = realpath($assembledPath);

            // Ensure the resolved path is within the document root.
            if ($path === false || strpos($path, realpath($this->documentRoot)) !== 0) {
                $this->respond404($conn);
                return;
            }

            // If a directory is requested, append index.php.
            if (is_dir($path)) {
                $path .= DIRECTORY_SEPARATOR . 'index.php';
            }

            // Route the request: if file exists, serve dynamic or static content.
            if (file_exists($path)) {
                if (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
                    $this->respondDynamic($conn, $path, $request->method, $getParams, $postBody, $request->headers);
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

    /**
     * Serve a static file.
     *
     * @param resource $conn The client connection resource.
     * @param string   $path The full path to the static file.
     */
    protected function respondStatic($conn, string $path): void {
        // Determine MIME type using Fileinfo if available.
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $path);
            finfo_close($finfo);
        } else {
            // Fallback MIME types.
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

        // Build and send HTTP headers.
        $headers = "HTTP/1.1 200 OK\r\nContent-Type: $mimeType\r\nConnection: close\r\n\r\n";
        fwrite($conn, $headers);

        // Stream the file content.
        $file = fopen($path, 'rb');
        if ($file) {
            while (!feof($file)) {
                $buffer = fread($file, 8192);
                fwrite($conn, $buffer);
            }
            fclose($file);
        } else {
            $this->respond500($conn, "Failed to open file: $path");
            return;
        }

        fclose($conn);
    }

    /**
     * Execute a PHP script dynamically and send its output.
     *
     * @param resource $conn      The client connection resource.
     * @param string   $path      Path to the PHP script.
     * @param string   $method    HTTP request method.
     * @param array    $getParams GET parameters.
     * @param mixed    $postBody  Parsed POST body.
     * @param array    $headers   Request headers.
     */
    protected function respondDynamic(
        $conn,
        string $path,
        string $method,
        array $getParams,
        $postBody,
        array $headers
    ): void {
        // Set up superglobals for the dynamic script.
        $_GET = $getParams;
        $_POST = $postBody;
        // $_COOKIE is already set in handleConnection()

        // Start session management using the shared sessions variable.
        $this->startSession();

        // Expose shared variables to dynamic scripts.
        $shared_variables = &$this->sharedVariables;
        // Provide the server instance so scripts can set cookies.
        $shared_variables['server'] = $this;

        // Default additional headers for dynamic responses.
        $additionalResponseHeaders = [
            "Content-Type" => "text/html",
            "Connection"   => "close"
        ];

        // By default, we use HTTP 200 OK.
        $responseCode = 200;

        // Capture the output of the dynamic PHP script.
        ob_start();
        require $path;
        $content = ob_get_clean();

        // Save session data back to the shared variable.
        $this->endSession();

        // Build HTTP response headers.
        $responseHeaders = "HTTP/1.1 $responseCode OK\r\n";
        foreach ($additionalResponseHeaders as $key => $value) {
            $responseHeaders .= "$key: $value\r\n";
        }
        // Append any Set-Cookie headers if cookies were set.
        foreach ($this->responseCookies as $cookie) {
            $responseHeaders .= "Set-Cookie: $cookie\r\n";
        }
        // Clear cookies after adding them.
        $this->responseCookies = [];

        // Send headers and content.
        fwrite($conn, $responseHeaders . "\r\n" . $content);
        fclose($conn);
    }

    /**
     * Send a 404 Not Found response.
     *
     * @param resource $conn The client connection resource.
     */
    protected function respond404($conn): void {
        $content = "<h1>404 Not Found</h1>";
        $headers = "HTTP/1.1 404 Not Found\r\nContent-Type: text/html\r\nConnection: close\r\n\r\n";
        fwrite($conn, $headers . $content);
        fclose($conn);
    }

    /**
     * Send a 500 Internal Server Error response.
     *
     * @param resource $conn         The client connection resource.
     * @param string   $errorMessage Error message to display.
     */
    protected function respond500($conn, string $errorMessage): void {
        $content = "<h1>500 Internal Server Error</h1><p>$errorMessage</p>";
        $headers = "HTTP/1.1 500 Internal Server Error\r\nContent-Type: text/html\r\nConnection: close\r\n\r\n";
        fwrite($conn, $headers . $content);
        fclose($conn);
    }
}

// -----------------
// Server Execution
// -----------------

// Configuration settings.
$host = '127.0.0.1';  // Server host.
$port = 8000;         // Server port.
$documentRoot = __DIR__ . DIRECTORY_SEPARATOR . 'public';  // Document root directory.

// Create and run the web server.
$server = new WebServer($host, $port, $documentRoot);
$server->run();

// End of server-http.php
