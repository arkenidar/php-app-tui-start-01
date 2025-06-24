#!/bin/bash

# Comprehensive Test Suite for PHP Fiber Web Server
# This script runs all test categories and generates detailed reports

set -e

# Configuration
SERVER_HOST="127.0.0.1"
SERVER_PORT="8001"
SERVER_URL="http://$SERVER_HOST:$SERVER_PORT"
TEST_DIR="$(dirname "$0")"
PUBLIC_DIR="$TEST_DIR/public"
RESULTS_DIR="$TEST_DIR/test_results"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Test counters
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

# Create results directory
mkdir -p "$RESULTS_DIR"

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  PHP Fiber Web Server Test Suite      ${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Function to log test results
log_test() {
    local test_name="$1"
    local status="$2"
    local message="$3"
    
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
    
    if [ "$status" = "PASS" ]; then
        PASSED_TESTS=$((PASSED_TESTS + 1))
        echo -e "${GREEN}✓ PASS${NC} $test_name"
    else
        FAILED_TESTS=$((FAILED_TESTS + 1))
        echo -e "${RED}✗ FAIL${NC} $test_name - $message"
    fi
    
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $status - $test_name - $message" >> "$RESULTS_DIR/test.log"
}

# Check if server is running
check_server() {
    echo -e "${YELLOW}Checking server availability...${NC}"
    
    if curl -s --max-time 2 "$SERVER_URL" > /dev/null 2>&1; then
        log_test "Server Availability" "PASS" "Server is running on $SERVER_URL"
        return 0
    else
        log_test "Server Availability" "FAIL" "Server is not responding on $SERVER_URL"
        echo -e "${RED}Please start the server with: php server/server-http-isolated.php${NC}"
        exit 1
    fi
}

# Test 1: Basic Functionality
test_basic_functionality() {
    echo -e "\n${YELLOW}=== Test 1: Basic Functionality ===${NC}"
    
    # Test static file serving
    local static_response=$(curl -s -w "%{http_code}" "$SERVER_URL/isolation-test-complete.php" -o /dev/null)
    if [ "$static_response" = "200" ]; then
        log_test "Static File Serving" "PASS" "HTTP 200 response received"
    else
        log_test "Static File Serving" "FAIL" "Expected HTTP 200, got $static_response"
    fi
    
    # Test GET parameters
    local get_response=$(curl -s "$SERVER_URL/isolation-test-complete.php?test=basic")
    if echo "$get_response" | grep -q "test"; then
        log_test "GET Parameters" "PASS" "GET parameters processed correctly"
    else
        log_test "GET Parameters" "FAIL" "GET parameters not processed"
    fi
    
    # Test 404 handling
    local not_found_response=$(curl -s -w "%{http_code}" "$SERVER_URL/nonexistent.php" -o /dev/null)
    if [ "$not_found_response" = "404" ]; then
        log_test "404 Error Handling" "PASS" "HTTP 404 response for missing files"
    else
        log_test "404 Error Handling" "FAIL" "Expected HTTP 404, got $not_found_response"
    fi
}

# Test 2: Request Isolation
test_request_isolation() {
    echo -e "\n${YELLOW}=== Test 2: Request Isolation ===${NC}"
    
    # Create test responses directory
    mkdir -p "$RESULTS_DIR/isolation"
    cd "$RESULTS_DIR/isolation"
    
    # Make concurrent requests with different parameters
    for i in {1..10}; do
        curl -s "$SERVER_URL/isolation-test-complete.php?request_id=$i&param=$((i * 100))" > "response_$i.html" &
    done
    
    # Wait for all requests to complete
    wait
    
    # Analyze responses for isolation violations
    local isolation_errors=0
    
    for i in {1..10}; do
        if [ -f "response_$i.html" ]; then
            # Check if request ID is correct
            local found_id=$(grep -o "Request ID:</strong> [^<]*" "response_$i.html" | head -1 | sed 's/.*> //')
            if [ "$found_id" != "$i" ]; then
                isolation_errors=$((isolation_errors + 1))
                echo "  ❌ Response $i has wrong request ID: $found_id" >> isolation_errors.log
            fi
            
            # Check if parameters are correct
            local found_param=$(grep -o "Request Param:</strong> [^<]*" "response_$i.html" | head -1 | sed 's/.*> //')
            local expected_param=$((i * 100))
            if [ "$found_param" != "$expected_param" ]; then
                isolation_errors=$((isolation_errors + 1))
                echo "  ❌ Response $i has wrong parameter: $found_param (expected $expected_param)" >> isolation_errors.log
            fi
            
            # Check for cross-contamination
            local other_ids=$(grep -o "Request ID:</strong> [^<]*" "response_$i.html" | sed 's/.*> //' | grep -v "^$i$" | wc -l)
            if [ "$other_ids" -gt 0 ]; then
                isolation_errors=$((isolation_errors + 1))
                echo "  ❌ Response $i contains other request IDs" >> isolation_errors.log
            fi
        fi
    done
    
    if [ $isolation_errors -eq 0 ]; then
        log_test "Request Isolation" "PASS" "No isolation violations detected"
    else
        log_test "Request Isolation" "FAIL" "$isolation_errors isolation violations found"
    fi
    
    cd - > /dev/null
}

# Test 3: Session Management
test_session_management() {
    echo -e "\n${YELLOW}=== Test 3: Session Management ===${NC}"
    
    local cookie_jar="$RESULTS_DIR/cookies.txt"
    
    # First request - establish session
    local response1=$(curl -s -c "$cookie_jar" "$SERVER_URL/isolation-test-complete.php?request_id=session1")
    local session_count1=$(echo "$response1" | grep -o "Session request count: [0-9]*" | grep -o "[0-9]*")
    
    # Second request - same session
    local response2=$(curl -s -b "$cookie_jar" -c "$cookie_jar" "$SERVER_URL/isolation-test-complete.php?request_id=session2")
    local session_count2=$(echo "$response2" | grep -o "Session request count: [0-9]*" | grep -o "[0-9]*")
    
    # Third request - new session (no cookies)
    local response3=$(curl -s "$SERVER_URL/isolation-test-complete.php?request_id=session3")
    local session_count3=$(echo "$response3" | grep -o "Session request count: [0-9]*" | grep -o "[0-9]*")
    
    # Verify session continuity
    if [ "$session_count1" = "1" ] && [ "$session_count2" = "2" ] && [ "$session_count3" = "1" ]; then
        log_test "Session Continuity" "PASS" "Sessions maintain state correctly"
    else
        log_test "Session Continuity" "FAIL" "Session counts: $session_count1, $session_count2, $session_count3"
    fi
    
    # Test session isolation between different sessions
    local cookie_jar2="$RESULTS_DIR/cookies2.txt"
    local response4=$(curl -s -c "$cookie_jar2" "$SERVER_URL/isolation-test-complete.php?request_id=session4")
    local session_count4=$(echo "$response4" | grep -o "Session request count: [0-9]*" | grep -o "[0-9]*")
    
    if [ "$session_count4" = "1" ]; then
        log_test "Session Isolation" "PASS" "Different sessions are properly isolated"
    else
        log_test "Session Isolation" "FAIL" "New session count should be 1, got $session_count4"
    fi
}

# Test 4: Concurrent Load Testing
test_concurrent_load() {
    echo -e "\n${YELLOW}=== Test 4: Concurrent Load Testing ===${NC}"
    
    local concurrent_requests=20
    local batch_size=5
    
    mkdir -p "$RESULTS_DIR/load"
    cd "$RESULTS_DIR/load"
    
    echo "  Running $concurrent_requests concurrent requests in batches of $batch_size..."
    
    # Function to make a timed request
    make_timed_request() {
        local id=$1
        local start_time=$(date +%s.%N)
        local http_code=$(curl -s -w "%{http_code}" -o "load_response_$id.html" \
                         "$SERVER_URL/isolation-test-complete.php?request_id=$id&load_test=true")
        local end_time=$(date +%s.%N)
        local duration=$(echo "$end_time - $start_time" | bc -l)
        echo "$id,$http_code,$duration" >> load_results.csv
    }
    
    # Create CSV header
    echo "request_id,http_code,duration" > load_results.csv
    
    # Run requests in batches
    for batch in $(seq 1 $((concurrent_requests / batch_size))); do
        echo "  Batch $batch..."
        for i in $(seq 1 $batch_size); do
            local request_id=$(((batch - 1) * batch_size + i))
            make_timed_request $request_id &
        done
        wait
    done
    
    # Analyze results
    local successful_requests=$(awk -F',' '$2 == 200 { count++ } END { print count+0 }' load_results.csv)
    local avg_duration=$(awk -F',' 'NR > 1 { sum += $3; count++ } END { if(count > 0) print sum/count; else print 0 }' load_results.csv)
    local max_duration=$(awk -F',' 'NR > 1 { if($3 > max) max = $3 } END { print max+0 }' load_results.csv)
    
    if [ "$successful_requests" -eq "$concurrent_requests" ]; then
        log_test "Load Test Success Rate" "PASS" "$successful_requests/$concurrent_requests requests successful"
    else
        log_test "Load Test Success Rate" "FAIL" "Only $successful_requests/$concurrent_requests requests successful"
    fi
    
    # Performance check (requests should complete within reasonable time)
    local avg_duration_ms=$(echo "$avg_duration * 1000" | bc -l | cut -d. -f1)
    if [ "$avg_duration_ms" -lt 1000 ]; then  # Less than 1 second average
        log_test "Load Test Performance" "PASS" "Average response time: ${avg_duration_ms}ms"
    else
        log_test "Load Test Performance" "FAIL" "Average response time too high: ${avg_duration_ms}ms"
    fi
    
    cd - > /dev/null
}

# Test 5: Error Handling
test_error_handling() {
    echo -e "\n${YELLOW}=== Test 5: Error Handling ===${NC}"
    
    # Create a PHP file with syntax error
    mkdir -p "$PUBLIC_DIR"
    cat > "$PUBLIC_DIR/syntax_error.php" << 'EOF'
<?php
echo "Before error";
this is invalid syntax;
echo "After error";
?>
EOF
    
    # Test script error handling
    local error_response=$(curl -s "$SERVER_URL/syntax_error.php")
    if echo "$error_response" | grep -q "Script Error"; then
        log_test "Script Error Handling" "PASS" "Script errors are properly caught"
    else
        log_test "Script Error Handling" "FAIL" "Script errors not handled properly"
    fi
    
    # Test that errors don't affect other requests
    local normal_response=$(curl -s "$SERVER_URL/isolation-test-complete.php?request_id=error_test")
    if echo "$normal_response" | grep -q "request_id=error_test"; then
        log_test "Error Isolation" "PASS" "Errors don't affect other requests"
    else
        log_test "Error Isolation" "FAIL" "Errors may be affecting other requests"
    fi
    
    # Clean up
    rm -f "$PUBLIC_DIR/syntax_error.php"
}

# Test 6: Content Type Handling
test_content_types() {
    echo -e "\n${YELLOW}=== Test 6: Content Type Handling ===${NC}"
    
    # Create JSON API endpoint
    cat > "$PUBLIC_DIR/api_test.php" << 'EOF'
<?php
$server_context->setHeader('Content-Type', 'application/json');
$server_context->setResponseCode(200);

$data = [
    'status' => 'success',
    'request_id' => $_GET['id'] ?? 'unknown',
    'timestamp' => time()
];

echo json_encode($data);
?>
EOF
    
    # Test JSON response
    local json_response=$(curl -s -H "Accept: application/json" "$SERVER_URL/api_test.php?id=123")
    local content_type=$(curl -s -I "$SERVER_URL/api_test.php" | grep -i "content-type" | tr -d '\r')
    
    if echo "$json_response" | jq . > /dev/null 2>&1; then
        log_test "JSON Response Format" "PASS" "Valid JSON response generated"
    else
        log_test "JSON Response Format" "FAIL" "Invalid JSON response"
    fi
    
    if echo "$content_type" | grep -q "application/json"; then
        log_test "JSON Content Type" "PASS" "Correct content-type header set"
    else
        log_test "JSON Content Type" "FAIL" "Wrong content-type: $content_type"
    fi
    
    # Test custom response code
    cat > "$PUBLIC_DIR/not_found_test.php" << 'EOF'
<?php
$server_context->setResponseCode(404);
$server_context->setHeader('Content-Type', 'application/json');
echo json_encode(['error' => 'Resource not found']);
?>
EOF
    
    local response_code=$(curl -s -w "%{http_code}" "$SERVER_URL/not_found_test.php" -o /dev/null)
    if [ "$response_code" = "404" ]; then
        log_test "Custom Response Code" "PASS" "Custom HTTP response codes work"
    else
        log_test "Custom Response Code" "FAIL" "Expected 404, got $response_code"
    fi
    
    # Clean up
    rm -f "$PUBLIC_DIR/api_test.php" "$PUBLIC_DIR/not_found_test.php"
}

# Test 7: Memory and Performance
test_memory_performance() {
    echo -e "\n${YELLOW}=== Test 7: Memory and Performance ===${NC}"
    
    # Test shared variable persistence
    local initial_counter=$(curl -s "$SERVER_URL/isolation-test-complete.php?request_id=mem1" | grep -o "Shared counter: [0-9]*" | grep -o "[0-9]*")
    local second_counter=$(curl -s "$SERVER_URL/isolation-test-complete.php?request_id=mem2" | grep -o "Shared counter: [0-9]*" | grep -o "[0-9]*")
    
    if [ "$second_counter" -gt "$initial_counter" ]; then
        log_test "Shared Variable Persistence" "PASS" "Shared variables persist across requests"
    else
        log_test "Shared Variable Persistence" "FAIL" "Shared variables not persisting"
    fi
    
    # Performance benchmark
    echo "  Running performance benchmark..."
    local bench_start=$(date +%s.%N)
    
    for i in {1..50}; do
        curl -s "$SERVER_URL/isolation-test-complete.php?request_id=bench$i" > /dev/null &
        if [ $((i % 10)) -eq 0 ]; then
            wait  # Wait every 10 requests to avoid overwhelming
        fi
    done
    wait
    
    local bench_end=$(date +%s.%N)
    local bench_duration=$(echo "$bench_end - $bench_start" | bc -l)
    local requests_per_second=$(echo "scale=2; 50 / $bench_duration" | bc -l)
    
    echo "  Benchmark: 50 requests in ${bench_duration}s (${requests_per_second} req/s)"
    
    # Performance should be reasonable (>10 req/s for simple requests)
    local rps_int=$(echo "$requests_per_second" | cut -d. -f1)
    if [ "$rps_int" -gt 10 ]; then
        log_test "Performance Benchmark" "PASS" "${requests_per_second} requests/second"
    else
        log_test "Performance Benchmark" "FAIL" "Low performance: ${requests_per_second} req/s"
    fi
}

# Generate test report
generate_report() {
    echo -e "\n${BLUE}========================================${NC}"
    echo -e "${BLUE}           TEST SUMMARY REPORT          ${NC}"
    echo -e "${BLUE}========================================${NC}"
    
    echo "Total Tests: $TOTAL_TESTS"
    echo -e "Passed: ${GREEN}$PASSED_TESTS${NC}"
    echo -e "Failed: ${RED}$FAILED_TESTS${NC}"
    
    local success_rate=$((PASSED_TESTS * 100 / TOTAL_TESTS))
    echo "Success Rate: $success_rate%"
    
    if [ $FAILED_TESTS -eq 0 ]; then
        echo -e "\n${GREEN}🎉 All tests passed! The server is working correctly.${NC}"
    else
        echo -e "\n${RED}⚠️  Some tests failed. Check the logs for details.${NC}"
    fi
    
    # Generate detailed report
    cat > "$RESULTS_DIR/test_report.html" << EOF
<!DOCTYPE html>
<html>
<head>
    <title>PHP Fiber Web Server Test Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .pass { color: green; }
        .fail { color: red; }
        .summary { background: #f0f0f0; padding: 10px; border-radius: 5px; }
        .log { font-family: monospace; background: #f8f8f8; padding: 10px; }
    </style>
</head>
<body>
    <h1>PHP Fiber Web Server Test Report</h1>
    <div class="summary">
        <h2>Summary</h2>
        <p>Total Tests: $TOTAL_TESTS</p>
        <p>Passed: <span class="pass">$PASSED_TESTS</span></p>
        <p>Failed: <span class="fail">$FAILED_TESTS</span></p>
        <p>Success Rate: $success_rate%</p>
        <p>Date: $(date)</p>
    </div>
    
    <h2>Detailed Log</h2>
    <div class="log">
        <pre>$(cat "$RESULTS_DIR/test.log")</pre>
    </div>
    
    <h2>Test Files</h2>
    <ul>
        <li><a href="isolation/">Isolation Test Results</a></li>
        <li><a href="load/">Load Test Results</a></li>
        <li><a href="test.log">Full Test Log</a></li>
    </ul>
</body>
</html>
EOF
    
    echo -e "\nDetailed report generated: $RESULTS_DIR/test_report.html"
    echo "Test logs available in: $RESULTS_DIR/"
}

# Main execution
main() {
    echo "Starting comprehensive test suite..."
    echo "Results will be saved to: $RESULTS_DIR"
    
    # Initialize log
    echo "Test Suite Started: $(date)" > "$RESULTS_DIR/test.log"
    
    # Run all tests
    check_server
    test_basic_functionality
    test_request_isolation
    test_session_management
    test_concurrent_load
    test_error_handling
    test_content_types
    test_memory_performance
    
    # Generate final report
    generate_report
    
    # Exit with appropriate code
    if [ $FAILED_TESTS -eq 0 ]; then
        exit 0
    else
        exit 1
    fi
}

# Handle script arguments
case "${1:-all}" in
    "basic")
        check_server && test_basic_functionality
        ;;
    "isolation")
        check_server && test_request_isolation
        ;;
    "session")
        check_server && test_session_management
        ;;
    "load")
        check_server && test_concurrent_load
        ;;
    "error")
        check_server && test_error_handling
        ;;
    "content")
        check_server && test_content_types
        ;;
    "performance")
        check_server && test_memory_performance
        ;;
    "all"|*)
        main
        ;;
esac
