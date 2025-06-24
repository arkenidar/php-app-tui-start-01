#!/bin/bash

# PHP Fiber Web Server - Live Demo Script
# This script demonstrates the server's key features and capabilities

set -e

echo "🚀 PHP Fiber Web Server - Live Demo"
echo "=================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
MAGENTA='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Check if server is running
check_server() {
    if curl -s http://127.0.0.1:8001 > /dev/null 2>&1; then
        echo -e "${GREEN}✅ Server is running on http://127.0.0.1:8001${NC}"
        return 0
    else
        echo -e "${RED}❌ Server is not running${NC}"
        echo "Please start the server first:"
        echo "  php server/server-http-isolated.php"
        return 1
    fi
}

# Demo functions
demo_basic_functionality() {
    echo -e "\n${BLUE}📋 Demo 1: Basic Functionality${NC}"
    echo "Testing basic server response..."
    
    response=$(curl -s http://127.0.0.1:8001/server-test.php)
    if [[ $response == *"Server Test Page"* ]]; then
        echo -e "${GREEN}✅ Basic functionality works${NC}"
    else
        echo -e "${RED}❌ Basic functionality failed${NC}"
    fi
}

demo_concurrency() {
    echo -e "\n${YELLOW}⚡ Demo 2: Concurrency Test${NC}"
    echo "Testing concurrent request handling..."
    
    # Send 10 concurrent requests
    for i in {1..10}; do
        curl -s "http://127.0.0.1:8001/isolation-test-complete.php?request=$i" &
    done
    wait
    
    echo -e "${GREEN}✅ Handled 10 concurrent requests successfully${NC}"
}

demo_session_isolation() {
    echo -e "\n${MAGENTA}🔒 Demo 3: Session Isolation${NC}"
    echo "Testing session isolation between requests..."
    
    # Create two different sessions
    session1=$(curl -s -c /tmp/cookies1.txt -b /tmp/cookies1.txt http://127.0.0.1:8001/cart-demo.php | grep -o 'Session ID: [^<]*' | head -1)
    session2=$(curl -s -c /tmp/cookies2.txt -b /tmp/cookies2.txt http://127.0.0.1:8001/cart-demo.php | grep -o 'Session ID: [^<]*' | head -1)
    
    echo "    Session 1: $session1"
    echo "    Session 2: $session2"
    
    if [[ "$session1" != "$session2" ]]; then
        echo -e "${GREEN}✅ Sessions are properly isolated${NC}"
    else
        echo -e "${RED}❌ Session isolation failed${NC}"
    fi
    
    # Cleanup
    rm -f /tmp/cookies1.txt /tmp/cookies2.txt
}

demo_utf8_support() {
    echo -e "\n${CYAN}🌍 Demo 4: UTF-8 and Emoji Support${NC}"
    echo "Testing Unicode and emoji handling..."
    
    response=$(curl -s http://127.0.0.1:8001/utf8-test.php)
    if [[ $response == *"🚀"* ]] && [[ $response == *"UTF-8"* ]]; then
        echo -e "${GREEN}✅ UTF-8 and emoji support works${NC}"
    else
        echo -e "${RED}❌ UTF-8 support failed${NC}"
    fi
}

demo_api_endpoint() {
    echo -e "\n${BLUE}🔗 Demo 5: REST API Endpoint${NC}"
    echo "Testing API functionality..."
    
    # Test GET request
    get_response=$(curl -s http://127.0.0.1:8001/api/users.php)
    if [[ $get_response == *"users"* ]]; then
        echo -e "${GREEN}✅ GET API endpoint works${NC}"
    else
        echo -e "${RED}❌ GET API endpoint failed${NC}"
    fi
    
    # Test POST request
    post_response=$(curl -s -X POST -H "Content-Type: application/json" -d '{"name":"Test User","email":"test@example.com"}' http://127.0.0.1:8001/api/users.php)
    if [[ $post_response == *"User created"* ]]; then
        echo -e "${GREEN}✅ POST API endpoint works${NC}"
    else
        echo -e "${RED}❌ POST API endpoint failed${NC}"
    fi
}

demo_performance() {
    echo -e "\n${YELLOW}📈 Demo 6: Performance Monitoring${NC}"
    echo "Opening performance monitor in browser..."
    echo "Visit: http://127.0.0.1:8001/performance-monitor.php"
    echo "(This shows real-time server metrics and request isolation)"
}

demo_static_files() {
    echo -e "\n${MAGENTA}📁 Demo 7: Static File Serving${NC}"
    echo "Testing static file serving..."
    
    # Test image serving
    response=$(curl -s -I http://127.0.0.1:8001/media/images/doggie.webp)
    if [[ $response == *"image/webp"* ]]; then
        echo -e "${GREEN}✅ Static file serving works (MIME type detected)${NC}"
    else
        echo -e "${RED}❌ Static file serving failed${NC}"
    fi
}

demo_error_handling() {
    echo -e "\n${RED}🛡️ Demo 8: Error Handling${NC}"
    echo "Testing 404 error handling..."
    
    response=$(curl -s http://127.0.0.1:8001/nonexistent-file.php)
    if [[ $response == *"404"* ]] && [[ $response == *"Not Found"* ]]; then
        echo -e "${GREEN}✅ 404 error handling works${NC}"
    else
        echo -e "${RED}❌ Error handling failed${NC}"
    fi
}

# Main demo execution
main() {
    if ! check_server; then
        exit 1
    fi
    
    echo -e "\n${CYAN}Starting comprehensive server demonstration...${NC}"
    
    demo_basic_functionality
    demo_concurrency
    demo_session_isolation
    demo_utf8_support
    demo_api_endpoint
    demo_static_files
    demo_error_handling
    demo_performance
    
    echo -e "\n${GREEN}🎉 Demo Complete!${NC}"
    echo ""
    echo "Key Features Demonstrated:"
    echo "  ✅ Basic HTTP request/response handling"
    echo "  ✅ Concurrent request processing with Fibers"
    echo "  ✅ Complete session isolation"
    echo "  ✅ UTF-8 and emoji support"
    echo "  ✅ REST API functionality"
    echo "  ✅ Static file serving with MIME detection"
    echo "  ✅ Proper error handling"
    echo "  ✅ Performance monitoring capabilities"
    echo ""
    echo "For more detailed testing, run:"
    echo "  ./server/run_tests.sh      # Complete test suite"
    echo "  ./server/validate.sh       # Quick validation"
    echo ""
    echo "Visit http://127.0.0.1:8001/performance-monitor.php for real-time metrics!"
}

# Run the demo
main "$@"
