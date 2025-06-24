# Testing Documentation

## Test Suite Overview

The PHP Fiber Web Server includes a comprehensive test suite that verifies all aspects of request isolation, performance, and functionality.

## Running Tests

### Prerequisites

1. Start the server:

```bash
php server/server-http-isolated.php
```

2. Install dependencies (if not already installed):

```bash
# Ubuntu/Debian
sudo apt-get install curl jq bc

# macOS
brew install curl jq bc
```

### Running All Tests

```bash
cd server
./run_tests.sh
```

### Running Specific Test Categories

```bash
# Basic functionality tests
./run_tests.sh basic

# Request isolation tests
./run_tests.sh isolation

# Session management tests
./run_tests.sh session

# Load testing
./run_tests.sh load

# Error handling tests
./run_tests.sh error

# Content type tests
./run_tests.sh content

# Performance tests
./run_tests.sh performance
```

## Test Categories

### 1. Basic Functionality Tests

- **Server Availability**: Confirms server is running and responding
- **Static File Serving**: Tests serving of PHP files
- **GET Parameters**: Verifies query parameter processing
- **404 Error Handling**: Tests missing file responses

### 2. Request Isolation Tests

- **Concurrent Request Handling**: 10 simultaneous requests with unique parameters
- **Parameter Isolation**: Ensures each request sees only its own parameters
- **Cross-Contamination Detection**: Verifies no data bleeding between requests
- **Response Integrity**: Confirms each response contains only its own data

### 3. Session Management Tests

- **Session Continuity**: Tests session persistence across requests
- **Session Isolation**: Verifies different sessions remain separate
- **Cookie Handling**: Tests session cookie creation and management
- **Session Data Integrity**: Ensures session data doesn't mix between users

### 4. Concurrent Load Tests

- **High Concurrency**: Tests 20+ simultaneous requests
- **Performance Benchmarking**: Measures response times and throughput
- **Success Rate**: Verifies all requests complete successfully
- **Resource Management**: Tests server stability under load

### 5. Error Handling Tests

- **Script Error Isolation**: Tests that PHP errors don't affect other requests
- **Error Response Format**: Verifies proper error page generation
- **Server Stability**: Ensures errors don't crash the server
- **Recovery Testing**: Tests continued operation after errors

### 6. Content Type Tests

- **JSON API Responses**: Tests JSON content type handling
- **Custom Response Codes**: Verifies HTTP status code setting
- **Header Management**: Tests custom header functionality
- **Content Negotiation**: Tests different response formats

### 7. Memory and Performance Tests

- **Shared Variable Persistence**: Tests cross-request data sharing
- **Memory Usage**: Monitors server memory consumption
- **Performance Benchmarking**: Measures requests per second
- **Resource Cleanup**: Verifies proper resource management

## Manual Testing

### Using curl

```bash
# Basic request
curl "http://127.0.0.1:8001/isolation-test-complete.php?request_id=1&param=100"

# JSON API test
curl -H "Accept: application/json" "http://127.0.0.1:8001/api/users.php?id=1"

# POST request test
curl -X POST -d "name=John&email=john@example.com" "http://127.0.0.1:8001/api/users.php"

# Session test with cookies
curl -c cookies.txt -b cookies.txt "http://127.0.0.1:8001/cart-demo.php"
```

### Browser Testing

1. **Isolation Test**: Open multiple tabs to `http://127.0.0.1:8001/isolation-test-complete.php?request_id=X`
2. **Session Test**: Use `http://127.0.0.1:8001/cart-demo.php` in multiple browser windows
3. **Performance Monitor**: Visit `http://127.0.0.1:8001/performance-monitor.php`

## Test Results

### Success Criteria

- **100% Success Rate**: All tests must pass for production readiness
- **Response Time**: Average response time should be < 100ms for simple requests
- **Concurrency**: Server should handle 20+ simultaneous requests without errors
- **Isolation**: Zero cross-contamination between concurrent requests
- **Memory**: Memory usage should remain stable during load tests

### Result Interpretation

```bash
# Example successful test output
✓ PASS Server Availability
✓ PASS Static File Serving
✓ PASS GET Parameters
✓ PASS Request Isolation
✓ PASS Session Continuity
✓ PASS Performance Benchmark - 45.67 requests/second

Total Tests: 25
Passed: 25
Failed: 0
Success Rate: 100%
```

### Test Artifacts

After running tests, the following files are generated:

- `test_results/test.log` - Detailed test execution log
- `test_results/test_report.html` - HTML test report
- `test_results/isolation/` - Individual response files from isolation tests
- `test_results/load/` - Load test results and timing data

## Continuous Integration

### GitHub Actions Example

```yaml
name: PHP Fiber Server Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v2

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: "8.1"
          extensions: fiber

      - name: Start Server
        run: |
          cd server
          php server-http-isolated.php &
          sleep 2

      - name: Run Tests
        run: |
          cd server
          ./run_tests.sh

      - name: Upload Test Results
        uses: actions/upload-artifact@v2
        if: always()
        with:
          name: test-results
          path: server/test_results/
```

## Debugging Tests

### Common Issues

1. **Server Not Running**

   ```bash
   # Check if server is running
   curl -I http://127.0.0.1:8001

   # Start server if needed
   php server/server-http-isolated.php &
   ```

2. **Permission Issues**

   ```bash
   # Make test scripts executable
   chmod +x run_tests.sh
   chmod +x test-complete-isolation.sh
   ```

3. **Missing Dependencies**
   ```bash
   # Install required tools
   sudo apt-get install curl jq bc
   ```

### Verbose Testing

```bash
# Run with verbose output
bash -x ./run_tests.sh

# Test specific endpoint manually
curl -v "http://127.0.0.1:8001/isolation-test-complete.php?request_id=debug"
```

### Performance Debugging

```bash
# Monitor server performance during tests
top -p $(pgrep -f "server-http-isolated.php")

# Check server logs
tail -f server_debug.log  # If logging is enabled
```

## Test Development

### Adding New Tests

1. **Add test function** to `run_tests.sh`:

```bash
test_new_feature() {
    echo -e "\n${YELLOW}=== Test: New Feature ===${NC}"

    # Your test logic here
    local result=$(curl -s "$SERVER_URL/new-feature.php")

    if echo "$result" | grep -q "expected"; then
        log_test "New Feature" "PASS" "Feature works correctly"
    else
        log_test "New Feature" "FAIL" "Feature not working"
    fi
}
```

2. **Add to main function**:

```bash
main() {
    # ... existing tests ...
    test_new_feature
    # ...
}
```

### Test Data Management

Create test fixtures in `server/public/tests/`:

```php
<?php
// test-fixture.php
return [
    'users' => [
        ['id' => 1, 'name' => 'Test User 1'],
        ['id' => 2, 'name' => 'Test User 2'],
    ],
    'expected_response' => 'success'
];
?>
```

## Security Testing

### Input Validation Tests

```bash
# Test XSS prevention
curl "http://127.0.0.1:8001/isolation-test-complete.php?request_id=<script>alert('xss')</script>"

# Test SQL injection (if using database)
curl "http://127.0.0.1:8001/api/users.php?id=1'; DROP TABLE users; --"

# Test path traversal
curl "http://127.0.0.1:8001/../../../etc/passwd"
```

### Security Test Checklist

- [ ] XSS prevention in output
- [ ] Path traversal protection
- [ ] Input sanitization
- [ ] Session security
- [ ] Cookie security flags
- [ ] Error message information disclosure

This comprehensive testing framework ensures that your PHP Fiber web server maintains proper isolation and performs reliably under various conditions.
