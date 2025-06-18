<?php
session_start();
include 'db_connect.php';

$user_id = $_SESSION['user_id'] ?? 0;
$ta_name = $_SESSION['full_name'] ?? 'TA';

// Get courses where this user is a TA, and also fetch the professor's name
$sql = "
SELECT c.course_id, c.course_name, u.full_name AS admin_name
FROM courses c
JOIN course_tas ct ON c.course_id = ct.course_id
JOIN users u ON c.admin_id = u.user_id
WHERE ct.ta_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>TA Dashboard</title>
  <link rel="shortcut icon" href="logo_red.png" type="image/x-icon">
  <link rel="stylesheet" href="styles.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
</head>
<body>
  <nav>
    <img src="./assets/logo_white.png">
    <a href="./index.php">
        <img src="./assets/logout.png">
    </a>
  </nav>
  <div class="dashboard-body">
    <h1 class="dashboard-message">Hello, <?= htmlspecialchars($ta_name) ?></h1>
    <h2>Your Courses as TA:</h2>
    <ul class="dashboard-courses">
      <?php
      if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
          echo '<a href="course.php?course_id=' . urlencode($row['course_id'] ?? '') . '">';
          echo '<li class="dashboard-course">';
          echo htmlspecialchars($row['course_name'] ?? '') . ' (Prof. ' . 
                htmlspecialchars($row['admin_name'] ?? '') . ')';
          echo '</li>';
          echo '</a>';
        }
      } else {
          echo "<li>No courses found.</li>";
      }
      ?>
    </ul>
  </div> 

  <script src="script.js"></script>
</body>
</html>
