<?php

/**
 * Performance monitoring and shared variables demo
 */

// Track request start time
$request_start = microtime(true);

// Initialize performance tracking
if (!isset($shared_variables['performance'])) {
    $shared_variables['performance'] = [
        'total_requests' => 0,
        'total_time' => 0,
        'max_time' => 0,
        'min_time' => PHP_FLOAT_MAX,
        'recent_requests' => []
    ];
}

?>
<!DOCTYPE html>
<html>

<head>
    <title>Performance Monitor</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        .metric {
            background: #f8f9fa;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
        }

        .metric h3 {
            margin-top: 0;
            color: #007bff;
        }

        .recent-list {
            max-height: 200px;
            overflow-y: auto;
        }

        .refresh-btn {
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }

        .clear-btn {
            background: #dc3545;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
    </style>
    <script>
        function autoRefresh() {
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        }

        function startAutoRefresh() {
            autoRefresh();
            setInterval(autoRefresh, 5000);
        }
    </script>
</head>

<body>
    <h1>📊 Performance Monitor</h1>

    <?php
    // Simulate some processing time
    $processing_time = rand(10, 100) * 1000; // 10-100ms in microseconds
    usleep($processing_time);

    // Calculate request time
    $request_time = microtime(true) - $request_start;

    // Update performance metrics
    $perf = &$shared_variables['performance'];
    $perf['total_requests']++;
    $perf['total_time'] += $request_time;
    $perf['max_time'] = max($perf['max_time'], $request_time);
    $perf['min_time'] = min($perf['min_time'], $request_time);

    // Store recent request info
    $perf['recent_requests'][] = [
        'timestamp' => date('H:i:s'),
        'time' => $request_time,
        'request_id' => $_GET['id'] ?? uniqid()
    ];

    // Keep only last 20 requests
    if (count($perf['recent_requests']) > 20) {
        $perf['recent_requests'] = array_slice($perf['recent_requests'], -20);
    }

    // Calculate averages
    $avg_time = $perf['total_requests'] > 0 ? $perf['total_time'] / $perf['total_requests'] : 0;
    $requests_per_second = $perf['total_requests'] / (time() - ($_SERVER['REQUEST_TIME'] ?? time()));

    // Handle actions
    if (($_POST['action'] ?? null) === 'reset') {
        $shared_variables['performance'] = [
            'total_requests' => 0,
            'total_time' => 0,
            'max_time' => 0,
            'min_time' => PHP_FLOAT_MAX,
            'recent_requests' => []
        ];
        echo '<div style="color: green; padding: 10px; background: #d4edda; border-radius: 3px; margin-bottom: 15px;">✅ Statistics reset!</div>';
    }
    ?>

    <div class="metric">
        <h3>📈 Overall Statistics</h3>
        <p><strong>Total Requests:</strong> <?= number_format($perf['total_requests']) ?></p>
        <p><strong>Average Response Time:</strong> <?= number_format($avg_time * 1000, 2) ?>ms</p>
        <p><strong>Fastest Request:</strong> <?= number_format($perf['min_time'] * 1000, 2) ?>ms</p>
        <p><strong>Slowest Request:</strong> <?= number_format($perf['max_time'] * 1000, 2) ?>ms</p>
        <p><strong>Estimated RPS:</strong> <?= number_format($requests_per_second, 2) ?> requests/second</p>
    </div>

    <div class="metric">
        <h3>⚡ Current Request</h3>
        <p><strong>Request ID:</strong> <?= htmlspecialchars($_GET['id'] ?? 'auto-generated') ?></p>
        <p><strong>Processing Time:</strong> <?= number_format($request_time * 1000, 2) ?>ms</p>
        <p><strong>Timestamp:</strong> <?= date('Y-m-d H:i:s') ?></p>
        <p><strong>Simulated Delay:</strong> <?= number_format($processing_time / 1000, 1) ?>ms</p>
    </div>

    <div class="metric">
        <h3>🕒 Recent Requests</h3>
        <div class="recent-list">
            <?php if (empty($perf['recent_requests'])): ?>
                <p>No recent requests recorded.</p>
            <?php else: ?>
                <?php foreach (array_reverse($perf['recent_requests']) as $request): ?>
                    <div style="padding: 5px; border-bottom: 1px solid #eee;">
                        <?= htmlspecialchars($request['timestamp']) ?> -
                        ID: <?= htmlspecialchars($request['request_id']) ?> -
                        <?= number_format($request['time'] * 1000, 2) ?>ms
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="metric">
        <h3>🔧 Server Information</h3>
        <p><strong>PHP Version:</strong> <?= PHP_VERSION ?></p>
        <p><strong>Fiber Support:</strong> <?= class_exists('Fiber') ? '✅ Available' : '❌ Not Available' ?></p>
        <p><strong>Memory Usage:</strong> <?= number_format(memory_get_usage() / 1024 / 1024, 2) ?>MB</p>
        <p><strong>Peak Memory:</strong> <?= number_format(memory_get_peak_usage() / 1024 / 1024, 2) ?>MB</p>
    </div>

    <div style="margin-top: 20px;">
        <button class="refresh-btn" onclick="window.location.reload()">🔄 Refresh</button>
        <button class="refresh-btn" onclick="startAutoRefresh()">🔄 Auto Refresh</button>

        <form method="post" style="display: inline;">
            <input type="hidden" name="action" value="reset">
            <button type="submit" class="clear-btn" onclick="return confirm('Reset all statistics?')">🗑️ Reset Stats</button>
        </form>
    </div>

    <hr>
    <h2>🧪 Load Testing</h2>
    <p>Use these links to test concurrent performance:</p>
    <ul>
        <li><a href="?id=test1" target="_blank">Test Request 1</a></li>
        <li><a href="?id=test2" target="_blank">Test Request 2</a></li>
        <li><a href="?id=test3" target="_blank">Test Request 3</a></li>
        <li><a href="?id=test4" target="_blank">Test Request 4</a></li>
        <li><a href="?id=test5" target="_blank">Test Request 5</a></li>
    </ul>

    <p><strong>Command line load test:</strong></p>
    <code style="background: #f8f8f8; padding: 10px; display: block;">
        for i in {1..10}; do curl "http://127.0.0.1:8001/performance-monitor.php?id=load_$i" &gt; /dev/null &amp; done; wait
    </code>
</body>

</html>