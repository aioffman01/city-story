<?php
/**
 * check_id.php
 * 
 * Asynchronous endpoint for checking User ID duplication.
 * Returns JSON object: { "exists": true|false, "error": ... }
 */

header('Content-Type: application/json');

require_once __DIR__ . '/conf/db_config.php';

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

$sql = "SELECT id FROM userinfo WHERE user_id = ?";
$stmt = $db->prepare($sql);

if ($stmt === false) {
    echo json_encode(array(
        'exists' => false, 
        'error' => 'db_prepare_failed',
        'message' => $db->errormsg()
    ));
    exit;
}

$result = $db->execute($stmt, array($userId));

if ($result === false) {
    echo json_encode(array(
        'exists' => false, 
        'error' => 'db_execute_failed',
        'message' => $db->errormsg()
    ));
    exit;
}

$exists = ($db->num_rows($result) > 0);

echo json_encode(array(
    'exists' => $exists
));
exit;
