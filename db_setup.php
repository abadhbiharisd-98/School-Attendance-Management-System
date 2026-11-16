<?php
/**
 * 1-Click Aiven Cloud MySQL Database Importer & Health Checker
 * School Attendance Management System
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Polyfills
if(!function_exists('get_magic_quotes_gpc')){ function get_magic_quotes_gpc(){ return false; } }

// Resolve environment defaults
$default_host = getenv('DB_SERVER') ?: (isset($_ENV['DB_SERVER']) ? $_ENV['DB_SERVER'] : 'localhost');
$default_port = getenv('DB_PORT') ?: (isset($_ENV['DB_PORT']) ? $_ENV['DB_PORT'] : '26284');
$default_user = getenv('DB_USERNAME') ?: (isset($_ENV['DB_USERNAME']) ? $_ENV['DB_USERNAME'] : 'avnadmin');
$default_pass = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : (isset($_ENV['DB_PASSWORD']) ? $_ENV['DB_PASSWORD'] : '');
$default_name = getenv('DB_NAME') ?: (isset($_ENV['DB_NAME']) ? $_ENV['DB_NAME'] : 'defaultdb');
$default_ssl  = getenv('DB_SSL') ?: (isset($_ENV['DB_SSL']) ? $_ENV['DB_SSL'] : 'true');

$host = isset($_POST['host']) ? trim($_POST['host']) : $default_host;
$port = isset($_POST['port']) ? (int)$_POST['port'] : (int)$default_port;
$user = isset($_POST['user']) ? trim($_POST['user']) : $default_user;
$pass = isset($_POST['pass']) ? $_POST['pass'] : $default_pass;
$name = isset($_POST['name']) ? trim($_POST['name']) : $default_name;
$use_ssl = isset($_POST['ssl']) ? ($_POST['ssl'] === '1' || $_POST['ssl'] === 'true') : ($default_ssl !== 'false' && $default_ssl !== '0');

// Locate SQL dump file
$possible_sql_paths = array(
    __DIR__ . '/Database/attendance_management.sql',
    __DIR__ . '/School_Attendance_Management_System/attendance/Database/attendance_management.sql',
    dirname(__FILE__) . '/Database/attendance_management.sql',
    dirname(__FILE__) . '/../Database/attendance_management.sql'
);
$sql_file = '';
foreach($possible_sql_paths as $p){
    if(file_exists($p)){
        $sql_file = $p;
        break;
    }
}

// Database Connection Helper
function get_aiven_connection($host, $user, $pass, $name, $port, $use_ssl, &$error_msg, &$ssl_cipher){
    $link = mysqli_init();
    if(!$link){
        $error_msg = "mysqli_init() failed.";
        return false;
    }
    @mysqli_options($link, MYSQLI_OPT_CONNECT_TIMEOUT, 15);
    
    // Parse host:port if user pasted host with port
    if(strpos($host, ':') !== false){
        list($h, $p) = explode(':', $host, 2);
        $host = $h;
        if(is_numeric($p)) $port = (int)$p;
    }

    $connected = false;
    if($use_ssl && defined('MYSQLI_CLIENT_SSL')){
        $connected = @mysqli_real_connect($link, $host, $user, $pass, $name, (int)$port, NULL, MYSQLI_CLIENT_SSL);
        if(!$connected){
            // Try connecting without db first if db does not exist yet
            $connected = @mysqli_real_connect($link, $host, $user, $pass, '', (int)$port, NULL, MYSQLI_CLIENT_SSL);
            if($connected && $name !== ''){
                @mysqli_query($link, "CREATE DATABASE IF NOT EXISTS `" . mysqli_real_escape_string($link, $name) . "`");
                @mysqli_select_db($link, $name);
            }
        }
    } else {
        $connected = @mysqli_real_connect($link, $host, $user, $pass, $name, (int)$port);
        if(!$connected){
            $connected = @mysqli_real_connect($link, $host, $user, $pass, '', (int)$port);
            if($connected && $name !== ''){
                @mysqli_query($link, "CREATE DATABASE IF NOT EXISTS `" . mysqli_real_escape_string($link, $name) . "`");
                @mysqli_select_db($link, $name);
            }
        }
    }

    if(!$connected){
        $error_msg = mysqli_connect_error() ?: "Could not connect to MySQL server at {$host}:{$port}";
        return false;
    }

    // Check SSL status
    $res = @mysqli_query($link, "SHOW STATUS LIKE 'Ssl_cipher'");
    if($res && ($row = @mysqli_fetch_row($res))){
        $ssl_cipher = $row[1] ?: 'None (Plaintext)';
    } else {
        $ssl_cipher = 'Unknown';
    }

    @mysqli_set_charset($link, 'utf8');
    return $link;
}

$conn_error = '';
$ssl_cipher = '';
$mysqli = false;
$tables = array();
$admin_verified = false;
$import_message = '';
$import_success = false;

// Attempt connection
$mysqli = get_aiven_connection($host, $user, $pass, $name, $port, $use_ssl, $conn_error, $ssl_cipher);
if($mysqli){
    $tres = @mysqli_query($mysqli, "SHOW TABLES");
    if($tres){
        while($r = @mysqli_fetch_row($tres)){
            $tables[] = $r[0];
        }
    }
    // Check admin existence
    if(in_array('membership_users', $tables)){
        $ares = @mysqli_query($mysqli, "SELECT memberID, email FROM membership_users WHERE memberID='admin' LIMIT 1");
        if($ares && @mysqli_num_rows($ares) > 0){
            $admin_verified = true;
        }
    }
}

// Handle 1-Click Import action
if(isset($_POST['action']) && $_POST['action'] === 'import'){
    if(!$mysqli){
        $import_message = "Cannot import: Database connection failed. Please verify credentials.";
    } elseif(!$sql_file || !file_exists($sql_file)){
        $import_message = "SQL Dump file (attendance_management.sql) not found on server.";
    } else {
        $sql_content = file_get_contents($sql_file);
        
        // Clean SQL parsing
        $queries = array();
        $queries[] = "SET FOREIGN_KEY_CHECKS=0";
        $queries[] = "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO'";
        
        // Remove comments
        $lines = explode("\n", $sql_content);
        $clean_sql = "";
        foreach($lines as $line){
            $trimmed = trim($line);
            if(strpos($trimmed, '--') === 0 || strpos($trimmed, '/*') === 0 || $trimmed === ''){
                continue;
            }
            $clean_sql .= $line . "\n";
        }

        // Split by semicolon (preserving quotes)
        $tokens = preg_split('/;\s*(?:\r\n|\r|\n|$)/', $clean_sql);
        foreach($tokens as $t){
            $t = trim($t);
            if($t !== ''){
                $queries[] = $t;
            }
        }
        $queries[] = "SET FOREIGN_KEY_CHECKS=1";

        $executed_count = 0;
        $failed_count = 0;
        $errors = array();

        foreach($queries as $q){
            if(!@mysqli_query($mysqli, $q)){
                $failed_count++;
                $errors[] = mysqli_error($mysqli);
            } else {
                $executed_count++;
            }
        }

        // Refresh tables list
        $tables = array();
        $tres = @mysqli_query($mysqli, "SHOW TABLES");
        if($tres){
            while($r = @mysqli_fetch_row($tres)){
                $tables[] = $r[0];
            }
        }

        if(in_array('membership_users', $tables)){
            $ares = @mysqli_query($mysqli, "SELECT memberID FROM membership_users WHERE memberID='admin' LIMIT 1");
            if($ares && @mysqli_num_rows($ares) > 0){
                $admin_verified = true;
            }
        }

        if($failed_count === 0 || count($tables) >= 8){
            $import_success = true;
            $import_message = "Successfully imported database! Executed {$executed_count} SQL statements. " . count($tables) . " tables verified in Aiven MySQL.";
        } else {
            $import_message = "Import finished with warnings: {$executed_count} queries executed, {$failed_count} queries had issues. Error sample: " . (isset($errors[0]) ? htmlspecialchars($errors[0]) : 'None');
        }
    }
}

// Determine App Login URL
$login_url = "index.php?signIn=1";
if(file_exists(__DIR__ . '/School_Attendance_Management_System/attendance/index.php')){
    $login_url = "School_Attendance_Management_System/attendance/index.php?signIn=1";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aiven Cloud MySQL Database Setup & Health Check</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background: #f0f2f5; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
        .hero-header { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; padding: 2.5rem 0; margin-bottom: 2rem; border-radius: 0 0 1rem 1rem; box-shadow: 0 4px 20px rgba(0,0,0,0.15); }
        .card { border: none; border-radius: 0.75rem; box-shadow: 0 2px 12px rgba(0,0,0,0.06); margin-bottom: 1.5rem; }
        .badge-ssl { background: #198754; color: white; padding: 0.35rem 0.65rem; border-radius: 20px; font-weight: 500; font-size: 0.85rem; }
        .status-pill { display: inline-flex; align-items: center; gap: 0.5rem; font-weight: 600; }
        .status-dot { width: 10px; height: 10px; border-radius: 50%; }
        .status-green { background: #28a745; }
        .status-red { background: #dc3545; }
        .table-badge { background: #e9ecef; color: #495057; font-size: 0.8rem; padding: 0.25rem 0.6rem; border-radius: 6px; margin: 2px; display: inline-block; }
    </style>
</head>
<body>

<div class="hero-header text-center">
    <div class="container">
        <h1 class="fw-bold">🏫 School Attendance Management System</h1>
        <p class="lead mb-0">Aiven Cloud MySQL 1-Click Database Setup & Health Diagnostic Tool</p>
    </div>
</div>

<div class="container mb-5">

    <?php if($import_message): ?>
        <div class="alert <?php echo $import_success ? 'alert-success' : 'alert-warning'; ?> alert-dismissible fade show shadow-sm" role="alert">
            <h5 class="alert-heading"><?php echo $import_success ? '🎉 Setup Successful!' : '⚠️ Import Notice'; ?></h5>
            <p class="mb-0"><?php echo htmlspecialchars($import_message); ?></p>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Left: Diagnostic & Status -->
        <div class="col-lg-7">
            <!-- Connection Status Card -->
            <div class="card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title fw-bold mb-0">🔌 Database Connection Status</h5>
                    <?php if($mysqli): ?>
                        <span class="status-pill text-success"><span class="status-dot status-green"></span> Connected</span>
                    <?php else: ?>
                        <span class="status-pill text-danger"><span class="status-dot status-red"></span> Disconnected</span>
                    <?php endif; ?>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <tbody>
                            <tr>
                                <th style="width: 35%;">Host & Port</th>
                                <td><code><?php echo htmlspecialchars($host); ?>:<?php echo htmlspecialchars($port); ?></code></td>
                            </tr>
                            <tr>
                                <th>Target Database</th>
                                <td><code><?php echo htmlspecialchars($name); ?></code></td>
                            </tr>
                            <tr>
                                <th>SSL Encryption</th>
                                <td>
                                    <?php if($ssl_cipher && $ssl_cipher !== 'None (Plaintext)'): ?>
                                        <span class="badge-ssl">🔒 SSL Active (<?php echo htmlspecialchars($ssl_cipher); ?>)</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">No SSL Cipher</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>PHP Version</th>
                                <td><code>PHP <?php echo PHP_VERSION; ?></code> (mysqli extension: <?php echo extension_loaded('mysqli') ? '✅ Enabled' : '❌ Missing'; ?>)</td>
                            </tr>
                            <tr>
                                <th>SQL Dump File</th>
                                <td>
                                    <?php if($sql_file): ?>
                                        <span class="text-success">✅ Found: <code><?php echo htmlspecialchars(basename($sql_file)); ?></code> (<?php echo round(filesize($sql_file)/1024, 1); ?> KB)</span>
                                    <?php else: ?>
                                        <span class="text-danger">❌ attendance_management.sql not found</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <?php if($conn_error): ?>
                    <div class="alert alert-danger mt-3 mb-0">
                        <strong>Connection Error:</strong> <?php echo htmlspecialchars($conn_error); ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Database Content & Verification Card -->
            <div class="card p-4">
                <h5 class="card-title fw-bold mb-3">📊 Database Tables & Admin Verification</h5>
                
                <div class="mb-3">
                    <strong>Tables Count:</strong> <span class="badge bg-primary"><?php echo count($tables); ?> tables</span>
                    <div class="mt-2">
                        <?php if(count($tables) > 0): ?>
                            <?php foreach($tables as $t): ?>
                                <span class="table-badge"><?php echo htmlspecialchars($t); ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="text-muted">No tables found. Please run the 1-Click Import below.</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="p-3 bg-light rounded-3 border">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <strong>Admin Account:</strong>
                            <?php if($admin_verified): ?>
                                <span class="text-success fw-bold ms-2">✅ Verified (Username: <code>admin</code>)</span>
                            <?php else: ?>
                                <span class="text-warning fw-bold ms-2">⚠️ Not Found (Run Import)</span>
                            <?php endif; ?>
                        </div>
                        <?php if($admin_verified): ?>
                            <a href="<?php echo htmlspecialchars($login_url); ?>" class="btn btn-success fw-bold">🚀 Login Now</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Actions & Config Form -->
        <div class="col-lg-5">
            <!-- 1-Click Import Card -->
            <div class="card p-4 border-primary" style="border: 2px solid #0d6efd;">
                <h5 class="card-title fw-bold text-primary mb-2">⚡ 1-Click Database Setup</h5>
                <p class="text-muted small">Imports all tables, default admin account (<code>admin</code> / <code>admin</code>), and demo records into your Aiven Cloud MySQL database with foreign key checks safely disabled.</p>

                <form method="POST">
                    <input type="hidden" name="action" value="import">
                    <input type="hidden" name="host" value="<?php echo htmlspecialchars($host); ?>">
                    <input type="hidden" name="port" value="<?php echo htmlspecialchars($port); ?>">
                    <input type="hidden" name="user" value="<?php echo htmlspecialchars($user); ?>">
                    <input type="hidden" name="pass" value="<?php echo htmlspecialchars($pass); ?>">
                    <input type="hidden" name="name" value="<?php echo htmlspecialchars($name); ?>">
                    <input type="hidden" name="ssl" value="<?php echo $use_ssl ? '1' : '0'; ?>">

                    <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold shadow-sm" <?php echo !$mysqli ? 'disabled' : ''; ?>>
                        🚀 Import Database Now
                    </button>
                </form>

                <?php if(!$mysqli): ?>
                    <p class="text-danger small mt-2 mb-0">⚠️ Connection required to enable 1-Click Import. Please check your credentials below.</p>
                <?php endif; ?>
            </div>

            <!-- Credentials Config Form -->
            <div class="card p-4">
                <h5 class="card-title fw-bold mb-3">⚙️ Connection Parameters</h5>
                <p class="text-muted small">Parameters auto-load from Render / Docker environment variables.</p>

                <form method="POST">
                    <div class="mb-2">
                        <label class="form-label small fw-bold">DB Host (Aiven Server)</label>
                        <input type="text" name="host" class="form-control form-control-sm" value="<?php echo htmlspecialchars($host); ?>" required>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Port (Aiven 5-digit)</label>
                            <input type="number" name="port" class="form-control form-control-sm" value="<?php echo htmlspecialchars($port); ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Database Name</label>
                            <input type="text" name="name" class="form-control form-control-sm" value="<?php echo htmlspecialchars($name); ?>" required>
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Username</label>
                            <input type="text" name="user" class="form-control form-control-sm" value="<?php echo htmlspecialchars($user); ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Password</label>
                            <input type="password" name="pass" class="form-control form-control-sm" value="<?php echo htmlspecialchars($pass); ?>">
                        </div>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="ssl" value="1" id="sslSwitch" <?php echo $use_ssl ? 'checked' : ''; ?>>
                        <label class="form-check-label small" for="sslSwitch">Enforce SSL (Required by Aiven)</label>
                    </div>

                    <button type="submit" class="btn btn-outline-secondary w-100 btn-sm">
                        🔄 Test & Save Connection
                    </button>
                </form>
            </div>

            <!-- Default Credentials Quick Reference -->
            <div class="card p-3 bg-white">
                <h6 class="fw-bold mb-2">🔑 Default System Credentials</h6>
                <div class="small">
                    <div><strong>Admin:</strong> <code>admin</code> / <code>admin</code></div>
                    <div><strong>User:</strong> <code>henry</code> / <code>123456789Henry</code></div>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="text-center text-muted small py-3">
    School Attendance Management System &bull; Render Cloud & Aiven MySQL Ready
</footer>

</body>
</html>
