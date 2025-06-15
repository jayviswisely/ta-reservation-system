<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? '';
$is_ta = false;

$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

// Get course info
$course_stmt = $conn->prepare("
    SELECT c.course_name, c.course_code, u.full_name AS professor_name
    FROM courses c
    JOIN users u ON c.admin_id = u.user_id
    WHERE c.course_id = ?
");
$course_stmt->bind_param("i", $course_id);
$course_stmt->execute();
$course_result = $course_stmt->get_result();

if ($course_result->num_rows === 0) {
    echo "Course not found.";
    exit();
}
$course = $course_result->fetch_assoc();

// Check if current user is a TA
$ta_check_stmt = $conn->prepare("SELECT 1 FROM course_tas WHERE course_id = ? AND ta_id = ?");
$ta_check_stmt->bind_param("ii", $course_id, $user_id);
$ta_check_stmt->execute();
$is_ta = $ta_check_stmt->get_result()->num_rows > 0;

// Handle new announcement post (admin only)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['new_announcement']) && $role === 'admin') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';

    if ($title && $content) {
        $insert_stmt = $conn->prepare("
            INSERT INTO announcements (course_id, author_id, title, content)
            VALUES (?, ?, ?, ?)
        ");
        $insert_stmt->bind_param("iiss", $course_id, $user_id, $title, $content);
        $insert_stmt->execute();
        header("Location: course.php?course_id=" . $course_id);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($course['course_name']) ?> - <?= htmlspecialchars($course['course_code']) ?></title>
    <link rel="stylesheet" href="./styles.css">
    <link rel="shortcut icon" href="logo_red.png" type="image/x-icon">
    <style>
        .announcement-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .delete-button {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #80141c;
            padding: 0 10px;
        }
        .delete-button:hover {
            color: #c03434;
        }
        .announcement-reactions {
            margin-top: 10px;
        }
        .reaction-button {
            background: none;
            border: 1px solid #ddd;
            border-radius: 20px;
            padding: 5px 10px;
            cursor: pointer;
            font-size: 14px;
        }
        .reaction-button:hover {
            background-color: #f0f0f0;
        }
        .reaction-button.reacted {
            background-color: #e3f2fd;
            border-color: #bbdefb;
        }
        .reaction-count {
            margin-left: 5px;
        }
    </style>
    <script>
        function showAppointmentHistory() {
            const modal = document.getElementById('historyModal');
            modal.style.display = 'block';
            
            fetch('get_history.php?course_id=<?= $course_id ?>')
                .then(response => response.text())
                .then(data => {
                    document.getElementById('historyContent').innerHTML = data;
                })
                .catch(error => {
                    document.getElementById('historyContent').innerHTML = 
                        '<p>Error loading history. Please try again.</p>';
                    console.error('Error:', error);
                });
        }
        
        function closeModal() {
            document.getElementById('historyModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('historyModal');
            if (event.target == modal) {
                closeModal();
            }
        }
        
        function toggleFeedbackForm(button, appointmentId, taId) {
            const form = document.getElementById(`feedback-form-${appointmentId}`);
            if (form.style.display === 'none') {
                form.style.display = 'block';
                button.textContent = 'Cancel';
            } else {
                form.style.display = 'none';
                button.textContent = 'Leave Feedback';
            }
        }

        function submitFeedback(button) {
            const form = button.closest('form');
            const formData = new FormData(form);
            
            fetch('submit_feedback.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(response.statusText);
                }
                return response.text();
            })
            .then(message => {
                alert(message);
                showAppointmentHistory();
            })
            .catch(error => {
                alert('Error submitting feedback: ' + error.message);
            });
        }
    </script>
</head>
<body>
    <nav>
        <img src="logo_white.png">
        <a href="./index.php">
            <img src="./logout.png">
        </a>
    </nav>

    <div class="dashboard-body">
        <div class="course-header">
            <h1><?= htmlspecialchars($course['course_code']) ?> - <?= htmlspecialchars($course['course_name']) ?></h1>
            <h2>Professor: <?= htmlspecialchars($course['professor_name']) ?></h2>
        </div>

        <!-- Announcements -->
        <div class="announcements-section">
            <h2>Announcements</h2>
            <?php if ($role === 'admin'): ?>
                <form method="POST" class="new-announcement-form">
                    <div class="form-group">
                        <input type="text" name="title" placeholder="Title" required>
                    </div>
                    <div class="form-group">
                        <textarea name="content" rows="4" placeholder="Content" required></textarea>
                    </div>
                    <button type="submit" name="new_announcement" class="login-button">Post Announcement</button>
                </form>
            <?php endif; ?>

            <div class="announcements-list">
                <?php
                $ann_stmt = $conn->prepare("
                    SELECT a.announcement_id, a.title, a.content, a.created_at, u.full_name AS author
                    FROM announcements a
                    JOIN users u ON a.author_id = u.user_id
                    WHERE a.course_id = ?
                    ORDER BY a.created_at DESC
                ");
                $ann_stmt->bind_param("i", $course_id);
                $ann_stmt->execute();
                $ann_result = $ann_stmt->get_result();

                if ($ann_result->num_rows > 0):
                    while ($a = $ann_result->fetch_assoc()): ?>
                    <div class="announcement-card">
                        <div class="announcement-header">
                            <h4><?= htmlspecialchars($a['title']) ?></h4>
                            <?php if ($role === 'admin'): ?>
                                <form method="POST" action="delete_announcement.php" class="delete-announcement-form">
                                    <input type="hidden" name="announcement_id" value="<?= $a['announcement_id'] ?>">
                                    <input type="hidden" name="course_id" value="<?= $course_id ?>">
                                    <button type="submit" class="delete-button" onclick="return confirm('Are you sure you want to delete this announcement?')">×</button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <div class="announcement-meta">
                            <span class="author">Posted by <?= htmlspecialchars($a['author']) ?></span>
                            <span class="date">on <?= date('M j, Y g:i A', strtotime($a['created_at'])) ?></span>
                        </div>
                        <div class="announcement-content">
                            <p><?= nl2br(htmlspecialchars($a['content'])) ?></p>
                        </div>
                        <div class="announcement-reactions">
                            <?php
                            $reacted = false;
                            if (isset($_SESSION['user_id']) && $role === 'student') {
                                $react_stmt = $conn->prepare("SELECT 1 FROM announcement_reactions WHERE announcement_id = ? AND user_id = ?");
                                $react_stmt->bind_param("ii", $a['announcement_id'], $_SESSION['user_id']);
                                $react_stmt->execute();
                                $reacted = $react_stmt->get_result()->num_rows > 0;
                            }
                            
                            $count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM announcement_reactions WHERE announcement_id = ?");
                            $count_stmt->bind_param("i", $a['announcement_id']);
                            $count_stmt->execute();
                            $count = $count_stmt->get_result()->fetch_assoc()['count'];
                            ?>
                            <form method="POST" action="toggle_reaction.php" class="reaction-form">
                                <input type="hidden" name="announcement_id" value="<?= $a['announcement_id'] ?>">
                                <input type="hidden" name="course_id" value="<?= $course_id ?>">
                                <button type="submit" class="reaction-button <?= $reacted ? 'reacted' : '' ?>" <?= $role !== 'student' ? 'disabled' : '' ?>>
                                    👍 <span class="reaction-count"><?= $count ?></span>
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endwhile; else: ?>
                    <p>No announcements yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- TA List -->
        <div class="tas-section">
            <h2>Teaching Assistants</h2>
            <ul class="ta-list">
                <?php
                $ta_stmt = $conn->prepare("
                    SELECT u.user_id, u.full_name, u.email
                    FROM course_tas ct
                    JOIN users u ON ct.ta_id = u.user_id
                    WHERE ct.course_id = ?
                ");
                $ta_stmt->bind_param("i", $course_id);
                $ta_stmt->execute();
                $ta_result = $ta_stmt->get_result();

                if ($ta_result->num_rows === 0):
                    echo "<li>No TAs assigned.</li>";
                else:
                    while ($ta = $ta_result->fetch_assoc()):
                        $ta_id = $ta['user_id'];
                        $name = htmlspecialchars($ta['full_name']);
                        $email = htmlspecialchars($ta['email']);
                        echo "<li>$name ($email)";

                        if ($is_ta && $ta_id == $user_id) {
                            echo "
                            <form action='manage_schedule.php' method='get' class='inline-form'>
                                <input type='hidden' name='course_id' value='$course_id'>
                                <button type='submit' class='small-button'>Manage Appointments</button>
                            </form>";
                        } elseif ($role === 'student') {
                            echo "
                            <form action='make_appointment.php' method='get' class='inline-form'>
                                <input type='hidden' name='course_id' value='$course_id'>
                                <input type='hidden' name='ta_id' value='$ta_id'>
                                <button type='submit' class='small-button'>Make Appointment</button>
                            </form>";
                        }

                        echo "</li>";
                    endwhile;
                endif;
                ?>
            </ul>
        </div>

        <!-- Upcoming Appointments -->
        <div class="appointments-section">
            <h2>Upcoming Appointments</h2>
            
            <?php
            // DEBUG: Show raw ta_schedule data
            $debug_schedule = $conn->prepare("
                SELECT s.schedule_id, s.date, s.time, s.is_booked, 
                    u.full_name as ta_name, a.appointment_id
                FROM ta_schedule s
                LEFT JOIN users u ON s.ta_id = u.user_id
                LEFT JOIN appointments a ON s.schedule_id = a.schedule_id
                WHERE s.course_id = ?
                ORDER BY s.date, s.time
            ");
            $debug_schedule->bind_param("i", $course_id);
            $debug_schedule->execute();
            $debug_result = $debug_schedule->get_result();
            
            echo "<!-- DEBUG: All TA Schedule Entries -->";
            echo "<!-- Schedule ID | Date | Time | Booked | TA Name | Appointment ID -->";
            while ($debug_row = $debug_result->fetch_assoc()) {
                echo "<!-- " . implode(" | ", $debug_row) . " -->";
            }
            ?>
            
            <ul class="appointments-list">
                <?php
                $upcoming_sql = "
                    SELECT 
                        a.appointment_id,
                        a.student_id,
                        s.date,
                        s.time,
                        su.full_name AS student_name,
                        tu.full_name AS ta_name,
                        tu.user_id AS ta_id
                    FROM appointments a
                    JOIN ta_schedule s ON a.schedule_id = s.schedule_id
                    JOIN users su ON a.student_id = su.user_id
                    JOIN users tu ON s.ta_id = tu.user_id
                    WHERE s.course_id = ? 
                    AND (s.date > CURDATE() OR (s.date = CURDATE() AND s.time > CURTIME()))
                    ORDER BY s.date, s.time
                ";
                $stmt = $conn->prepare($upcoming_sql);
                $stmt->bind_param("i", $course_id);
                $stmt->execute();
                $upcoming_result = $stmt->get_result();

                if ($upcoming_result->num_rows > 0) {
                    while ($row = $upcoming_result->fetch_assoc()) {
                        $ta_you = ($row['ta_id'] == $user_id && $is_ta);
                        $student_you = ($row['student_id'] == $user_id && !$is_ta);

                        echo "<li><strong>TA:</strong> " . htmlspecialchars($row['ta_name']) . ($ta_you ? " <strong>(You)</strong>" : "") .
                            " | <strong>When:</strong> " . htmlspecialchars($row['date']) . " at " . htmlspecialchars($row['time']) .
                            " | <strong>Student:</strong> " . htmlspecialchars($row['student_name']) . ($student_you ? " <strong>(You)</strong>" : "") . "</li>";
                    }
                } else {
                    echo "<li>No upcoming appointments.</li>";
                }
                ?>
            </ul>
                    
            <!-- View History Button -->
            <button class="view-history-btn" onclick="showAppointmentHistory()">
                <?= $role === 'admin' ? 'View All Appointment History' : 'View My Appointment History' ?>
            </button>
        </div>

        <!-- History Popup Modal -->
        <div id="historyModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal()">&times;</span>
                <h2><?= $role === 'admin' ? 'All Appointment History' : 'My Appointment History' ?></h2>
                <div id="historyContent" class="appointments-list">
                    <!-- Content will be loaded via AJAX -->
                    <p>Loading history...</p>
                </div>
            </div>
        </div>

        <!-- Discussion Room -->
        <div style="margin-top: 30px;">
            <a href="<?php echo $_SESSION['role'] ?? 'student'; ?>.php" class="page-button">← Back to Dashboard</a>
            <a href="discussion.php?course_id=<?php echo $course_id; ?>" class="page-button">Q&A Discussion Room</a>
        </div>
    </div>
</body>
</html>