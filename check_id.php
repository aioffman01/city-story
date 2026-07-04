<?php
/**
 * check_id.php
 * 
 * Asynchronous endpoint for checking User ID duplication.
 * Returns JSON object: { "exists": true|false, "error": ... }
 */

header('Content-Type: application/json');

require_once __DIR__ . '/conf/db_config.php';
require_once __DIR__ . '/lib_table/userinfo_tbl.php';

// Accept both POST and GET for flexibility (prefer POST for forms, GET for test)
$userId = isset($_REQUEST['user_id']) ? trim($_REQUEST['user_id']) : '';

if ($userId === '') {
    echo json_encode(array('exists' => false, 'error' => 'empty_id'));
    exit;
}

$db = get_db_connection();

if ($db === false) {
    // Return connection error information for debugging
    echo json_encode(array(
        'exists' => false, 
        'error' => 'db_connection_failed',
        'message' => 'Could not connect to the database. Please ensure MySQL is running.'
    ));
    exit;
}

// userinfo 테이블 모델 클래스 인스턴스 생성
$userinfoTbl = new userinfo_tbl();

// 클래스 메서드를 통해 중복 아이디가 존재하는지 확인 (SQL 쿼리가 캡슐화됨)
$exists = $userinfoTbl->existsUser($userId);

echo json_encode(array(
    'exists' => $exists
));
exit;
