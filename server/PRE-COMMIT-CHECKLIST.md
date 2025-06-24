# Pre-Commit Checklist

Before committing your PHP Fiber web server code, verify the following:

## ✅ Code Quality Checks

### 1. PHP Syntax Validation

```bash
cd server
php -l server-http-isolated.php
find public -name "*.php" -exec php -l {} \;
```

### 2. UTF-8 Encoding Check

```bash
file -bi public/server-test.php     # Should show: charset=utf-8
file -bi public/utf8-post-test.php  # Should show: charset=utf-8
```

### 3. Server Functionality Test

```bash
# Start server (in background)
php -dxdebug.mode=off server-http-isolated.php &
SERVER_PID=$!

# Wait a moment for startup
sleep 2

# Test basic functionality
curl -s "http://localhost:8001/server-test.php" | grep -q "✅" && echo "✅ Server working" || echo "❌ Server not responding"

# Test UTF-8 support
curl -s "http://localhost:8001/utf8-post-test.php" | grep -q "🌐" && echo "✅ UTF-8 emojis working" || echo "❌ UTF-8 issues"

# Clean up
kill $SERVER_PID 2>/dev/null
```

## ✅ Required Files Present

- [x] `server-http-isolated.php` - Main server file
- [x] `public/server-test.php` - Basic functionality test
- [x] `public/utf8-post-test.php` - UTF-8 encoding test
- [x] `public/utf8-test.php` - Comprehensive UTF-8 demo
- [x] `README.md` - Documentation
- [x] `docs/DEVELOPER_GUIDE.md` - Developer guide
- [x] `docs/API_REFERENCE.md` - API reference
- [x] `docs/TESTING.md` - Testing guide

## ✅ Key Features Working

- [x] **Request Isolation**: Each request has isolated superglobals and output
- [x] **UTF-8 Support**: Emojis and international characters display correctly
- [x] **Session Management**: Sessions work properly with isolation
- [x] **Error Handling**: 404/500 errors display with UTF-8 support
- [x] **Fiber Concurrency**: Multiple concurrent requests handled properly

## ✅ Git Preparation

1. **Check status**: `git status`
2. **Add changes**: `git add .` (or specific files)
3. **Review changes**: `git diff --cached`
4. **Commit with message**: `git commit -m "Implement PHP Fiber web server with UTF-8 support and request isolation"`

## 🚀 Quick Validation Commands

```bash
# One-liner to check everything
cd server && echo "Checking syntax..." && php -l server-http-isolated.php && echo "Starting server..." && timeout 5s php -dxdebug.mode=off server-http-isolated.php > /dev/null 2>&1 & sleep 2 && curl -s http://localhost:8001/server-test.php | grep -q "✅" && echo "✅ All good!" || echo "❌ Issues found"
```

## 📝 Suggested Commit Message

```
feat: Add PHP Fiber web server with proper isolation and UTF-8 support

- Implement request/response isolation using PHP Fibers
- Add comprehensive UTF-8 and emoji support
- Create test pages for functionality validation
- Include developer documentation and API reference
- Add session management with proper isolation
- Ensure error pages support UTF-8 encoding
```
