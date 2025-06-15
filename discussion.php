<?php
session_start();
include 'db_connect.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get course_id from URL
if (!isset($_GET['course_id']) || !is_numeric($_GET['course_id'])) {
    echo "Invalid course ID.";
    exit();
}

$course_id = intval($_GET['course_id']);

// Get course info
$course_sql = "SELECT course_code, course_name FROM courses WHERE course_id = ?";
$stmt = $conn->prepare($course_sql);
$stmt->bind_param("i", $course_id);
$stmt->execute();
$course_result = $stmt->get_result();

if ($course_result->num_rows === 0) {
    echo "Course not found.";
    exit();
}

$course = $course_result->fetch_assoc();

// Check if user is TA
$is_ta = false;
if (isset($_SESSION['user_id'])) {
    $ta_check = $conn->prepare("SELECT 1 FROM course_tas WHERE course_id = ? AND ta_id = ?");
    $ta_check->bind_param("ii", $course_id, $_SESSION['user_id']);
    $ta_check->execute();
    $is_ta = $ta_check->get_result()->num_rows > 0;
}

// Handle form submission for new posts
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['content'])) {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content']);
    $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
    $image_path = null;
    
    if (!empty($_FILES['image']['name'])) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $file_ext;
        $target_file = $target_dir . $filename;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image_path = $target_file;
        }
    }
    
    if (!empty($content)) {
        // Set default title if empty
        if (empty($title)) {
            $title = "Untitled";
        }
        
        $insert_sql = "INSERT INTO discussion_posts (course_id, user_id, title, content, parent_id, image_path) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("iissis", $course_id, $_SESSION['user_id'], $title, $content, $parent_id, $image_path);
        $stmt->execute();
    }
}

// Handle pin/unpin
if (isset($_GET['pin'])) {
    $post_id = intval($_GET['pin']);
    if ($is_ta) {
        $conn->query("UPDATE discussion_posts SET is_pinned = NOT is_pinned WHERE post_id = $post_id");
    }
    header("Location: discussion.php?course_id=$course_id");
    exit();
}

// Get all discussion posts for this course (pinned first)
$posts_sql = "SELECT dp.*, u.full_name 
              FROM discussion_posts dp
              JOIN users u ON dp.user_id = u.user_id
              WHERE dp.course_id = ? AND dp.parent_id IS NULL
              ORDER BY dp.is_pinned DESC, dp.created_at DESC";
$stmt = $conn->prepare($posts_sql);
$stmt->bind_param("i", $course_id);
$stmt->execute();
$posts_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo $course['course_name']; ?> - Discussion Room</title>
    <link rel="shortcut icon" href="logo_red.png" type="image/x-icon">
    <link rel="stylesheet" href="styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <style>
        .post-image {
            margin: 15px 0;
            max-width: 100%;
        }
        .post-image img {
            max-width: 100%;
            max-height: 400px;
            border-radius: 4px;
        }
        .pin-form {
            display: inline-block;
            margin-left: 10px;
        }
        .pin-button {
            background: none;
            border: none;
            cursor: pointer;
            color: #80141c;
            font-size: 14px;
        }
        .pin-button:hover {
            color: #c03434;
        }
        .pinned-badge {
            display: inline-block;
            background-color: #fff3e0;
            color: #e65100;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            margin-left: 10px;
            border: 1px solid #ffe0b2;
        }
    </style>
</head>
<body>
    <nav>
        <img src="./logo_white.png">
        <a href="./index.php">
            <img src="./logout.png">
        </a>
    </nav>
    <div class="dashboard-body">
        <h1><?php echo $course['course_code']; ?> - <?php echo $course['course_name']; ?></h1>
        <h2>Q&A Discussion Room</h2>
        
        <!-- New Post Form -->
        <div class="post">
            <h3>Ask a New Question</h3>
            <form method="POST" action="discussion.php?course_id=<?php echo $course_id; ?>" enctype="multipart/form-data">
                <div>
                    <label for="title">Title:</label>
                    <input type="text" id="title" name="title" required style="width: 100%; padding: 8px; margin-bottom: 10px;">
                </div>
                <div>
                    <label for="content">Question:</label>
                    <textarea id="content" name="content" required style="width: 100%; padding: 8px; min-height: 100px;"></textarea>
                </div>
                <div>
                    <label for="image">Upload Image (optional):</label>
                    <input type="file" id="image" name="image" accept="image/*">
                </div>
                <button type="submit" style="margin-top: 10px;">Post Question</button>
            </form>
        </div>
        
        <!-- List of Posts -->
        <h3>Questions</h3>
        <?php if ($posts_result->num_rows > 0): ?>
            <?php while ($post = $posts_result->fetch_assoc()): ?>
                <div class="post" id="post-<?php echo $post['post_id']; ?>">
                    <div class="post-header">
                        <span><?php echo htmlspecialchars($post['full_name']); ?>
                            <?php if ($post['is_pinned']): ?>
                                <span class="pinned-badge">📌 Pinned Question</span>
                            <?php endif; ?>
                        </span>
                        <span><?php echo date('M j, Y g:i a', strtotime($post['created_at'])); ?></span>
                    </div>
                    <h3><?php echo htmlspecialchars($post['title']); ?></h3>
                    <div class="post-content">
                        <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                    </div>
                    
                    <?php if (!empty($post['image_path'])): ?>
                        <div class="post-image">
                            <img src="<?php echo htmlspecialchars($post['image_path']); ?>" alt="Post image">
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($is_ta): ?>
                        <form method="GET" class="pin-form">
                            <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
                            <input type="hidden" name="pin" value="<?php echo $post['post_id']; ?>">
                            <button type="submit" class="pin-button">
                                <?php echo $post['is_pinned'] ? '📌 Unpin' : '📌 Pin'; ?>
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <!-- Reply button -->
                    <button onclick="toggleReplyForm(<?php echo $post['post_id']; ?>)" class="reply-btn">Reply</button>
                    
                    <!-- Reply form (hidden by default) -->
                    <div class="reply-form" id="reply-form-<?php echo $post['post_id']; ?>">
                        <form method="POST" action="discussion.php?course_id=<?php echo $course_id; ?>" enctype="multipart/form-data">
                            <input type="hidden" name="parent_id" value="<?php echo $post['post_id']; ?>">
                            <div>
                                <label for="title-<?php echo $post['post_id']; ?>">Title (optional):</label>
                                <input type="text" id="title-<?php echo $post['post_id']; ?>" name="title" placeholder="Leave blank for 'Untitled'" style="width: 100%; padding: 8px; margin-bottom: 10px;">
                            </div>
                            <div>
                                <label for="content-<?php echo $post['post_id']; ?>">Your Answer:</label>
                                <textarea id="content-<?php echo $post['post_id']; ?>" name="content" required style="width: 100%; padding: 8px; min-height: 80px;"></textarea>
                            </div>
                            <div>
                                <label for="image-<?php echo $post['post_id']; ?>">Upload Image (optional):</label>
                                <input type="file" id="image-<?php echo $post['post_id']; ?>" name="image" accept="image/*">
                            </div>
                            <button type="submit" style="margin-top: 10px;">Post Reply</button>
                        </form>
                    </div>
                    
                    <!-- Toggle replies button and replies container -->
                    <?php
                    // Check if this post has any replies
                    $reply_check_sql = "SELECT COUNT(*) as reply_count FROM discussion_posts WHERE parent_id = ?";
                    $stmt = $conn->prepare($reply_check_sql);
                    $stmt->bind_param("i", $post['post_id']);
                    $stmt->execute();
                    $reply_check_result = $stmt->get_result();
                    $reply_count = $reply_check_result->fetch_assoc()['reply_count'];
                    ?>
                    
                    <?php if ($reply_count > 0): ?>
                        <div class="toggle-replies" onclick="toggleReplies(<?php echo $post['post_id']; ?>)">
                            View <?php echo $reply_count; ?> replies
                        </div>
                        
                        <div class="replies" id="replies-<?php echo $post['post_id']; ?>" style="display: none;">
                            <?php
                            // Get replies for this post
                            $replies_sql = "SELECT dp.*, u.full_name 
                                           FROM discussion_posts dp
                                           JOIN users u ON dp.user_id = u.user_id
                                           WHERE dp.parent_id = ?
                                           ORDER BY dp.created_at ASC";
                            $stmt = $conn->prepare($replies_sql);
                            $stmt->bind_param("i", $post['post_id']);
                            $stmt->execute();
                            $replies_result = $stmt->get_result();
                            
                            while ($reply = $replies_result->fetch_assoc()):
                            ?>
                                <div class="reply">
                                    <div class="post-header">
                                        <span><?php echo htmlspecialchars($reply['full_name']); ?></span>
                                        <span><?php echo date('M j, Y g:i a', strtotime($reply['created_at'])); ?></span>
                                    </div>
                                    <?php if (!empty($reply['title'])): ?>
                                        <h4><?php echo htmlspecialchars($reply['title']); ?></h4>
                                    <?php endif; ?>
                                    <div class="post-content">
                                        <?php echo nl2br(htmlspecialchars($reply['content'])); ?>
                                    </div>
                                    <?php if (!empty($reply['image_path'])): ?>
                                        <div class="post-image">
                                            <img src="<?php echo htmlspecialchars($reply['image_path']); ?>" alt="Reply image">
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No questions have been posted yet. Be the first to ask!</p>
        <?php endif; ?>

        <div style="margin-top: 30px;">
            <a href="course.php?course_id=<?php echo $course_id; ?>" class="page-button">← Back to Course</a>
        </div>
        
    </div>

    <script>
        function toggleReplyForm(postId) {
            const form = document.getElementById('reply-form-' + postId);
            form.style.display = form.style.display === 'block' ? 'none' : 'block';
        }
        
        function toggleReplies(postId) {
            const replies = document.getElementById('replies-' + postId);
            const toggleBtn = replies.previousElementSibling;
            
            if (replies.style.display === 'block') {
                replies.style.display = 'none';
                toggleBtn.textContent = 'View replies';
            } else {
                replies.style.display = 'block';
                toggleBtn.textContent = 'Hide replies';
            }
        }
    </script>
</body>
</html>