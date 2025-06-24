#!/bin/bash

# Load test script to demonstrate response isolation issues
# Run this while your server is running to test concurrent requests

echo "Testing response isolation with concurrent requests..."
echo "Make sure your server is running first!"
echo ""

# Function to make a request
make_request() {
    local id=$1
    local param=$2
    echo "Making request $id with param=$param"
    curl -s "http://127.0.0.1:8000/test-isolation.php?request_id=$id&param=$param" > "response_$id.html" &
}

# Make 10 concurrent requests
for i in {1..10}; do
    make_request $i $((i * 100))
done

# Wait for all requests to complete
wait

echo "All requests completed. Check response_*.html files for isolation issues."
echo ""
echo "Look for:"
echo "1. Mixed request IDs in responses"
echo "2. Incorrect GET parameters showing up in wrong responses"
echo "3. Session data bleeding between requests"
echo ""

# Show a summary
echo "=== RESPONSE SUMMARY ==="
for i in {1..10}; do
    if [ -f "response_$i.html" ]; then
        echo "Response $i:"
        grep "Request ID\|GET params\|param=" "response_$i.html" | head -3
        echo ""
    fi
done
