<?php
// verify_security_key.php
include 'config.php';
session_start();

header('Content-Type: application/json');

// Get the raw POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($_SESSION['user_id']) || !isset($data['security_key'])) {
    echo json_encode(['success' => false]);
    exit();
}

$user_id = $_SESSION['user_id'];
$entered_key = trim($data['security_key']);

// Fetch the stored safety key
$query = "SELECT safety_key FROM user_form WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($stored_key);
$stmt->fetch();
$stmt->close();

// Verify the entered key
// If safety_key is hashed, use password_verify. Otherwise, use a simple comparison.
if ($stored_key === $entered_key) { // Replace with password_verify($entered_key, $stored_key) if hashed
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>
