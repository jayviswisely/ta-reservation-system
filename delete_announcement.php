<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    header("HTTP/1.1 403 Forbidden");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['announcement_id'], $_POST['course_id'])) {
    $announcement_id = intval($_POST['announcement_id']);
    $course_id = intval($_POST['course_id']);
    
    // First delete reactions
    $conn->query("DELETE FROM announcement_reactions WHERE announcement_id = $announcement_id");
    
    // Then delete announcement
    $stmt = $conn->prepare("DELETE FROM announcements WHERE announcement_id = ? AND course_id = ?");
    $stmt->bind_param("ii", $announcement_id, $course_id);
    $stmt->execute();
    
    header("Location: course.php?course_id=" . $course_id);
    exit();
}
?>