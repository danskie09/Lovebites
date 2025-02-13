<?php
session_start();

// Include necessary files
require_once 'config/database.php'; // Database connection file
require_once 'includes/auth.php';  // Authorization functions (e.g., checkAdminAccess)

// Check if the user has admin access
checkAdminAccess();

// Database connection and StaffManager initialization
$db = new Database();
$conn = $db->getConnection();
$staffManager = new StaffManager($conn);

/**
 * Function to process adding a new staff member
 */
function processAddStaff($staffManager, $conn, $postData, $currentUserId) {
    $username = trim($postData['username']);
    $email = filter_var($postData['email'], FILTER_VALIDATE_EMAIL);
    $password = trim($postData['password']);
    $full_name = trim($postData['full_name']);
    $role = trim($postData['role']);

    // Validate input
    if (!$username || !$email || !$password || !$full_name || !$role) {
        return ["error" => "All fields are required and must be valid."];
    }

    // You should hash the password before saving it
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Add staff using the StaffManager class
    $result = $staffManager->addStaff($username, $email, $hashedPassword, $full_name, $role);

    if ($result) {
        logActivity($conn, $currentUserId, "Added new staff member: $username");
        return ["success" => "Staff member added successfully!"];
    } else {
        return ["error" => "Failed to add staff member. Please check your input."];
    }
}

// Handle the form submission for adding staff
if (isset($_POST['addStaff'])) {
    // Call the function to process adding staff
    $response = processAddStaff($staffManager, $conn, $_POST, $_SESSION['user_id']);

    // Set success or error messages in the session
    if (isset($response['success'])) {
        $_SESSION['success'] = $response['success'];
    } else {
        $_SESSION['error'] = $response['error'];
    }

    // Redirect back to the staff management page
    header("Location: manage-staff.php");
    exit();
}
?>
