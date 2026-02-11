<?php
session_start();

// Block unauthorized users
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    http_response_code(403);
    die('Forbidden');
}

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method Not Allowed');
}

// Get the posted data
$reg_id = $_POST['reg_id'] ?? null;
$column = $_POST['column'] ?? null;
$value = $_POST['value'] ?? null;

// Validate required fields
if (!$reg_id || !$column) {
    http_response_code(400);
    die('Missing required fields');
}

// Validate column name to prevent SQL injection
$allowed_columns = ['current_grade', 'payment_status', 'payment_amount'];
if (!in_array($column, $allowed_columns)) {
    http_response_code(400);
    die('Invalid column');
}

// Validate values based on column type
if ($column === 'current_grade') {
    if ($value !== '' && (!is_numeric($value) || $value < 1 || $value > 13)) {
        http_response_code(400);
        die('Current grade must be between 1 and 13 or empty');
    }
    // Allow empty value for current_grade since it's optional
    $value = $value === '' ? null : (int)$value;
} elseif ($column === 'payment_status') {
    $valid_statuses = ['pending', 'completed', 'failed', 'refunded'];
    if (!in_array($value, $valid_statuses)) {
        http_response_code(400);
        die('Invalid payment status');
    }
} elseif ($column === 'payment_amount') {
    if (!is_numeric($value) || $value < 0) {
        http_response_code(400);
        die('Payment amount must be a positive number');
    }
    $value = (float)$value;
}

try {
    require 'db_configuration.php';
    
    // Create connection
    $conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Prepare the update statement
    $sql = "UPDATE registrations SET $column = ? WHERE Reg_Id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    // Bind parameters based on data type
    if ($column === 'current_grade' && $value === null) {
        $stmt->bind_param("si", $null_value, $reg_id);
        $null_value = null;
    } elseif ($column === 'current_grade') {
        $stmt->bind_param("ii", $value, $reg_id);
    } elseif ($column === 'payment_amount') {
        $stmt->bind_param("di", $value, $reg_id);
    } else {
        $stmt->bind_param("si", $value, $reg_id);
    }
    
    // Execute the statement
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Updated successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Registration not found']);
        }
    } else {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>