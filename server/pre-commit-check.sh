#!/bin/bash

# Pre-commit validation script
# Run this before committing to ensure everything is working

echo "🔍 Pre-Commit Validation"
echo "========================"

cd "$(dirname "$0")"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

SUCCESS=0

# Function to print status
print_status() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✅ $2${NC}"
    else
        echo -e "${RED}❌ $2${NC}"
        SUCCESS=1
    fi
}

# Check PHP version and Fiber support
echo "📋 Checking PHP requirements..."
php -v | head -1
if php -r "exit(class_exists('Fiber') ? 0 : 1);"; then
    print_status 0 "PHP Fiber support available"
else
    print_status 1 "PHP Fiber support missing - requires PHP 8.1+"
fi

# Check file syntax
echo ""
echo "📄 Checking PHP syntax..."
find . -name "*.php" -print0 | while IFS= read -r -d '' file; do
    if php -l "$file" > /dev/null 2>&1; then
        echo -e "${GREEN}✅${NC} $file"
    else
        echo -e "${RED}❌${NC} $file - Syntax error"
        SUCCESS=1
    fi
done

# Check UTF-8 encoding
echo ""
echo "🌐 Checking UTF-8 encoding..."
if file -bi public/server-test.php | grep -q "charset=utf-8"; then
    print_status 0 "server-test.php uses UTF-8 encoding"
else
    print_status 1 "server-test.php encoding issue"
fi

if file -bi public/utf8-post-test.php | grep -q "charset=utf-8"; then
    print_status 0 "utf8-post-test.php uses UTF-8 encoding"
else
    print_status 1 "utf8-post-test.php encoding issue"
fi

# Test server startup (quick test)
echo ""
echo "🚀 Testing server startup..."
timeout 5s php -dxdebug.mode=off server-http-isolated.php > /tmp/server_test.log 2>&1 &
SERVER_PID=$!
sleep 2

if kill -0 $SERVER_PID 2>/dev/null; then
    print_status 0 "Server starts successfully"
    kill $SERVER_PID 2>/dev/null
else
    print_status 1 "Server startup failed"
    cat /tmp/server_test.log
fi

# Check required files exist
echo ""
echo "📁 Checking required files..."
required_files=(
    "server-http-isolated.php"
    "public/server-test.php"
    "public/utf8-post-test.php"
    "public/utf8-test.php"
    "README.md"
    "docs/DEVELOPER_GUIDE.md"
    "docs/API_REFERENCE.md"
    "docs/TESTING.md"
)

for file in "${required_files[@]}"; do
    if [ -f "$file" ]; then
        print_status 0 "$file exists"
    else
        print_status 1 "$file missing"
    fi
done

# Check documentation files are not empty
echo ""
echo "📖 Checking documentation..."
for doc in README.md docs/*.md; do
    if [ -f "$doc" ] && [ -s "$doc" ]; then
        print_status 0 "$doc is not empty"
    else
        print_status 1 "$doc is missing or empty"
    fi
done

# Summary
echo ""
echo "📋 Validation Summary"
echo "===================="
if [ $SUCCESS -eq 0 ]; then
    echo -e "${GREEN}🎉 All checks passed! Ready to commit.${NC}"
    exit 0
else
    echo -e "${RED}❌ Some checks failed. Please fix issues before committing.${NC}"
    exit 1
fi
