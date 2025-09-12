<?php
/**
 * db.php
 * Database connection and helper functions for LMS
 */

// --- DATABASE CONFIGURATION ---
$DB_HOST = "localhost";   // Database server (usually localhost)
$DB_USER = "root";        // Database username
$DB_PASS = "";            // Database password
$DB_NAME = "lms_db";      // Database name

// --- CREATE CONNECTION ---
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

// --- CHECK CONNECTION ---
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// --- SET UTF-8 CHARACTER SET (important for international support) ---
$conn->set_charset("utf8mb4");

// ==========================
// DATABASE HELPER FUNCTIONS
// ==========================

/**
 * Run a prepared SELECT query
 * @param string $query
 * @param string $types - Parameter types (e.g., "si" for string, int)
 * @param array $params - Parameters array
 * @return array|null
 */
function db_select($query, $types = "", $params = []) {
    global $conn;

    // Prepare statement
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die("SQL Prepare Failed: (" . $conn->errno . ") " . $conn->error . " -> SQL: $query");
    }

    // Bind parameters only if types and params are provided
    if (!empty($types) && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $data;
}


/**
 * Run a prepared INSERT/UPDATE/DELETE query
 * @param string $query
 * @param string $types
 * @param array $params
 * @return bool|int - Insert ID if insert, true/false otherwise
 */
function db_execute($query, $types = "", $params = []) {
    global $conn;
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die("SQL Prepare Failed: (" . $conn->errno . ") " . $conn->error . " -> SQL: $query");
    }
    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }
    $success = $stmt->execute();

    if (!$success) {
        // Provide a more detailed error message for execution failures
        die("SQL Execute Failed: (" . $stmt->errno . ") " . $stmt->error . " -> SQL: $query");
    }
    $insert_id = $stmt->insert_id;
    $stmt->close();

    if ($insert_id > 0) {
        return $insert_id; // return insert ID for INSERT
    }
    return $success; // true/false for UPDATE/DELETE
}

/**
 * Escape string safely (use only if you can’t use prepared statements)
 * @param string $value
 * @return string
 */
function db_escape($value) {
    global $conn;
    return $conn->real_escape_string($value);
}

/**
 * Get a single setting value from the database.
 * @param string $key The setting key.
 * @param mixed $default The default value to return if not found.
 * @return mixed The setting value or default.
 */
function get_setting($key, $default = null) {
    $result = db_select("SELECT setting_value FROM settings WHERE setting_key = ?", 's', [$key]);
    if (!empty($result)) {
        // Unserialize if the value is a serialized array or object
        $value = $result[0]['setting_value'];
        return @unserialize($value) !== false ? unserialize($value) : $value;
    }
    return $default;
}

/**
 * Update or create a setting in the database.
 * @param string $key The setting key.
 * @param mixed $value The setting value.
 * @return bool True on success, false on failure.
 */
function update_setting($key, $value) {
    $value_to_store = is_array($value) || is_object($value) ? serialize($value) : $value;
    return db_execute("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)", "ss", [$key, $value_to_store]);
}

/**
 * Close database connection
 */
function db_close() {
    global $conn;
    $conn->close();
}
