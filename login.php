<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $selected_role = $_POST['role'] ?? '';

    // Fetch user info
    $stmt = $conn->prepare("SELECT user_id, username, full_name, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($user = $res->fetch_assoc()) {
        if ($password === $user['password']) {
            $user_id = $user['user_id'];

            // Now check if the selected role matches any assigned role
            $role_stmt = $conn->prepare("
                SELECT roles.role_name 
                FROM user_roles 
                JOIN roles ON user_roles.role_id = roles.role_id 
                WHERE user_roles.user_id = ? AND roles.role_name = ?
            ");
            $role_stmt->bind_param("is", $user_id, $selected_role);
            $role_stmt->execute();
            $role_res = $role_stmt->get_result();

            if ($role_res->fetch_assoc()) {
                // Success: user has that role
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $selected_role;
                $_SESSION['full_name'] = $user['full_name'];


                // Redirect based on selected role
                switch ($selected_role) {
                    case 'student': header("Location: student.php"); break;
                    case 'ta': header("Location: ta.php"); break;
                    case 'admin': header("Location: admin.php"); break;
                }
                exit;
            } else {
                $_SESSION['error'] = "This account is not assigned the role: $selected_role";
                header("Location: index.php");
                exit;
            }
        } else {
            $_SESSION['error'] = "Incorrect password.";
            header("Location: index.php");
            exit;
        }
    } else {
        $_SESSION['error'] = "User not found.";
        header("Location: index.php");
        exit;
    }
}
?>
