<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 403 Forbidden");
    exit();
}

$user_id = $_SESSION['user_id'];
$is_ta = isset($_SESSION['role']) && $_SESSION['role'] === 'ta';
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

if (!isset($_GET['course_id']) || !is_numeric($_GET['course_id'])) {
    header("HTTP/1.1 400 Bad Request");
    exit();
}

$course_id = intval($_GET['course_id']);

$history_sql = "
    SELECT 
        a.appointment_id,
        a.student_id,
        s.date,
        s.time,
        su.full_name AS student_name,
        tu.full_name AS ta_name,
        tu.user_id AS ta_id,
        af.feedback_text,
        af.rating,
        af.created_at AS feedback_date
    FROM appointments a
    JOIN ta_schedule s ON a.schedule_id = s.schedule_id
    JOIN users su ON a.student_id = su.user_id
    JOIN users tu ON s.ta_id = tu.user_id
    LEFT JOIN appointment_feedback af ON a.appointment_id = af.appointment_id
    WHERE s.course_id = ? 
    AND (s.date < CURDATE() OR (s.date = CURDATE() AND s.time <= CURTIME()))
";

// Modify filtering based on user role
if ($is_admin) {
    // Admin sees all history - no additional conditions
} elseif ($is_ta) {
    $history_sql .= " AND s.ta_id = ?";
} else {
    $history_sql .= " AND a.student_id = ?";
}

$history_sql .= " ORDER BY s.date DESC, s.time DESC";

$stmt = $conn->prepare($history_sql);
if ($is_admin) {
    $stmt->bind_param("i", $course_id);
} elseif ($is_ta) {
    $stmt->bind_param("ii", $course_id, $user_id);
} else {
    $stmt->bind_param("ii", $course_id, $user_id);
}

$stmt->execute();
$history_result = $stmt->get_result();

if ($history_result->num_rows > 0) {
    while ($row = $history_result->fetch_assoc()) {
        $ta_you = ($row['ta_id'] == $user_id && $is_ta);
        $student_you = ($row['student_id'] == $user_id && !$is_ta && !$is_admin);

        echo "<li class='history-item'>";
        echo "<div class='appointment-info'>";
        echo "<strong>TA:</strong> " . htmlspecialchars($row['ta_name']) . ($ta_you ? " <strong>(You)</strong>" : "") .
             " | <strong>When:</strong> " . htmlspecialchars($row['date']) . " at " . htmlspecialchars($row['time']) .
             " | <strong>Student:</strong> " . htmlspecialchars($row['student_name']) . ($student_you ? " <strong>(You)</strong>" : "");
        echo "</div>";

        // Show feedback if exists
        if ($row['feedback_text']) {
            echo "<div class='feedback-display'>";
            echo "<strong>Feedback:</strong> " . htmlspecialchars($row['feedback_text']);
            if ($row['rating']) {
                echo " | <strong>Rating:</strong> ";
                for ($i = 1; $i <= 5; $i++) {
                    echo $i <= $row['rating'] ? "★" : "☆";
                }
            }
            echo "</div>";
        } elseif (!$is_admin && !$is_ta && $student_you) {
            // Show feedback form for students if no feedback exists
            echo "<div class='feedback-form-container'>";
            echo "<button class='small-button toggle-feedback-btn' onclick='toggleFeedbackForm(this, {$row['appointment_id']}, {$row['ta_id']})'>Leave Feedback</button>";
            echo "<form class='feedback-form' id='feedback-form-{$row['appointment_id']}' style='display:none;'>";
            echo "<input type='hidden' name='appointment_id' value='{$row['appointment_id']}'>";
            echo "<input type='hidden' name='ta_id' value='{$row['ta_id']}'>";
            echo "<textarea name='feedback_text' placeholder='Your feedback...' required></textarea>";
            echo "<div class='rating-container'>";
            echo "<span>Rating: </span>";
            for ($i = 1; $i <= 5; $i++) {
                echo "<label><input type='radio' name='rating' value='$i'>$i</label>";
            }
            echo "</div>";
            echo "<button type='button' class='small-button' onclick='submitFeedback(this)'>Submit</button>";
            echo "</form>";
            echo "</div>";
        }

        echo "</li>";
    }
} else {
    echo "<li>No past appointments found.</li>";
}
?>