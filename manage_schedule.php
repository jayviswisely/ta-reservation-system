<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'] ?? 'TA';

if (!isset($_GET['course_id']) || !is_numeric($_GET['course_id'])) {
    echo "Invalid course ID.";
    exit();
}

$course_id = intval($_GET['course_id']);

// Check if this TA is assigned to this course
$check_sql = "SELECT 1 FROM course_tas WHERE course_id = ? AND ta_id = ?";
$stmt = $conn->prepare($check_sql);
$stmt->bind_param("ii", $course_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Access denied. You are not a TA for this course.";
    exit();
}

// Handle adding new slot
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['date'], $_POST['time'])) {
    $date = $_POST['date'];
    $time = $_POST['time'];

    $insert_sql = "INSERT INTO ta_schedule (ta_id, course_id, date, time) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("iiss", $user_id, $course_id, $date, $time);
    $stmt->execute();
    header("Location: manage_schedule.php?course_id=$course_id");
    exit();
}

// Handle deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $schedule_id = intval($_GET['delete']);
    $delete_sql = "DELETE FROM ta_schedule WHERE schedule_id = ? AND ta_id = ? AND course_id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("iii", $schedule_id, $user_id, $course_id);
    $stmt->execute();
    header("Location: manage_schedule.php?course_id=$course_id");
    exit();
}

// Fetch schedule
$schedule_sql = "SELECT schedule_id, date, time, is_booked FROM ta_schedule WHERE ta_id = ? AND course_id = ? ORDER BY date, time";
$stmt = $conn->prepare($schedule_sql);
$stmt->bind_param("ii", $user_id, $course_id);
$stmt->execute();
$schedule_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Schedule - <?= htmlspecialchars($full_name) ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <nav>
        <img src="./assets/logo_white.png">
        <a href="./index.php">
            <img src="./assets/logout.png">
        </a>
    </nav>

    <div class="dashboard-body">
        <div class="appointments-section">
            <h2>Manage Your Schedule</h2>

            <p><a class="page-button" href="course.php?course_id=<?= $course_id ?>">← Back to Course</a></p>

            <h3>Add New Available Slot</h3>
            <form method="post" class="new-announcement-form">
                <div class="form-group">
                    <label for="date">Date</label>
                    <input type="date" name="date" required>
                </div>
                <div class="form-group">
                    <label for="time">Time</label>
                    <input type="time" name="time" required>
                </div>
                <button type="submit" class="small-button">Add Slot</button>
            </form>

            <h3>Existing Schedule Slots</h3>
            <?php if ($schedule_result->num_rows > 0): ?>
                <ul class="appointments-list">
                    <?php while ($row = $schedule_result->fetch_assoc()): ?>
                        <li>
                            <?= htmlspecialchars($row['date']) ?> at <?= htmlspecialchars($row['time']) ?> -
                            <?= $row['is_booked']
                                ? "<strong style='color:red;'>Booked</strong>"
                                : "<span style='color:green;'>Available</span>" ?>

                            <?php if (!$row['is_booked']): ?>
                                <a class="small-button inline-form"
                                   href="manage_schedule.php?course_id=<?= $course_id ?>&delete=<?= $row['schedule_id'] ?>"
                                   onclick="return confirm('Are you sure you want to delete this slot?')">
                                    Delete
                                </a>
                            <?php endif; ?>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p>No schedule slots yet.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
