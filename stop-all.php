<?php
// Stop All Node.js Processes Script
header('Content-Type: text/html; charset=utf-8');

// Security check
$password = 'stop123'; // Change this password!
$provided_password = $_GET['password'] ?? '';

if ($provided_password !== $password) {
    die('<h1>Access Denied</h1><p>Usage: stop-all.php?password=stop123</p>');
}

echo '<!DOCTYPE html>
<html>
<head>
    <title>Stop All Node.js Processes</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .output { background: #f0f0f0; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #333; }
        .success { background: #e8f5e8; color: #2e7d32; border-left-color: #4caf50; }
        .error { background: #ffebee; color: #c62828; border-left-color: #f44336; }
        .warning { background: #fff3e0; color: #f57c00; border-left-color: #ff9800; }
        .command { background: #263238; color: #fff; padding: 10px; border-radius: 4px; font-family: monospace; margin: 5px 0; }
        h1 { color: #333; border-bottom: 2px solid #333; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; }
        .step { background: #e3f2fd; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #2196f3; }
    </style>
</head>
<body>';

echo '<div class="container">';
echo '<h1>🛑 Stop All Node.js Processes</h1>';

// Function to execute command and return output
function executeCommand($command) {
    $output = shell_exec($command . ' 2>&1');
    return $output ? trim($output) : 'No output';
}

// Step 1: Show current processes
echo '<h2>📋 Step 1: Current Node.js Processes</h2>';
echo '<div class="output">';
$processes = executeCommand('ps aux | grep node');
if (strpos($processes, 'node') !== false && strpos($processes, 'grep') === false) {
    echo '<h3>Found Node.js processes:</h3>';
    echo '<pre>' . htmlspecialchars($processes) . '</pre>';
} else {
    echo '<p class="success">✅ No Node.js processes currently running.</p>';
}
echo '</div>';

// Step 2: Kill all Node.js processes
echo '<h2>🔪 Step 2: Killing All Node.js Processes</h2>';
echo '<div class="output">';

$killCommands = [
    'pkill -f "node.*server"' => 'Kill server processes',
    'pkill -f "node.*app"' => 'Kill app processes',
    'pkill -f "node.*training"' => 'Kill training processes',
    'pkill -f "npm.*start"' => 'Kill npm start processes',
    'pkill -TERM node' => 'Graceful termination',
    'sleep 3' => 'Wait 3 seconds',
    'pkill -9 node' => 'Force kill remaining processes'
];

foreach ($killCommands as $command => $description) {
    echo "<div class='command'>$description: $command</div>";
    $result = executeCommand($command);
    
    if ($command === 'sleep 3') {
        echo '<p class="warning">⏳ Waiting for graceful shutdown...</p>';
        sleep(3);
    } else {
        echo '<p class="success">✅ Command executed</p>';
    }
}
echo '</div>';

// Step 3: Verify cleanup
echo '<h2>✅ Step 3: Verification</h2>';
echo '<div class="output">';
$processesAfter = executeCommand('ps aux | grep node');
if (strpos($processesAfter, 'node') !== false && strpos($processesAfter, 'grep') === false) {
    echo '<p class="warning">⚠️ Some Node.js processes may still be running:</p>';
    echo '<pre>' . htmlspecialchars($processesAfter) . '</pre>';
} else {
    echo '<p class="success">✅ All Node.js processes have been stopped!</p>';
}
echo '</div>';

// Step 4: Check for locked files
echo '<h2>🔒 Step 4: Check for Locked Files</h2>';
echo '<div class="output">';
$lockedFiles = executeCommand('lsof | grep training 2>/dev/null');
if ($lockedFiles && $lockedFiles !== 'No output') {
    echo '<p class="warning">⚠️ Some files may still be locked:</p>';
    echo '<pre>' . htmlspecialchars($lockedFiles) . '</pre>';
} else {
    echo '<p class="success">✅ No locked files found.</p>';
}
echo '</div>';

// Step 5: Instructions
echo '<h2>📝 Step 5: Next Steps for Your Hosting</h2>';
echo '<div class="step">';
echo '<h3>In Your Hosting Control Panel:</h3>';
echo '<ol>';
echo '<li><strong>Go to Node.js Applications</strong></li>';
echo '<li><strong>Find your training app</strong></li>';
echo '<li><strong>Click "Stop" or "Delete"</strong> the application</li>';
echo '<li><strong>Wait 30 seconds</strong> for complete shutdown</li>';
echo '</ol>';
echo '</div>';

echo '<div class="step">';
echo '<h3>Clean Upload for PHP Version:</h3>';
echo '<ol>';
echo '<li><strong>Delete ALL files</strong> from your hosting directory</li>';
echo '<li><strong>Upload only these 3 PHP files:</strong>';
echo '<ul>';
echo '<li>index.php</li>';
echo '<li>api.php</li>';
echo '<li>app.js</li>';
echo '</ul>';
echo '</li>';
echo '<li><strong>Set folder permissions</strong> to 755</li>';
echo '<li><strong>Visit your URL</strong> (no Node.js setup needed)</li>';
echo '</ol>';
echo '</div>';

// Step 6: File cleanup commands
echo '<h2>🧹 Step 6: Cleanup Commands (if you have SSH access)</h2>';
echo '<div class="output">';
echo '<p>If you have SSH access to your hosting, run these commands:</p>';
echo '<div class="command">cd /path/to/your/app</div>';
echo '<div class="command">rm -f package.json package-lock.json server.js</div>';
echo '<div class="command">rm -rf node_modules</div>';
echo '<div class="command">rm -f *.log</div>';
echo '<div class="command">ls -la</div>';
echo '</div>';

// Refresh option
echo '<div class="output">';
echo '<p><a href="?password=' . $password . '" style="background: #333; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">🔄 Refresh Status</a></p>';
echo '</div>';

echo '</div>'; // Close container
echo '</body></html>';
?>
