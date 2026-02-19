<?php
/**
 * SSL Configuration Test Script
 * Access this file via your browser to test SSL configuration
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSL Configuration Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .status { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .warning { background-color: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>🔒 SSL Configuration Test</h1>
    
    <?php
    $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    $protocol = $isHttps ? 'HTTPS' : 'HTTP';
    $currentUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    ?>
    
    <div class="status <?php echo $isHttps ? 'success' : 'warning'; ?>">
        <strong>Current Connection:</strong> <?php echo $protocol; ?><br>
        <strong>URL:</strong> <?php echo htmlspecialchars($currentUrl); ?>
    </div>
    
    <?php if (!$isHttps): ?>
    <div class="status error">
        <strong>⚠️ Warning:</strong> You are not using HTTPS. 
        <a href="<?php echo str_replace('http://', 'https://', $currentUrl); ?>">Click here to test HTTPS</a>
    </div>
    <?php else: ?>
    <div class="status success">
        <strong>✅ Success:</strong> SSL/HTTPS is working correctly!
    </div>
    <?php endif; ?>
    
    <h2>Server Information</h2>
    <table>
        <tr><th>Setting</th><th>Value</th></tr>
        <tr><td>Server Software</td><td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></td></tr>
        <tr><td>PHP Version</td><td><?php echo PHP_VERSION; ?></td></tr>
        <tr><td>Laravel Version</td><td><?php 
            $composerLock = json_decode(file_get_contents(__DIR__ . '/composer.lock'), true);
            $laravelVersion = 'Unknown';
            foreach ($composerLock['packages'] as $package) {
                if ($package['name'] === 'laravel/framework') {
                    $laravelVersion = $package['version'];
                    break;
                }
            }
            echo $laravelVersion;
        ?></td></tr>
        <tr><td>Document Root</td><td><?php echo $_SERVER['DOCUMENT_ROOT']; ?></td></tr>
        <tr><td>Server Name</td><td><?php echo $_SERVER['SERVER_NAME']; ?></td></tr>
        <tr><td>Server Port</td><td><?php echo $_SERVER['SERVER_PORT']; ?></td></tr>
    </table>
    
    <h2>SSL/HTTPS Headers</h2>
    <table>
        <tr><th>Header</th><th>Value</th></tr>
        <?php
        $sslHeaders = [
            'HTTPS' => $_SERVER['HTTPS'] ?? 'Not Set',
            'HTTP_X_FORWARDED_PROTO' => $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'Not Set',
            'HTTP_X_FORWARDED_SSL' => $_SERVER['HTTP_X_FORWARDED_SSL'] ?? 'Not Set',
            'SERVER_PORT' => $_SERVER['SERVER_PORT'] ?? 'Not Set',
        ];
        
        foreach ($sslHeaders as $header => $value) {
            echo "<tr><td>{$header}</td><td>" . htmlspecialchars($value) . "</td></tr>";
        }
        ?>
    </table>
    
    <h2>Environment Configuration</h2>
    <?php
    $envFile = __DIR__ . '/.env';
    if (file_exists($envFile)) {
        $envContent = file_get_contents($envFile);
        preg_match('/APP_URL=(.*)/', $envContent, $appUrlMatch);
        preg_match('/APP_ENV=(.*)/', $envContent, $appEnvMatch);
        preg_match('/APP_DEBUG=(.*)/', $envContent, $appDebugMatch);
        
        $appUrl = $appUrlMatch[1] ?? 'Not Set';
        $appEnv = $appEnvMatch[1] ?? 'Not Set';
        $appDebug = $appDebugMatch[1] ?? 'Not Set';
        
        echo "<table>";
        echo "<tr><th>Setting</th><th>Value</th></tr>";
        echo "<tr><td>APP_URL</td><td>" . htmlspecialchars($appUrl) . "</td></tr>";
        echo "<tr><td>APP_ENV</td><td>" . htmlspecialchars($appEnv) . "</td></tr>";
        echo "<tr><td>APP_DEBUG</td><td>" . htmlspecialchars($appDebug) . "</td></tr>";
        echo "</table>";
        
        if (strpos($appUrl, 'https://') === 0 && $isHttps) {
            echo '<div class="status success"><strong>✅ Configuration Match:</strong> APP_URL matches current HTTPS connection</div>';
        } elseif (strpos($appUrl, 'http://') === 0 && !$isHttps) {
            echo '<div class="status info"><strong>ℹ️ Development Mode:</strong> APP_URL matches current HTTP connection</div>';
        } else {
            echo '<div class="status warning"><strong>⚠️ Configuration Mismatch:</strong> APP_URL does not match current connection protocol</div>';
        }
    } else {
        echo '<div class="status error"><strong>❌ Error:</strong> .env file not found</div>';
    }
    ?>
    
    <h2>Security Headers Test</h2>
    <div id="security-headers">
        <p>Testing security headers...</p>
        <script>
            // Test if security headers are present
            fetch(window.location.href, {method: 'HEAD'})
                .then(response => {
                    const headers = [
                        'strict-transport-security',
                        'x-content-type-options',
                        'x-frame-options',
                        'x-xss-protection',
                        'referrer-policy'
                    ];
                    
                    let html = '<table><tr><th>Security Header</th><th>Status</th></tr>';
                    headers.forEach(header => {
                        const value = response.headers.get(header);
                        const status = value ? '✅ Present' : '❌ Missing';
                        html += `<tr><td>${header}</td><td>${status}</td></tr>`;
                    });
                    html += '</table>';
                    
                    document.getElementById('security-headers').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('security-headers').innerHTML = '<div class="status error">Error testing headers: ' + error.message + '</div>';
                });
        </script>
    </div>
    
    <h2>Next Steps</h2>
    <div class="status info">
        <strong>For Local Development (Laragon):</strong>
        <ol>
            <li>Enable SSL in Laragon Control Panel</li>
            <li>Access your site via https://kaninichapchap.test</li>
        </ol>
        
        <strong>For Production Deployment:</strong>
        <ol>
            <li>Run: <code>deploy-ssl.bat production</code> (Windows) or <code>./deploy-ssl.sh production</code> (Linux)</li>
            <li>Install SSL certificate (see SSL_SETUP_GUIDE.md)</li>
            <li>Test your site and verify all resources load over HTTPS</li>
        </ol>
    </div>
    
    <p><small>Generated at: <?php echo date('Y-m-d H:i:s'); ?></small></p>
</body>
</html>
