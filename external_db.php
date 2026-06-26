<?php
/**
 * external_db.php
 * Connects to the SEPARATE school_records_db database.
 * This simulates connecting to the school's existing portal
 * database where fees and library records are already stored.
 *
 * In production, this would be replaced with the real
 * connection details of the school's actual database.
 */

function externalDb(): mysqli {
    static $conn;
    if (!$conn) {
        $conn = new mysqli('localhost', 'root', '', 'school_records_db');
        if ($conn->connect_error) {
            error_log('External DB connection failed: ' . $conn->connect_error);
            return null;
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}

/**
 * Check if a student has any outstanding fees.
 * Returns an array of owing fee records (empty array if none).
 */
function checkOutstandingFees(string $matricNumber): array {
    $db = externalDb();
    if (!$db) return [];

    $stmt = $db->prepare(
        "SELECT fee_type, amount, session
         FROM outstanding_fees
         WHERE matric_number = ? AND status = 'owing'"
    );
    $stmt->bind_param('s', $matricNumber);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}

/**
 * Check if a student has any unreturned library books.
 * Returns an array of borrowed book records (empty array if none).
 */
function checkLibraryRecords(string $matricNumber): array {
    $db = externalDb();
    if (!$db) return [];

    $stmt = $db->prepare(
        "SELECT book_title, book_code, date_borrowed, due_date
         FROM library_records
         WHERE matric_number = ? AND status = 'borrowed'"
    );
    $stmt->bind_param('s', $matricNumber);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}