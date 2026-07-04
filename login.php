<?php
/**
 * login.php
 * 
 * Authenticates the user based on database entries from the 'userinfo' table.
 * Verifies secure one-way password hashes using password_verify().
 */

session_start();

require_once __DIR__ . '/conf/db_config.php';
require_once __DIR__ . '/lib_table/userinfo_tbl.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = isset($_POST['user_id']) ? trim($_POST['user_id']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($userId === '' || $password === '') {
        header("Location: index.html?error=invalid");
        exit;
    }

    // userinfo 테이블 모델 인스턴스 생성
    $userinfoTbl = new userinfo_tbl();

    // 회원 정보 조회를 통해 인증 수행 (SQL 쿼리 캡슐화)
    $row = $userinfoTbl->getUserForAuth($userId);

    if ($row !== null) {
        // Verify the password hash securely
        if (password_verify($password, $row['password'])) {
            // Authentication success: store session variables
            $_SESSION['authenticated'] = true;
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['name'] = $row['name'];
            $_SESSION['last_activity'] = time();

            // 로그인 성공 시 마지막 로그인 시간 업데이트
            $userinfoTbl->updateLastLogin($row['id']);

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
