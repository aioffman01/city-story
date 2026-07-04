<?php
session_start();

header('Content-Type: application/json');

$response = [
    'authenticated' => false
];

if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    // 세션이 유효한 경우 세션 갱신 및 상태 반환
    $_SESSION['last_activity'] = time();
    $response['authenticated'] = true;
    // 로그인된 사용자 ID 정보 추가
    $response['user_id'] = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '';
}

echo json_encode($response);
exit;
?>
