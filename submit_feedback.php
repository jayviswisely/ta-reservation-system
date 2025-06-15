<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("HTTP/1.1 403 Forbidden");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("HTTP/1.1 405 Method Not Allowed");
    exit();
}

$student_id = $_SESSION['user_id'];
$appointment_id = intval($_POST['appointment_id']);
$ta_id = intval($_POST['ta_id']);
$feedback_text = $_POST['feedback_text'] ?? '';
$rating = isset($_POST['rating']) ? intval($_POST['rating']) : null;

// Validate rating
if ($rating !== null && ($rating < 1 || $rating > 5)) {
    header("HTTP/1.1 400 Bad Request");
    echo "Invalid rating value";
    exit();
}

// Verify the appointment belongs to this student
$verify_sql = "SELECT 1 FROM appointments WHERE appointment_id = ? AND student_id = ?";
$stmt = $conn->prepare($verify_sql);
$stmt->bind_param("ii", $appointment_id, $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("HTTP/1.1 403 Forbidden");
    echo "You can only leave feedback for your own appointments";
    exit();
}

// Insert or update feedback
$feedback_sql = "INSERT INTO appointment_feedback 
                (appointment_id, student_id, ta_id, feedback_text, rating) 
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                feedback_text = VALUES(feedback_text), 
                rating = VALUES(rating)";

$stmt = $conn->prepare($feedback_sql);
$stmt->bind_param("iiisi", $appointment_id, $student_id, $ta_id, $feedback_text, $rating);

if ($stmt->execute()) {
    echo "Feedback submitted successfully";
} else {
    header("HTTP/1.1 500 Internal Server Error");
    echo "Failed to submit feedback";
}
?>