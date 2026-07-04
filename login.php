<?php
/**
 * login.php
 * 
 * Authenticates the user based on database entries from the 'userinfo' table.
 * Verifies secure one-way password hashes using password_verify().
 */

session_start();

require_once __DIR__ . '/conf/db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = isset($_POST['user_id']) ? trim($_POST['user_id']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($userId === '' || $password === '') {
        header("Location: index.html?error=invalid");
        exit;
    }

    $db = get_db_connection();

    if ($db === false) {
        error_log("Login database connection failed.");
        header("Location: index.html?error=invalid");
        exit;
    }

    // Query user by user_id
    $sql = "SELECT id, user_id, name, password FROM userinfo WHERE user_id = ?";
    $stmt = $db->prepare($sql);
    
    if ($stmt === false) {
        error_log("Login query prepare failed: " . $db->errormsg());
        header("Location: index.html?error=invalid");
        exit;
    }

    $result = $db->execute($stmt, array($userId));

    if ($result && $db->num_rows($result) > 0) {
        $row = $db->fetch_array($result);
        
        // Verify the password hash securely
        if (password_verify($password, $row['password'])) {
            // Authentication success: store session variables
            $_SESSION['authenticated'] = true;
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['name'] = $row['name'];
            $_SESSION['last_activity'] = time();

            // Log login time to the database
            $updateSql = "UPDATE userinfo SET last_login_at = NOW() WHERE id = ?";
            $updateStmt = $db->prepare($updateSql);
            if ($updateStmt !== false) {
                $db->execute($updateStmt, array($row['id']));
            }

            header("Location: menu.html");
            exit;
        }
    }

    // Authentication failed
    header("Location: index.html?error=invalid");
    exit;
} else {
    header("Location: index.html");
    exit;
}
?>
