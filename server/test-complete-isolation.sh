#!/bin/bash

# Advanced load test for complete isolation verification
# This script will create multiple concurrent requests and analyze the responses

echo "🚀 Advanced Isolation Load Test"
echo "================================"
echo ""

# Configuration
SERVER_URL="http://127.0.0.1:8001"  # Isolated server port
TEST_FILE="isolation-test-complete.php"
NUM_REQUESTS=15
CONCURRENT_BATCH=5

echo "Server: $SERVER_URL"
echo "Test file: $TEST_FILE"
echo "Total requests: $NUM_REQUESTS"
echo "Concurrent batch size: $CONCURRENT_BATCH"
echo ""

# Clean up previous test results
rm -f response_*.html test_results.txt

echo "Starting load test..."

# Function to make a request
make_request() {
    local id=$1
    local param=$2
    local batch=$3
    
    echo "Making request $id (batch $batch) with param=$param"
    
    curl -s -w "Request $id: HTTP %{http_code}, Time: %{time_total}s\n" \
         "$SERVER_URL/$TEST_FILE?request_id=$id&param=$param&batch=$batch" \
         > "response_$id.html" \
         2>> curl_timing.log &
}

# Make requests in batches to test concurrency
batch=1
request_id=1

while [ $request_id -le $NUM_REQUESTS ]; do
    echo ""
    echo "🔥 Starting batch $batch (requests $request_id to $((request_id + CONCURRENT_BATCH - 1)))"
    
    # Start concurrent requests in this batch
    for i in $(seq 0 $((CONCURRENT_BATCH - 1))); do
        if [ $request_id -le $NUM_REQUESTS ]; then
            make_request $request_id $((request_id * 100)) $batch
            request_id=$((request_id + 1))
        fi
    done
    
    # Wait for this batch to complete before starting next
    wait
    echo "✅ Batch $batch completed"
    
    batch=$((batch + 1))
    sleep 0.5  # Small delay between batches
done

echo ""
echo "⏳ All requests completed. Analyzing results..."
echo ""

# Analyze results
echo "🔍 ISOLATION ANALYSIS" > test_results.txt
echo "===================" >> test_results.txt
echo "" >> test_results.txt

isolation_errors=0
total_responses=0

for i in $(seq 1 $NUM_REQUESTS); do
    if [ -f "response_$i.html" ]; then
        total_responses=$((total_responses + 1))
        
        echo "--- Response $i Analysis ---" >> test_results.txt
        
        # Extract key information
        request_id_found=$(grep -o "Request ID:</strong> [^<]*" "response_$i.html" | head -1 | sed 's/.*> //')
        param_found=$(grep -o "Request Param:</strong> [^<]*" "response_$i.html" | head -1 | sed 's/.*> //')
        random_number=$(grep -o "Random number for this request:.*[0-9]\{5\}" "response_$i.html" | grep -o "[0-9]\{5\}")
        session_random=$(grep -o "Random number stored in session:.*[0-9]\{5\}" "response_$i.html" | grep -o "[0-9]\{5\}")
        
        echo "Expected: Request ID=$i, Param=$((i * 100))" >> test_results.txt
        echo "Found: Request ID=$request_id_found, Param=$param_found" >> test_results.txt
        echo "Random numbers: Generated=$random_number, Session=$session_random" >> test_results.txt
        
        # Check for isolation violations
        if [ "$request_id_found" != "$i" ]; then
            echo "❌ ISOLATION ERROR: Wrong request ID!" >> test_results.txt
            isolation_errors=$((isolation_errors + 1))
        fi
        
        if [ "$param_found" != "$((i * 100))" ]; then
            echo "❌ ISOLATION ERROR: Wrong parameter!" >> test_results.txt
            isolation_errors=$((isolation_errors + 1))
        fi
        
        if [ "$random_number" != "$session_random" ]; then
            echo "❌ ISOLATION ERROR: Session random number mismatch!" >> test_results.txt
            isolation_errors=$((isolation_errors + 1))
        fi
        
        # Check for cross-contamination
        other_request_ids=$(grep -o "Request ID:</strong> [^<]*" "response_$i.html" | sed 's/.*> //' | grep -v "^$i$" | wc -l)
        if [ "$other_request_ids" -gt 0 ]; then
            echo "❌ CROSS-CONTAMINATION: Found other request IDs in response!" >> test_results.txt
            isolation_errors=$((isolation_errors + 1))
        fi
        
        if [ $isolation_errors -eq 0 ]; then
            echo "✅ Response $i: Isolation OK" >> test_results.txt
        fi
        
        echo "" >> test_results.txt
    fi
done

# Final summary
echo "📊 FINAL RESULTS" >> test_results.txt
echo "===============" >> test_results.txt
echo "Total responses: $total_responses" >> test_results.txt
echo "Isolation errors: $isolation_errors" >> test_results.txt

if [ $isolation_errors -eq 0 ]; then
    echo "🎉 SUCCESS: All responses are properly isolated!" >> test_results.txt
else
    echo "⚠️  WARNING: Found $isolation_errors isolation violations!" >> test_results.txt
fi

# Display results
cat test_results.txt

echo ""
echo "📁 Files generated:"
echo "  - response_*.html (individual responses)"
echo "  - test_results.txt (analysis results)"
echo "  - curl_timing.log (timing information)"
echo ""

if [ $isolation_errors -eq 0 ]; then
    echo "🎉 All tests passed! Response isolation is working correctly."
else
    echo "⚠️  Some tests failed. Check test_results.txt for details."
fi
