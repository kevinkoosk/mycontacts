<?php
session_start();

$dbFile = __DIR__ . '/../clients.db';

function getDB() {
    global $dbFile;
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function is_logged_in() {
    return isset($_SESSION['username']);
}

function require_login() {
    if (!is_logged_in()) {
        header("Location: app.php?action=login");
        exit;
    }
}

function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

// ---------------------- LOGIN ----------------------
if ($action === 'login') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
            die("Invalid CSRF token.");
        }
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['username'] = $user['username'];
            $_SESSION['category'] = $user['category'];
            header("Location: app.php");
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    }
    $csrf_token = generate_csrf_token();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Login</title>
        <link rel="stylesheet" href="./css/bootstrap.min.css">
    </head>
    <body>
    <div class="container mt-5">
        <h2>Login</h2>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo h($error); ?></div>
        <?php endif; ?>
        <form method="post" action="app.php?action=login">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" name="username" id="username" class="form-control" required>
            </div>
            <div class="mb-3">
               <label for="password" class="form-label">Password</label>
               <input type="password" name="password" id="password" class="form-control" required>
            </div>
            <input type="hidden" name="csrf_token" value="<?php echo h($csrf_token); ?>">
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
    </div>
    <script src="./js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
    exit;
} 
// ---------------------- LOGOUT ----------------------
elseif ($action === 'logout') {
    session_destroy();
    header("Location: app.php?action=login");
    exit;
} 
// ---------------------- VIEW CLIENT DETAIL ----------------------
elseif ($action === 'view') {
    require_login();
    if (!isset($_GET['id'])) {
        die("No client id specified.");
    }
    $client_id = (int)$_GET['id'];
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$client_id]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$client) { die("Client not found."); }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
       <meta charset="utf-8">
       <title>Client Detail</title>
       <link rel="stylesheet" href="./css/bootstrap.min.css">
    </head>
    <body>
      <div class="container mt-5">
         <h2>Client Detail</h2>
         <p><strong>Name:</strong> <?php echo h($client['name']); ?></p>
         <p><strong>NRIC/Passport:</strong> <?php echo h($client['nric_passport']); ?></p>
         <p><strong>Telephone:</strong> <?php echo h($client['telephone']); ?></p>
         <p><strong>Email:</strong> <?php echo h($client['email']); ?></p>
         <p><strong>Address:</strong><br>
            <?php echo nl2br(h($client['address_line1'])); ?><br>
            <?php echo nl2br(h($client['address_line2'])); ?><br>
            <?php echo nl2br(h($client['address_line3'])); ?><br>
            <?php echo nl2br(h($client['address_line4'])); ?>
         </p>
         <p><strong>Status:</strong> <?php echo h($client['status']); ?></p>
         <p><strong>Our Reference:</strong> <?php echo h($client['our_reference']); ?></p>
         <p><strong>Client Notes:</strong> <?php echo h($client['client_notes']); ?></p>
         <a href="app.php?action=export&id=<?php echo $client_id; ?>&format=md" class="btn btn-secondary">Export to Markdown</a>
         <a href="app.php?action=export&id=<?php echo $client_id; ?>&format=docx" class="btn btn-secondary">Export to DOCX</a>
         <?php if (in_array($_SESSION['category'], ['admin', 'editor'])): ?>
             <a href="app.php?action=edit&id=<?php echo $client_id; ?>" class="btn btn-primary">Edit Client</a>
             <a href="app.php?action=delete&id=<?php echo $client_id; ?>" class="btn btn-danger">Delete Client</a>
         <?php endif; ?>
         <br><br>
         <a href="app.php" class="btn btn-primary">Back to Clients</a>
      </div>
      <script src="./js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
    exit;
} 
// ---------------------- EXPORT CLIENT DETAIL ----------------------
elseif ($action === 'export') {
    require_login();
    if (!isset($_GET['id']) || !isset($_GET['format'])) {
        die("Missing parameters.");
    }
    $client_id = (int)$_GET['id'];
    $format = $_GET['format'];
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$client_id]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$client) { die("Client not found."); }
    
    // Build a Markdown representation of the client detail.
    $content = "# Client Detail\n\n";
    $content .= "**Name:** " . $client['name'] . "\n\n";
    $content .= "**NRIC/Passport:** " . $client['nric_passport'] . "\n\n";
    $content .= "**Telephone:** " . $client['telephone'] . "\n\n";
    $content .= "**Email:** " . $client['email'] . "\n\n";
    $content .= "**Address:**\n" 
              . $client['address_line1'] . "\n" 
              . $client['address_line2'] . "\n" 
              . $client['address_line3'] . "\n" 
              . $client['address_line4'] . "\n\n";
    $content .= "**Status:** " . $client['status'] . "\n\n";
    $content .= "**Our Reference:** " . $client['our_reference'] . "\n\n";
    $content .= "**Client Notes:** " . $client['client_notes'] . "\n\n";
    
    if ($format === 'md') {
        header('Content-Type: text/markdown');
        header('Content-Disposition: attachment; filename="client_' . $client_id . '.md"');
        echo $content;
        exit;
    } elseif ($format === 'docx') {
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="client_' . $client_id . '.docx"');
        echo $content;
        exit;
    } else {
        die("Invalid format specified.");
    }
} 
// ---------------------- ADD NEW CLIENT ----------------------
elseif ($action === 'add') {
    require_login();
    if (!in_array($_SESSION['category'], ['admin', 'editor'])) {
        die("You do not have permission to add clients.");
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
            die("Invalid CSRF token.");
        }
        $name           = trim($_POST['name']);
        $nric_passport  = trim($_POST['nric_passport']);
        $telephone      = trim($_POST['telephone']);
        $email          = trim($_POST['email']);
        $address_line1  = trim($_POST['address_line1']);
        $address_line2  = trim($_POST['address_line2']);
        $address_line3  = trim($_POST['address_line3']);
        $address_line4  = trim($_POST['address_line4']);
        $status         = trim($_POST['status']);
        $our_reference  = trim($_POST['our_reference']);
        $client_notes   = trim($_POST['client_notes']);
        
        $pdo = getDB();
        $stmt
