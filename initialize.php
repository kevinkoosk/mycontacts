<?php
session_start();

// Generate CSRF token if needed
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token.");
    }
    
    if (empty($_POST['admin_password'])) {
        $error = "Please enter a desired password.";
    } else {
        $adminPassword = $_POST['admin_password'];
        try {
            $dbFile = __DIR__ . '/../clients.db';
            $pdo = new PDO('sqlite:' . $dbFile);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create the users table
            $pdo->exec("CREATE TABLE IF NOT EXISTS users (
                username TEXT PRIMARY KEY,
                password TEXT NOT NULL,
                category TEXT NOT NULL
            )");
            
            // Create the clients table
            $pdo->exec("CREATE TABLE IF NOT EXISTS clients (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                nric_passport TEXT,
                telephone TEXT,
                email TEXT,
                address_line1 TEXT,
                address_line2 TEXT,
                address_line3 TEXT,
                address_line4 TEXT,
                status TEXT,
                our_reference TEXT,
                client_notes TEXT
            )");
            
            // Hash the admin password using PHP’s built-in function
            $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
            
            // Insert default admin user
            $stmt = $pdo->prepare("INSERT INTO users (username, password, category) VALUES (?, ?, ?)");
            $stmt->execute(['admin', $hashedPassword, 'admin']);
            
            // Output success message
            echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <title>Initialization Success</title>
    <link rel='stylesheet' href='./css/bootstrap.min.css'>
</head>
<body>
<div class='container mt-5'>
    <div class='alert alert-success'>
        <strong>Success!</strong> Database initialized and admin user created.
        Please start the application at <a href='app.php'>app.php</a>.
    </div>
</div>
<script src='./js/bootstrap.bundle.min.js'></script>
</body>
</html>";
            
            // Delete this file for security
            unlink(__FILE__);
            exit;
        } catch (PDOException $e) {
            die("Database error: " . htmlspecialchars($e->getMessage()));
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
   <meta charset="utf-8">
   <title>Initialize Client Database</title>
   <link rel="stylesheet" href="./css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h2>Initialize Client Database</h2>
    <?php if (isset($error)) {
        echo "<div class='alert alert-danger'>" . htmlspecialchars($error) . "</div>";
    } ?>
    <form method="post">
         <div class="mb-3">
             <label for="admin_password" class="form-label">Desired Admin Password</label>
             <input type="password" name="admin_password" id="admin_password" class="form-control" required>
         </div>
         <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
         <button type="submit" class="btn btn-primary">Initialize</button>
    </form>
</div>
<script src="./js/bootstrap.bundle.min.js"></script>
</body>
</html>
