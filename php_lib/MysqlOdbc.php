<?php
/**
 * MysqlOdbc Library
 * 
 * A PHP library wrapping mysqli to mimic PHP's ODBC function interfaces.
 * Provides a clean object-oriented class that makes transitioning from ODBC simple.
 */

class MysqlOdbc {
    private $conn = null;
    private $error = '';
    private $errorCode = 0;

    /**
     * Constructor
     */
    public function __construct() {
        // Disable exceptions for mysqli globally to return false on failures (mimicking ODBC behavior)
        @mysqli_report(MYSQLI_REPORT_OFF);
    }

    /**
     * Destructor
     */
    public function __destruct() {
        $this->close();
    }

    /**
     * Establish database connection (mimics odbc_connect / odbc_pconnect)
     * Supports either a connection DSN-like string or separate parameters.
     * 
     * @param string $dsn_or_host Hostname or "host=localhost;port=3306;dbname=test_db;charset=utf8mb4"
     * @param string $user Username
     * @param string $password Password
     * @param string $database Database name
     * @param int $port Port number
     * @param string $charset Character set
     * @return bool True on success, false on failure
     */
    public function connect($dsn_or_host, $user = '', $password = '', $database = '', $port = 3306, $charset = 'utf8mb4') {
        $this->clearError();
        @mysqli_report(MYSQLI_REPORT_OFF);
        
        $host = $dsn_or_host;

        // Parse DSN if it looks like a DSN string e.g., "host=localhost;port=3306;dbname=test_db;charset=utf8mb4"
        if (strpos($dsn_or_host, '=') !== false) {
            $parts = explode(';', $dsn_or_host);
            foreach ($parts as $part) {
                if (empty($part)) continue;
                $kv = explode('=', $part, 2);
                if (count($kv) === 2) {
                    $key = strtolower(trim($kv[0]));
                    $val = trim($kv[1]);
                    if ($key === 'host') $host = $val;
                    elseif ($key === 'port') $port = (int)$val;
                    elseif ($key === 'dbname' || $key === 'database') $database = $val;
                    elseif ($key === 'charset') $charset = $val;
                }
            }
        }

        try {
            // Establish mysqli connection wrapped in try-catch to prevent uncaught exceptions in PHP 8.1+
            $this->conn = @mysqli_connect($host, $user, $password, $database, $port);
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            $this->errorCode = $e->getCode() ?: 2002;
            return false;
        } catch (Throwable $t) {
            $this->error = $t->getMessage();
            $this->errorCode = $t->getCode() ?: 2002;
            return false;
        }

        if (!$this->conn) {
            $this->error = mysqli_connect_error();
            $this->errorCode = mysqli_connect_errno();
            return false;
        }

        if ($charset) {
            try {
                @mysqli_set_charset($this->conn, $charset);
            } catch (Throwable $t) {
                // Ignore charset warnings/exceptions during connection
            }
        }

        return true;
    }

    /**
     * Close connection (mimics odbc_close)
     */
    public function close() {
        if ($this->conn) {
            @mysqli_close($this->conn);
            $this->conn = null;
        }
    }

    /**
     * Execute SQL query directly (mimics odbc_exec)
     * 
     * @param string $query_string
     * @return MysqlOdbcResult|bool Returns MysqlOdbcResult object for SELECT-like queries, bool for write queries, or false on error
     */
    public function exec($query_string) {
        $this->clearError();
        if (!$this->conn) {
            $this->error = "No active database connection.";
            $this->errorCode = 0;
            return false;
        }

        try {
            $result = @mysqli_query($this->conn, $query_string);
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            $this->errorCode = $e->getCode();
            return false;
        } catch (Throwable $t) {
            $this->error = $t->getMessage();
            $this->errorCode = $t->getCode();
            return false;
        }

        if ($result === false) {
            $this->setError();
            return false;
        }

        if ($result instanceof mysqli_result) {
            return new MysqlOdbcResult($result);
        }

        return true;
    }

    /**
     * Prepare a statement for execution (mimics odbc_prepare)
     * 
     * @param string $query_string SQL query with '?' placeholders
     * @return MysqlOdbcStatement|bool Returns statement object or false on error
     */
    public function prepare($query_string) {
        $this->clearError();
        if (!$this->conn) {
            $this->error = "No active database connection.";
            $this->errorCode = 0;
            return false;
        }

        try {
            $stmt = @mysqli_prepare($this->conn, $query_string);
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            $this->errorCode = $e->getCode();
            return false;
        } catch (Throwable $t) {
            $this->error = $t->getMessage();
            $this->errorCode = $t->getCode();
            return false;
        }

        if ($stmt === false) {
            $this->setError();
            return false;
        }

        return new MysqlOdbcStatement($stmt);
    }

    /**
     * Execute a prepared statement (mimics odbc_execute)
     * 
     * @param MysqlOdbcStatement $stmt
     * @param array $parameters Parameters array
     * @return MysqlOdbcResult|bool Returns MysqlOdbcResult object for SELECT-like queries, bool for write queries, or false on error
     */
    public function execute($stmt, $parameters = array()) {
        $this->clearError();
        if (!$stmt || !($stmt instanceof MysqlOdbcStatement)) {
            $this->error = "Invalid statement object.";
            $this->errorCode = 0;
            return false;
        }

        $res = $stmt->execute($parameters);
        if ($res === false) {
            $this->error = $stmt->error();
            $this->errorCode = $stmt->errno();
            return false;
        }

        return $res;
    }

    /**
     * Fetch a row from result set (mimics odbc_fetch_row)
     * 
     * @param MysqlOdbcResult $result
     * @param int|null $row_number Row index (1-based index)
     * @return bool True if a row was fetched, false if no more rows or error
     */
    public function fetch_row($result, $row_number = null) {
        if (!$result || !($result instanceof MysqlOdbcResult)) {
            return false;
        }
        return $result->fetchRow($row_number);
    }

    /**
     * Fetch row as associative array (mimics odbc_fetch_array)
     * 
     * @param MysqlOdbcResult $result
     * @param int|null $row_number Row index (1-based index)
     * @return array|false Returns row associative array, or false on error/end
     */
    public function fetch_array($result, $row_number = null) {
        if (!$result || !($result instanceof MysqlOdbcResult)) {
            return false;
        }
        return $result->fetchArray($row_number);
    }

    /**
     * Fetch all rows (helper method)
     * 
     * @param MysqlOdbcResult $result
     * @return array Array of associative arrays
     */
    public function fetch_all($result) {
        if (!$result || !($result instanceof MysqlOdbcResult)) {
            return array();
        }
        $rows = array();
        while ($row = $result->fetchArray()) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Get a single field value from current fetched row (mimics odbc_result)
     * 
     * @param MysqlOdbcResult $result
     * @param int|string $field Column index (1-based) or column name string
     * @return mixed Field value, or null if not found
     */
    public function result($result, $field) {
        if (!$result || !($result instanceof MysqlOdbcResult)) {
            return null;
        }
        return $result->getResultVal($field);
    }

    /**
     * Get number of rows in result set (mimics odbc_num_rows)
     * 
     * @param MysqlOdbcResult|bool $result
     * @return int Number of rows, or -1/0 on error
     */
    public function num_rows($result) {
        if (!$result || !($result instanceof MysqlOdbcResult)) {
            return 0;
        }
        return $result->numRows();
    }

    /**
     * Get number of fields/columns in result set (mimics odbc_num_fields)
     * 
     * @param MysqlOdbcResult $result
     * @return int Number of fields, or 0 on error
     */
    public function num_fields($result) {
        if (!$result || !($result instanceof MysqlOdbcResult)) {
            return 0;
        }
        return $result->numFields();
    }

    /**
     * Get name of field at column index (mimics odbc_field_name)
     * 
     * @param MysqlOdbcResult $result
     * @param int $field_number Column index (1-based)
     * @return string|false Column name, or false on error
     */
    public function field_name($result, $field_number) {
        if (!$result || !($result instanceof MysqlOdbcResult)) {
            return false;
        }
        return $result->fieldName($field_number);
    }

    /**
     * Enable/disable auto-committing (mimics odbc_autocommit)
     * 
     * @param bool $status
     * @return bool
     */
    public function autocommit($status = false) {
        $this->clearError();
        if (!$this->conn) return false;
        try {
            $res = @mysqli_autocommit($this->conn, $status);
        } catch (Throwable $t) {
            $this->error = $t->getMessage();
            $this->errorCode = $t->getCode();
            return false;
        }
        if (!$res) $this->setError();
        return $res;
    }

    /**
     * Commit active transaction (mimics odbc_commit)
     * 
     * @return bool
     */
    public function commit() {
        $this->clearError();
        if (!$this->conn) return false;
        try {
            $res = @mysqli_commit($this->conn);
        } catch (Throwable $t) {
            $this->error = $t->getMessage();
            $this->errorCode = $t->getCode();
            return false;
        }
        if (!$res) $this->setError();
        return $res;
    }

    /**
     * Rollback active transaction (mimics odbc_rollback)
     * 
     * @return bool
     */
    public function rollback() {
        $this->clearError();
        if (!$this->conn) return false;
        try {
            $res = @mysqli_rollback($this->conn);
        } catch (Throwable $t) {
            $this->error = $t->getMessage();
            $this->errorCode = $t->getCode();
            return false;
        }
        if (!$res) $this->setError();
        return $res;
    }

    /**
     * Get last error message (mimics odbc_errormsg)
     * 
     * @return string
     */
    public function errormsg() {
        return $this->error;
    }

    /**
     * Get last error code (mimics odbc_error)
     * 
     * @return int
     */
    public function errorcode() {
        return $this->errorCode;
    }

    /**
     * Get underlying raw mysqli connection object
     * 
     * @return mysqli|null
     */
    public function getConnection() {
        return $this->conn;
    }

    /**
     * Get last inserted ID
     * 
     * @return int|string
     */
    public function insert_id() {
        if (!$this->conn) return 0;
        return mysqli_insert_id($this->conn);
    }

    /**
     * Get number of affected rows in last query
     * 
     * @return int
     */
    public function affected_rows() {
        if (!$this->conn) return -1;
        return mysqli_affected_rows($this->conn);
    }

    /**
     * Helper: Sets error message and code from connection
     */
    private function setError() {
        if ($this->conn) {
            $this->error = mysqli_error($this->conn);
            $this->errorCode = mysqli_errno($this->conn);
        }
    }

    /**
     * Helper: Clears connection errors
     */
    private function clearError() {
        $this->error = '';
        $this->errorCode = 0;
    }
}

/**
 * MysqlOdbcResult Class
 * Wraps a mysqli_result to mimic cursor fetching behaviour.
 */
class MysqlOdbcResult {
    private $result;
    private $currentRow = null;
    private $rowCounter = 0;

    /**
     * Constructor
     * 
     * @param mysqli_result $mysqli_result
     */
    public function __construct($mysqli_result) {
        $this->result = $mysqli_result;
    }

    /**
     * Destructor
     */
    public function __destruct() {
        if ($this->result instanceof mysqli_result) {
            @mysqli_free_result($this->result);
        }
    }

    /**
     * Fetch a row (mimics odbc_fetch_row)
     * 
     * @param int|null $row_number 1-based row index
     * @return bool
     */
    public function fetchRow($row_number = null) {
        try {
            if ($row_number !== null) {
                $idx = $row_number - 1;
                if ($idx >= 0 && $idx < $this->numRows()) {
                    if (@mysqli_data_seek($this->result, $idx)) {
                        $this->rowCounter = $idx;
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            }

            // Fetch as MYSQLI_BOTH to support both integer indices (1-based in odbc_result) and column names
            $row = @mysqli_fetch_array($this->result, MYSQLI_BOTH);
            if ($row) {
                $this->currentRow = $row;
                $this->rowCounter++;
                return true;
            }
        } catch (Throwable $t) {
            // Ignore/handle fetching exceptions gracefully
        }

        $this->currentRow = null;
        return false;
    }

    /**
     * Fetch a row as associative array (mimics odbc_fetch_array)
     * 
     * @param int|null $row_number 1-based row index
     * @return array|false
     */
    public function fetchArray($row_number = null) {
        try {
            if ($row_number !== null) {
                $idx = $row_number - 1;
                if ($idx >= 0 && $idx < $this->numRows()) {
                    if (@mysqli_data_seek($this->result, $idx)) {
                        $this->rowCounter = $idx;
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            }

            $row = @mysqli_fetch_array($this->result, MYSQLI_ASSOC);
            if ($row) {
                $this->currentRow = $row;
                $this->rowCounter++;
                return $row;
            }
        } catch (Throwable $t) {
            // Graceful error handling
        }

        $this->currentRow = null;
        return false;
    }

    /**
     * Get result value (mimics odbc_result)
     * 
     * @param int|string $field 1-based index or field name string
     * @return mixed|null
     */
    public function getResultVal($field) {
        if ($this->currentRow === null) {
            return null;
        }

        if (is_int($field) || is_numeric($field)) {
            // ODBC uses 1-based indexing
            $idx = (int)$field - 1;
            if (isset($this->currentRow[$idx])) {
                return $this->currentRow[$idx];
            }
            
            // Fallback: If currentRow is associative, map via array_values
            $vals = array_values($this->currentRow);
            
            // Extract values of string columns in order
            $assocKeys = array_filter(array_keys($this->currentRow), 'is_string');
            $orderedVals = array();
            foreach ($assocKeys as $key) {
                $orderedVals[] = $this->currentRow[$key];
            }
            
            if (isset($orderedVals[$idx])) {
                return $orderedVals[$idx];
            }
        } else {
            if (isset($this->currentRow[$field])) {
                return $this->currentRow[$field];
            }
        }

        return null;
    }

    /**
     * Number of rows in result set (mimics odbc_num_rows)
     * 
     * @return int
     */
    public function numRows() {
        try {
            return @mysqli_num_rows($this->result);
        } catch (Throwable $t) {
            return 0;
        }
    }

    /**
     * Number of fields in result set (mimics odbc_num_fields)
     * 
     * @return int
     */
    public function numFields() {
        try {
            return @mysqli_num_fields($this->result);
        } catch (Throwable $t) {
            return 0;
        }
    }

    /**
     * Name of field at column index (mimics odbc_field_name)
     * 
     * @param int $field_number 1-based index
     * @return string|false
     */
    public function fieldName($field_number) {
        try {
            $idx = $field_number - 1;
            $info = @mysqli_fetch_field_direct($this->result, $idx);
            return $info ? $info->name : false;
        } catch (Throwable $t) {
            return false;
        }
    }

    /**
     * Get underlying mysqli_result object
     * 
     * @return mysqli_result
     */
    public function getRawResult() {
        return $this->result;
    }
}

/**
 * MysqlOdbcStatement Class
 * Wraps a mysqli_stmt to support prepared statements.
 */
class MysqlOdbcStatement {
    private $stmt;
    private $error = '';
    private $errno = 0;

    /**
     * Constructor
     * 
     * @param mysqli_stmt $mysqli_stmt
     */
    public function __construct($mysqli_stmt) {
        $this->stmt = $mysqli_stmt;
    }

    /**
     * Destructor
     */
    public function __destruct() {
        if ($this->stmt instanceof mysqli_stmt) {
            @mysqli_stmt_close($this->stmt);
        }
    }

    /**
     * Execute statement with parameters (mimics odbc_execute)
     * 
     * @param array $parameters Parameter values matching ? placeholders
     * @return MysqlOdbcResult|bool Returns MysqlOdbcResult on SELECT success, true on DML success, false on failure
     */
    public function execute($parameters = array()) {
        $this->error = '';
        $this->errno = 0;

        try {
            if (!empty($parameters)) {
                $types = '';
                $bindParams = array();

                foreach ($parameters as $param) {
                    if (is_int($param)) {
                        $types .= 'i';
                    } elseif (is_double($param)) {
                        $types .= 'd';
                    } elseif (is_string($param)) {
                        $types .= 's';
                    } else {
                        $types .= 'b'; // blob
                    }
                }

                // Create reference array for mysqli_stmt_bind_param
                $bindParams[] = &$types;
                $count = count($parameters);
                for ($i = 0; $i < $count; $i++) {
                    $bindParams[] = &$parameters[$i];
                }

                $bind_res = call_user_func_array(array($this->stmt, 'bind_param'), $bindParams);
                if ($bind_res === false) {
                    $this->error = $this->stmt->error;
                    $this->errno = $this->stmt->errno;
                    return false;
                }
            }

            $exec_res = @mysqli_stmt_execute($this->stmt);
            if ($exec_res === false) {
                $this->error = $this->stmt->error;
                $this->errno = $this->stmt->errno;
                return false;
            }

            // Check for result set metadata to determine if SELECT query
            $meta = @mysqli_stmt_result_metadata($this->stmt);
            if ($meta) {
                $result = @mysqli_stmt_get_result($this->stmt);
                if ($result) {
                    return new MysqlOdbcResult($result);
                } else {
                    $this->error = $this->stmt->error;
                    $this->errno = $this->stmt->errno;
                    return false;
                }
            }
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            $this->errno = $e->getCode() ?: 500;
            return false;
        } catch (Throwable $t) {
            $this->error = $t->getMessage();
            $this->errno = $t->getCode() ?: 500;
            return false;
        }

        return true;
    }

    /**
     * Get statement error message
     * 
     * @return string
     */
    public function error() {
        return $this->error;
    }

    /**
     * Get statement error number
     * 
     * @return int
     */
    public function errno() {
        return $this->errno;
    }

    /**
     * Get underlying raw mysqli_stmt object
     * 
     * @return mysqli_stmt
     */
    public function getRawStmt() {
        return $this->stmt;
    }
}
