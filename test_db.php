<?php
/**
 * DB Library Verification and Reference script
 * 
 * Verifies class syntax, namespace loading, and displays detailed usage instructions.
 */

require_once __DIR__ . '/conf/db_config.php';

echo "=== MysqlOdbc Library Verification ===\n\n";

// 1. Check classes existence
echo "Checking if wrapper classes exist:\n";
echo "Class 'MysqlOdbc' exists:          " . (class_exists('MysqlOdbc') ? "YES" : "NO") . "\n";
echo "Class 'MysqlOdbcResult' exists:    " . (class_exists('MysqlOdbcResult') ? "YES" : "NO") . "\n";
echo "Class 'MysqlOdbcStatement' exists: " . (class_exists('MysqlOdbcStatement') ? "YES" : "NO") . "\n\n";

// 2. Demonstrate ODBC-like connection error handling
echo "Demonstrating ODBC-like connection validation...\n";
$db = get_db_connection(); // Calls connect internally

if ($db === false) {
    echo "Connection failed as expected (database server not running or not configured).\n";
    
    // Create direct connection to show error handling explicitly
    $testDb = new MysqlOdbc();
    $testDb->connect("host=127.0.0.1;port=3306;dbname=non_existent_db", "wrong_user", "wrong_password");
    echo "Last connection error message: " . $testDb->errormsg() . "\n";
    echo "Last connection error code:    " . $testDb->errorcode() . "\n\n";
} else {
    echo "Successfully connected to the database!\n\n";
}

// 3. Show API usage examples (printed as reference)
echo "---------------------------------------------------------\n";
echo "API Usage Reference Examples:\n";
echo "---------------------------------------------------------\n";
echo "<?php\n";
echo "// 1. Establish connection\n";
echo "\$db = get_db_connection();\n\n";

echo "// 2. Execute SQL directly (odbc_exec style)\n";
echo "\$result = \$db->exec(\"SELECT id, name, description FROM items WHERE status = 'active'\");\n";
echo "if (\$result === false) {\n";
echo "    die(\"Query failed: \" . \$db->errormsg());\n";
echo "}\n\n";

echo "// 3. Fetch rows (odbc_fetch_row & odbc_result style)\n";
echo "while (\$db->fetch_row(\$result)) {\n";
echo "    \$id   = \$db->result(\$result, 1);             // 1-based column index\n";
echo "    \$name = \$db->result(\$result, 'name');        // column name string\n";
echo "    echo \"ID: \$id, Name: \$name\\n\";\n";
echo "}\n\n";

echo "// 4. Fetch as associative array (odbc_fetch_array style)\n";
echo "\$result2 = \$db->exec(\"SELECT * FROM items\");\n";
echo "while (\$row = \$db->fetch_array(\$result2)) {\n";
echo "    echo \"Name: \" . \$row['name'] . \"\\n\";\n";
echo "}\n\n";

echo "// 5. Prepared Statements (odbc_prepare & odbc_execute style)\n";
echo "\$stmt = \$db->prepare(\"INSERT INTO items (name, quantity, price) VALUES (?, ?, ?)\");\n";
echo "if (\$stmt !== false) {\n";
echo "    // Bind and execute with parameters array\n";
echo "    \$success = \$db->execute(\$stmt, array('Apple', 10, 1.99));\n";
echo "    if (!\$success) {\n";
echo "        echo \"Execution error: \" . \$db->errormsg();\n";
echo "    } else {\n";
echo "        echo \"Inserted ID: \" . \$db->insert_id() . \"\\n\";\n";
echo "    }\n";
echo "}\n\n";

echo "// 6. Transactions (odbc_autocommit, odbc_commit, odbc_rollback style)\n";
echo "\$db->autocommit(false); // Begin transaction\n";
echo "\$res1 = \$db->exec(\"UPDATE account SET balance = balance - 100 WHERE id = 1\");\n";
echo "\$res2 = \$db->exec(\"UPDATE account SET balance = balance + 100 WHERE id = 2\");\n";
echo "if (\$res1 && \$res2) {\n";
echo "    \$db->commit();\n";
echo "} else {\n";
echo "    \$db->rollback();\n";
echo "}\n";
echo "=========================================================\n";
