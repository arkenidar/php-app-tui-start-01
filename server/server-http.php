<?php

/**
 * Class representing an HTTP request.
 */
class HttpRequest {
    public string $method;
    public string $uri;
    public array $headers = [];
    public string $body;

    public function __construct(string $rawRequest) {
        // Split the raw request into header and body parts.
        $parts = explode("\r\n\r\n", $rawRequest, 2);
        $headerPart = $parts[0];
        $this->body = $parts[1] ?? '';

        // Split header part into lines.
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

    /**
     * Return parsed GET parameters from the URI.
     */
    public function getQueryParams(): array {
        $queryString = parse_url($this->uri, PHP_URL_QUERY) ?? '';
        $params = [];
        parse_str($queryString, $params);
        return $params;
    }

    /**
     * Parse the request body based on the Content-Type header.
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
    ];

    public function __construct(string $host, int $port, string $documentRoot) {
        $this->host = $host;
        $this->port = $port;
        $this->documentRoot = $documentRoot;
    }

    /**
     * Run the web server.
     */
    public function run(): void {
        // Ensure document root exists.
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

        echo "PHP Fiber Web Server running at http://{$this->host}:{$this->port}\n";

        // Main loop: accept connections and handle them in a Fiber.
        while (true) {
            $conn = @stream_socket_accept($this->serverSocket, 0);
            if ($conn === false) {
                usleep(10000); // Sleep 10ms to avoid CPU overuse.
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
     * Handle an individual connection.
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
            $getParams = $request->getQueryParams();
            $postBody = $request->getParsedBody();

            // Sanitize the URI to prevent directory traversal.
            $safeUri = str_replace(['../', '..\\'], '', parse_url($request->uri, PHP_URL_PATH));
            $path = realpath($this->documentRoot . DIRECTORY_SEPARATOR . ltrim($safeUri, '/\\'));

            // Ensure the resolved path is within the document root.
            if (!$path || strpos($path, realpath($this->documentRoot)) !== 0) {
                $this->respond404($conn);
                return;
            }

            // If a directory is requested, look for an index.php file.
            if (is_dir($path)) {
                $path .= DIRECTORY_SEPARATOR . 'index.php';
            }

            // Route the request: dynamic PHP script or static file.
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
     * Send a static file as a response.
     */
    protected function respondStatic($conn, string $path): void {
        // Determine MIME type using Fileinfo if available.
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
        } else {
            $this->respond500($conn, "Failed to open file: $path");
            return;
        }

        fclose($conn);
    }

    /**
     * Execute a PHP script dynamically and send its output.
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

        // Optionally, expose shared variables to dynamic scripts.
        $shared_variables = $this->sharedVariables;
        // You might later update $this->sharedVariables if dynamic scripts modify it.

        ob_start();
        require $path;
        $content = ob_get_clean();

        $responseHeaders = "HTTP/1.1 200 OK\r\nContent-Type: text/html\r\nConnection: close\r\n\r\n";
        fwrite($conn, $responseHeaders . $content);
        fclose($conn);
    }

    /**
     * Send a 404 Not Found response.
     */
    protected function respond404($conn): void {
        $content = "<h1>404 Not Found</h1>";
        $headers = "HTTP/1.1 404 Not Found\r\nContent-Type: text/html\r\nConnection: close\r\n\r\n";
        fwrite($conn, $headers . $content);
        fclose($conn);
    }

    /**
     * Send a 500 Internal Server Error response.
     */
    protected function respond500($conn, string $errorMessage): void {
        $content = "<h1>500 Internal Server Error</h1><p>$errorMessage</p>";
        $headers = "HTTP/1.1 500 Internal Server Error\r\nContent-Type: text/html\r\nConnection: close\r\n\r\n";
        fwrite($conn, $headers . $content);
        fclose($conn);
    }
}

// Configuration values.
$host = '127.0.0.1';
$port = 8000;
$documentRoot = __DIR__ . DIRECTORY_SEPARATOR . 'public';

// Create and run the web server.
$server = new WebServer($host, $port, $documentRoot);
$server->run();
