<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role'] !== 'student') {
    header("HTTP/1.1 403 Forbidden");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['announcement_id'], $_POST['course_id'])) {
    $announcement_id = intval($_POST['announcement_id']);
    $user_id = $_SESSION['user_id'];
    $course_id = intval($_POST['course_id']);
    
    // Check if already reacted
    $check_stmt = $conn->prepare("SELECT 1 FROM announcement_reactions WHERE announcement_id = ? AND user_id = ?");
    $check_stmt->bind_param("ii", $announcement_id, $user_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        // Remove reaction
        $delete_stmt = $conn->prepare("DELETE FROM announcement_reactions WHERE announcement_id = ? AND user_id = ?");
        $delete_stmt->bind_param("ii", $announcement_id, $user_id);
        $delete_stmt->execute();
    } else {
        // Add reaction
        $insert_stmt = $conn->prepare("INSERT INTO announcement_reactions (announcement_id, user_id) VALUES (?, ?)");
        $insert_stmt->bind_param("ii", $announcement_id, $user_id);
        $insert_stmt->execute();
    }
    
    header("Location: course.php?course_id=" . $course_id);
    exit();
}
?>