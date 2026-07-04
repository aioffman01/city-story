<?php
/**
 * Database Configuration
 * 
 * Defines the connection parameters for the MySQL database
 * and provides a helper function to retrieve the DB connection wrapper.
 */

// Require the MysqlOdbc class
require_once dirname(__DIR__) . '/php_lib/MysqlOdbc.php';

// Database credentials setup
// Modify these constants to match your database environment
define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'test_db');
define('DB_CHARSET', 'utf8mb4');

/**
 * Retrieves the database connection singleton.
 * Keeps a static connection object to avoid duplicate connections.
 * 
 * @return MysqlOdbc|false Returns MysqlOdbc instance on success, false on connection failure
 */
function get_db_connection() {
    static $dbInstance = null;

    if ($dbInstance === null) {
        $dbInstance = new MysqlOdbc();
        
        // Build DSN string for ODBC mimic connection
        $dsn = sprintf("host=%s;port=%d;dbname=%s;charset=%s", DB_HOST, DB_PORT, DB_NAME, DB_CHARSET);
        $connected = $dbInstance->connect($dsn, DB_USER, DB_PASSWORD);

        if (!$connected) {
            error_log("MysqlOdbc Connection failed: " . $dbInstance->errormsg());
            $dbInstance = null; // Reset for future retries if appropriate
            return false;
        }
    }

    return $dbInstance;
}
