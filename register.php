<?php
/**
 * register.php
 * 
 * Member registration page.
 * Handles the registration POST request and displays the signup form.
 */

require_once __DIR__ . '/conf/db_config.php';

$errorMsg = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = isset($_POST['user_id']) ? trim($_POST['user_id']) : '';
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';

    if ($userId === '' || $name === '' || $password === '') {
        $errorMsg = '아이디, 이름, 비밀번호는 필수 입력 항목입니다.';
    } else {
        $db = get_db_connection();

        if ($db === false) {
            $errorMsg = '데이터베이스 연결에 실패했습니다. 관리자에게 문의하세요.';
        } else {
            // 1. Double check ID duplication on the server side
            $checkSql = "SELECT id FROM userinfo WHERE user_id = ?";
            $checkStmt = $db->prepare($checkSql);
            $checkRes = $db->execute($checkStmt, array($userId));

            if ($checkRes && $db->num_rows($checkRes) > 0) {
                $errorMsg = '이미 존재하거나 사용 중인 아이디입니다.';
            } else {
                // 2. Encrypt/Hash the password using secure bcrypt
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // 3. Insert user info into database
                $insertSql = "INSERT INTO userinfo (user_id, name, password, phone, email, address, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
                $insertStmt = $db->prepare($insertSql);
                $params = array($userId, $name, $hashedPassword, $phone, $email, $address);

                $insertRes = $db->execute($insertStmt, $params);

                if ($insertRes) {
                    // Registration successful, redirect to login page with success code
                    header("Location: index.html?register=success");
                    exit;
                } else {
                    $errorMsg = '회원 가입 처리 중 데이터베이스 오류가 발생했습니다: ' . $db->errormsg();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>도시이야기 - 회원가입</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .glass-container.signup-container {
            max-width: 500px;
            padding: 2.5rem 3rem;
            margin: 2rem auto;
        }
        .form-group-row {
            display: flex;
            gap: 0.8rem;
        }
        .form-group-row .form-group {
            flex: 1;
        }
        .btn-check-id:hover {
            background: rgba(139, 92, 246, 0.15) !important;
            border-color: var(--primary-color) !important;
        }
    </style>
</head>
<body>
    <div class="glass-container signup-container">
        <h1>도시이야기 - 회원가입</h1>
        <p class="subtitle">시스템 사용을 위해 회원 정보를 등록하십시오.</p>

        <?php if (!empty($errorMsg)): ?>
        <div id="errorBox" class="error-msg" style="display: flex;">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <span><?php echo htmlspecialchars($errorMsg); ?></span>
        </div>
        <?php endif; ?>

        <div id="jsErrorBox" class="error-msg">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <span id="jsErrorMsg"></span>
        </div>

        <form action="register.php" method="POST" id="signupForm">
            <!-- ID (Duplication Check) -->
            <div class="form-group">
                <label for="user_id">아이디 *</label>
                <div style="display: flex; gap: 0.5rem;">
                    <div class="input-wrapper" style="flex-grow: 1;">
                        <input type="text" id="user_id" name="user_id" placeholder="사용할 ID 입력" required autocomplete="off">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <button type="button" id="btnCheckId" class="btn-check-id" style="width: auto; padding: 0 1.2rem; border: 1px solid var(--card-border); border-radius: 12px; background: rgba(255,255,255,0.03); color: var(--text-primary); font-size: 0.95rem; font-weight: 500; cursor: pointer; transition: var(--transition);">중복 확인</button>
                </div>
                <div id="idCheckMsg" style="margin-top: 0.4rem; font-size: 0.85rem; text-align: left; display: none;"></div>
            </div>

            <!-- Name & Password -->
            <div class="form-group-row">
                <div class="form-group">
                    <label for="name">이름 *</label>
                    <div class="input-wrapper">
                        <input type="text" id="name" name="name" placeholder="이름" required>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                        </svg>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">비밀번호 *</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" placeholder="••••••••" required>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Phone & Email -->
            <div class="form-group-row">
                <div class="form-group">
                    <label for="phone">전화번호</label>
                    <div class="input-wrapper">
                        <input type="tel" id="phone" name="phone" placeholder="010-0000-0000">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                        </svg>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">이메일</label>
                    <div class="input-wrapper">
                        <input type="email" id="email" name="email" placeholder="example@mail.com">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Address -->
            <div class="form-group">
                <label for="address">주소</label>
                <div class="input-wrapper">
                    <input type="text" id="address" name="address" placeholder="기본 주소 입력">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                        <circle cx="12" cy="10" r="3"></circle>
                    </svg>
                </div>
            </div>
            
            <button type="submit" class="btn-submit" style="margin-top: 1rem;">가입 완료</button>
        </form>

        <div class="login-footer" style="margin-top: 1.5rem; font-size: 0.95rem; color: var(--text-secondary);">
            이미 계정이 있으신가요? <a href="index.html" style="color: var(--primary-color); text-decoration: none; font-weight: 600; transition: var(--transition);" onmouseover="this.style.color='var(--secondary-color)'" onmouseout="this.style.color='var(--primary-color)'">로그인</a>
        </div>
    </div>

    <script>
        let isIdChecked = false;
        let checkedId = '';

        const userIdInput = document.getElementById('user_id');
        const btnCheckId = document.getElementById('btnCheckId');
        const idCheckMsg = document.getElementById('idCheckMsg');
        const signupForm = document.getElementById('signupForm');
        const jsErrorBox = document.getElementById('jsErrorBox');
        const jsErrorMsg = document.getElementById('jsErrorMsg');

        // Reset check status when ID changes
        userIdInput.addEventListener('input', () => {
            isIdChecked = false;
            idCheckMsg.style.display = 'none';
            idCheckMsg.textContent = '';
        });

        // Perform async ID check
        btnCheckId.addEventListener('click', () => {
            const userId = userIdInput.value.trim();
            if (userId === '') {
                showMsg('아이디를 입력해 주세요.', 'error');
                return;
            }

            // Call check_id.php API
            fetch(`check_id.php?user_id=${encodeURIComponent(userId)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        if (data.error === 'db_connection_failed') {
                            showMsg('DB 미설치 또는 오프라인 상태입니다. 중복 확인 없이 임시 진행할 수 있습니다. (연결에 실패했습니다)', 'warning');
                            isIdChecked = true; // allow bypass in offline/uninstalled DB modes for development test, but note it
                            checkedId = userId;
                        } else {
                            showMsg(`오류 발생: ${data.message || data.error}`, 'error');
                        }
                    } else if (data.exists) {
                        showMsg('이미 사용 중인 아이디입니다.', 'error');
                        isIdChecked = false;
                    } else {
                        showMsg('사용 가능한 아이디입니다.', 'success');
                        isIdChecked = true;
                        checkedId = userId;
                    }
                })
                .catch(err => {
                    console.error('ID check failed:', err);
                    showMsg('ID 중복 확인을 수행할 수 없습니다 (서버 통신 실패).', 'error');
                });
        });

        // Form submission handler
        signupForm.addEventListener('submit', (e) => {
            const userId = userIdInput.value.trim();
            
            // Check if ID was checked
            if (!isIdChecked || checkedId !== userId) {
                e.preventDefault();
                jsErrorBox.style.display = 'flex';
                jsErrorMsg.textContent = '아이디 중복 확인을 완료해 주십시오.';
                userIdInput.focus();
                return;
            }

            jsErrorBox.style.display = 'none';
        });

        // Helper to show ID check results
        function showMsg(text, type) {
            idCheckMsg.textContent = text;
            idCheckMsg.style.display = 'block';
            
            if (type === 'success') {
                idCheckMsg.style.color = '#10b981'; // Green
            } else if (type === 'warning') {
                idCheckMsg.style.color = '#eab308'; // Yellow
            } else {
                idCheckMsg.style.color = 'var(--error-color)'; // Red
            }
        }
    </script>
</body>
</html>
