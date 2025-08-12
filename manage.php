<?php
session_start();

// Ensure only admin users can access this page.
if (!isset($_SESSION['username']) || $_SESSION['category'] !== 'admin') {
    header("Location: app.php");
    exit;
}

$dbFile = __DIR__ . '/../clients.db';
try {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database error: " . htmlspecialchars($e->getMessage()));
}

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Handle form submissions for updating, adding, or deleting users.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $csrf_token) {
        die("Invalid CSRF token.");
    }
    
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'update_user') {
            // Update user password and/or category.
            $username = $_POST['username'];
            $new_password = $_POST['new_password'];
            $new_category = $_POST['new_category'];
            
            // The default admin’s category must remain "admin"
            if ($username === 'admin') {
                $new_category = 'admin';
            }
            
            if (!empty($new_password)) {
                $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ?, category = ? WHERE username = ?");
                $stmt->execute([$hashedPassword, $new_category, $username]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET category = ? WHERE username = ?");
                $stmt->execute([$new_category, $username]);
            }
            $message = "User '$username' updated successfully.";
        } elseif ($_POST['action'] === 'add_user') {
            // Add a new user.
            $username = $_POST['new_username'];
            $new_password = $_POST['new_password'];
            $new_category = $_POST['new_category'];
            
            if (empty($username) || empty($new_password) || empty($new_category)) {
                $error = "All fields are required for adding a new user.";
            } else {
                $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, category) VALUES (?, ?, ?)");
                try {
                    $stmt->execute([$username, $hashedPassword, $new_category]);
                    $message = "New user '$username' added successfully.";
                } catch (PDOException $e) {
                    $error = "Error adding user: " . htmlspecialchars($e->getMessage());
                }
            }
        } elseif ($_POST['action'] === 'delete_user') {
            // Delete a user. Cannot delete the default admin.
            $username = $_POST['username'];
            if ($username === 'admin') {
                $error = "Cannot delete the default admin user.";
            } else {
                $stmt = $pdo->prepare("DELETE FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $message = "User '$username' deleted successfully.";
            }
        }
    }
}

// Pagination for users list.
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$per_page = 25;
$offset = ($page - 1) * $per_page;

$query = "SELECT * FROM users";
$params = [];
if (!empty($search)) {
    $query .= " WHERE username LIKE ? OR category LIKE ?";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$total_users_stmt = $pdo->prepare("SELECT COUNT(*) FROM (" . $query . ")");
$total_users_stmt->execute($params);
$total_users = $total_users_stmt->fetchColumn();

$query .= " LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_pages = ceil($total_users / $per_page);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admin Dashboard - Manage Users</title>
    <link rel="stylesheet" href="./css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h2>Admin Dashboard - Manage Users</h2>
    <?php
    if (isset($message)) {
        echo "<div class='alert alert-success'>" . htmlspecialchars($message) . "</div>";
    }
    if (isset($error)) {
        echo "<div class='alert alert-danger'>" . htmlspecialchars($error) . "</div>";
    }
    ?>
    <a href="app.php" class="btn btn-primary mb-3">Back to app</a>
    <form method="get" class="mb-3">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Search users" value="<?php echo htmlspecialchars($search); ?>">
            <button class="btn btn-outline-secondary" type="submit">Search</button>
        </div>
    </form>
    <table class="table table-bordered">
       <thead>
         <tr>
            <th>Username</th>
            <th>Category</th>
            <th>Actions</th>
         </tr>
       </thead>
       <tbody>
         <?php foreach ($users as $user): ?>
         <tr>
            <td><?php echo htmlspecialchars($user['username']); ?></td>
            <td><?php echo htmlspecialchars($user['category']); ?></td>
            <td>
                <form method="post" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" value="update_user">
                    <input type="hidden" name="username" value="<?php echo htmlspecialchars($user['username']); ?>">
                    <div class="input-group">
                        <input type="password" name="new_password" class="form-control" placeholder="New Password">
                        <select name="new_category" class="form-select">
                            <option value="admin" <?php if($user['category']=='admin') echo 'selected'; ?>>admin</option>
                            <option value="editor" <?php if($user['category']=='editor') echo 'selected'; ?>>editor</option>
                            <option value="browser" <?php if($user['category']=='browser') echo 'selected'; ?>>browser</option>
                        </select>
                        <button type="submit" class="btn btn-success">Update</button>
                    </div>
                </form>
                <?php if ($user['username'] !== 'admin'): ?>
                <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete user <?php echo htmlspecialchars($user['username']); ?>?');">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="username" value="<?php echo htmlspecialchars($user['username']); ?>">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
                <?php endif; ?>
            </td>
         </tr>
         <?php endforeach; ?>
       </tbody>
    </table>
    <!-- Pagination -->
    <nav>
      <ul class="pagination">
        <?php for ($p = 1; $p <= $total_pages; $p++): ?>
            <li class="page-item <?php if($p == $page) echo 'active'; ?>">
                <a class="page-link" href="?page=<?php echo $p; ?>&search=<?php echo urlencode($search); ?>"><?php echo $p; ?></a>
            </li>
        <?php endfor; ?>
      </ul>
    </nav>
    <hr>
    <h3>Add New User</h3>
    <form method="post">
         <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
         <input type="hidden" name="action" value="add_user">
         <div class="mb-3">
             <label class="form-label">Username</label>
             <input type="text" name="new_username" class="form-control" required>
         </div>
         <div class="mb-3">
             <label class="form-label">Password</label>
             <input type="password" name="new_password" class="form-control" required>
         </div>
         <div class="mb-3">
             <label class="form-label">Category</label>
             <select name="new_category" class="form-select" required>
                 <option value="admin">admin</option>
                 <option value="editor">editor</option>
                 <option value="browser">browser</option>
             </select>
         </div>
         <button type="submit" class="btn btn-success">Add User</button>
    </form>
</div>
<script src="./js/bootstrap.bundle.min.js"></script>
</body>
</html>
