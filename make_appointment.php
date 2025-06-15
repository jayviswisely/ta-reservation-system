<?php
session_start();
include 'db_connect.php';

// Check if student is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// Get TA and course from URL
if (!isset($_GET['ta_id']) || !isset($_GET['course_id'])) {
    echo "Missing TA or course ID.";
    exit();
}

$ta_id = intval($_GET['ta_id']);
$course_id = intval($_GET['course_id']);

// Handle booking POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule_id'])) {
    $schedule_id = intval($_POST['schedule_id']);

    // 1. Insert appointment
    $stmt = $conn->prepare("INSERT INTO appointments (student_id, schedule_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $student_id, $schedule_id);
    if ($stmt->execute()) {
        // 2. Mark schedule as booked
        $update_stmt = $conn->prepare("UPDATE ta_schedule SET is_booked = 1 WHERE schedule_id = ?");
        $update_stmt->bind_param("i", $schedule_id);
        $update_stmt->execute();

        $success_message = "Appointment booked successfully!";
    } else {
        $error_message = "Failed to book appointment.";
    }
}

// Get TA name
$ta_name_stmt = $conn->prepare("SELECT full_name FROM users WHERE user_id = ?");
$ta_name_stmt->bind_param("i", $ta_id);
$ta_name_stmt->execute();
$ta_name_result = $ta_name_stmt->get_result();
$ta_name = $ta_name_result->fetch_assoc()['full_name'] ?? 'TA';

// Fetch available slots
$sql = "SELECT schedule_id, date, time FROM ta_schedule 
        WHERE ta_id = ? AND course_id = ? AND is_booked = 0 
        AND (date > CURDATE() OR (date = CURDATE() AND time > CURTIME()))
        ORDER BY date, time";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $ta_id, $course_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Make Appointment with <?= htmlspecialchars($ta_name) ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <nav>
        <img src="./logo_white.png">
        <a href="./index.php">
            <img src="./logout.png">
        </a>
    </nav>
    <div class="dashboard-body">
        <div class="appointments-section">
            <h2>Book Appointment with <?= htmlspecialchars($ta_name) ?></h2>

            <?php if (!empty($success_message)): ?>
                <p style="color: green; font-weight: bold;"><?= $success_message ?></p>
            <?php elseif (!empty($error_message)): ?>
                <p style="color: red; font-weight: bold;"><?= $error_message ?></p>
            <?php endif; ?>

            <?php if ($result->num_rows > 0): ?>
                <form method="POST">
                    <ul class="appointments-list">
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <li>
                                <strong><?= htmlspecialchars($row['date']) ?></strong> at <?= htmlspecialchars($row['time']) ?>
                                <button class="small-button inline-form" type="submit" name="schedule_id" value="<?= $row['schedule_id'] ?>">Book</button>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                </form>
            <?php else: ?>
                <p>No available slots.</p>
            <?php endif; ?>

            <br>
            <a class="page-button" href="course.php?course_id=<?= $course_id ?>">← Back to Course</a>
        </div>
    </div>
</body>
</html>
