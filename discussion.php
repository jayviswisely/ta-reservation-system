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

function createThumbnail($src, $dest, $targetWidth, $targetHeight) {
    // Check if GD is installed
    if (!function_exists('gd_info')) {
        // Just copy the original if GD isn't available
        copy($src, $dest);
        return false;
    }

    $info = getimagesize($src);
    $mime = $info['mime'] ?? '';
    
    try {
        switch ($mime) {
            case 'image/jpeg':
                $image = function_exists('imagecreatefromjpeg') ? imagecreatefromjpeg($src) : false;
                break;
            case 'image/png':
                $image = function_exists('imagecreatefrompng') ? imagecreatefrompng($src) : false;
                break;
            case 'image/gif':
                $image = function_exists('imagecreatefromgif') ? imagecreatefromgif($src) : false;
                break;
            default:
                return false;
        }

        if (!$image) {
            copy($src, $dest);
            return false;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        
        $originalAspect = $width / $height;
        $thumbAspect = $targetWidth / $targetHeight;
        
        if ($originalAspect >= $thumbAspect) {
            $newHeight = $targetHeight;
            $newWidth = $width / ($height / $targetHeight);
        } else {
            $newWidth = $targetWidth;
            $newHeight = $height / ($width / $targetWidth);
        }
        
        $thumb = imagecreatetruecolor($targetWidth, $targetHeight);
        
        // Preserve transparency for PNG/GIF
        if ($mime == 'image/png' || $mime == 'image/gif') {
            imagecolortransparent($thumb, imagecolorallocatealpha($thumb, 0, 0, 0, 127));
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
        }
        
        imagecopyresampled($thumb, $image,
                          0 - ($newWidth - $targetWidth) / 2,
                          0 - ($newHeight - $targetHeight) / 2,
                          0, 0,
                          $newWidth, $newHeight,
                          $width, $height);
        
        switch ($mime) {
            case 'image/jpeg':
                imagejpeg($thumb, $dest, 85);
                break;
            case 'image/png':
                imagepng($thumb, $dest, 8);
                break;
            case 'image/gif':
                imagegif($thumb, $dest);
                break;
        }
        
        imagedestroy($thumb);
        imagedestroy($image);
        
        return true;
    } catch (Exception $e) {
        // Fallback to copying original if any error occurs
        copy($src, $dest);
        return false;
    }
}

// Handle form submission for new posts
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['content'])) {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content']);
    $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
    $file_path = null;
    
    if (!empty($_FILES['file']['name'])) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_name = basename($_FILES['file']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
        
        if (in_array($file_ext, $allowed_exts)) {
            $filename = uniqid() . '.' . $file_ext;
            $target_file = $target_dir . $filename;
            
            if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
                $file_path = $target_file;
                
                // Create thumbnail for images
                if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                    if (!file_exists($target_dir . 'thumbs/')) {
                        mkdir($target_dir . 'thumbs/', 0777, true);
                    }
                    
                    $thumb_path = $target_dir . 'thumbs/' . $filename;
                    createThumbnail($target_file, $thumb_path, 200, 200);
                }
            } else {
                $_SESSION['error'] = "Sorry, there was an error uploading your file.";
            }
        } else {
            $_SESSION['error'] = "Only JPG, JPEG, PNG, GIF, and PDF files are allowed.";
        }
    }
    
    if (!empty($content)) {
        if (empty($title)) {
            $title = "Untitled";
        }
        
        $insert_sql = "INSERT INTO discussion_posts (course_id, user_id, title, content, parent_id, file_path) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("iissis", $course_id, $_SESSION['user_id'], $title, $content, $parent_id, $file_path);
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
$posts_sql = "SELECT dp.*, u.full_name, 
              MAX(CASE WHEN r.role_name = 'admin' THEN 1 ELSE 0 END) as is_admin,
              MAX(CASE WHEN r.role_name = 'ta' THEN 1 ELSE 0 END) as is_ta_general,
              MAX(CASE WHEN ct.ta_id IS NOT NULL THEN 1 ELSE 0 END) as is_ta_for_course
              FROM discussion_posts dp
              JOIN users u ON dp.user_id = u.user_id
              LEFT JOIN user_roles ur ON u.user_id = ur.user_id
              LEFT JOIN roles r ON ur.role_id = r.role_id
              LEFT JOIN course_tas ct ON u.user_id = ct.ta_id AND ct.course_id = ?
              WHERE dp.course_id = ? AND dp.parent_id IS NULL
              GROUP BY dp.post_id, u.user_id
              ORDER BY dp.is_pinned DESC, dp.created_at DESC";
$stmt = $conn->prepare($posts_sql);
$stmt->bind_param("ii", $course_id, $course_id);
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
        .post-file {
            margin: 15px 0;
            max-width: 100%;
        }
        .thumbnail-container {
            position: relative;
            display: inline-block;
        }
        .thumbnail {
            max-width: 200px;
            max-height: 200px;
            cursor: pointer;
            border: 1px solid #ddd;
            border-radius: 4px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .thumbnail:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .zoom-icon {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background-color: rgba(0,0,0,0.5);
            color: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.9);
            overflow: auto;
        }
        .modal-content {
            display: block;
            margin: 5% auto;
            max-width: 90%;
            max-height: 90%;
            animation: zoom 0.3s;
        }
        @keyframes zoom {
            from {transform: scale(0.9)}
            to {transform: scale(1)}
        }
        .close {
            position: absolute;
            top: 20px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            transition: 0.3s;
            cursor: pointer;
        }
        .close:hover {
            color: #bbb;
        }
        .pdf-preview {
            display: inline-block;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 4px;
            text-align: center;
            border: 1px solid #ddd;
        }
        .pdf-icon {
            font-size: 50px;
            color: #80141c;
            display: block;
            margin-bottom: 10px;
        }
        .pdf-link {
            display: inline-block;
            padding: 8px 15px;
            background-color: #80141c;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .pdf-link:hover {
            background-color: #c03434;
        }
        .error-message {
            color: #d32f2f;
            background-color: #ffebee;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #ef9a9a;
        }

        .user-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 8px;
            vertical-align: middle;
        }

        .ta-badge {
            background-color: #80141c;
            color: white;
        }

        .admin-badge {
            background-color: #2c387e;
            color: white;
        }

        .pinned-badge {
            background-color: #f5a623;
            color: white;
            padding: 2px 6px;
            border-radius: 12px;
            font-size: 12px;
            margin-left: 8px;
        }
    </style>
</head>
<body>
    <nav>
        <img src="./assets/logo_white.png">
        <a href="./index.php">
            <img src="./assets/logout.png">
        </a>
    </nav>
    <div class="dashboard-body">
        <h1><?php echo $course['course_code']; ?> - <?php echo $course['course_name']; ?></h1>
        <h2>Q&A Discussion Room</h2>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
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
                    <label for="file">Upload File (JPG, JPEG, PNG, GIF, or PDF):</label>
                    <input type="file" id="file" name="file" accept=".jpg,.jpeg,.png,.gif,.pdf">
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
                        <span>
                            <?php echo htmlspecialchars($post['full_name']); ?>
                            <?php if ($post['is_admin']): ?>
                                <span class="user-badge admin-badge">Professor</span>
                            <?php elseif ($post['is_ta_for_course'] || $post['is_ta_general']): ?>
                                <span class="user-badge ta-badge">TA</span>
                            <?php endif; ?>
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
                    
                    <?php if (!empty($post['file_path'])): ?>
                        <div class="post-file">
                            <?php
                            $file_ext = strtolower(pathinfo($post['file_path'], PATHINFO_EXTENSION));
                            if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])): 
                                $thumb_path = dirname($post['file_path']) . '/thumbs/' . basename($post['file_path']);
                                if (file_exists($thumb_path)): ?>
                                    <div class="thumbnail-container">
                                        <img src="<?php echo htmlspecialchars($thumb_path); ?>" 
                                             class="thumbnail" 
                                             onclick="openModal('<?php echo htmlspecialchars($post['file_path']); ?>')"
                                             alt="Post image">
                                        <div class="zoom-icon">🔍</div>
                                    </div>
                                <?php else: ?>
                                    <img src="<?php echo htmlspecialchars($post['file_path']); ?>" 
                                         style="max-width: 200px; max-height: 200px; cursor: pointer;" 
                                         onclick="openModal('<?php echo htmlspecialchars($post['file_path']); ?>')"
                                         alt="Post image">
                                <?php endif; ?>
                            <?php elseif ($file_ext === 'pdf'): ?>
                                <div class="pdf-preview">
                                    <span class="pdf-icon">📄</span>
                                    <a href="<?php echo htmlspecialchars($post['file_path']); ?>" class="pdf-link" target="_blank">View PDF</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Image Modal -->
                    <div id="imageModal" class="modal">
                        <span class="close" onclick="closeModal()">&times;</span>
                        <img class="modal-content" id="modalImage">
                    </div>
                    
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
                                <label for="file-<?php echo $post['post_id']; ?>">Upload File (JPG, JPEG, PNG, GIF, or PDF):</label>
                                <input type="file" id="file-<?php echo $post['post_id']; ?>" name="file" accept=".jpg,.jpeg,.png,.gif,.pdf">
                            </div>
                            <button type="submit" style="margin-top: 10px;">Post Reply</button>
                        </form>
                    </div>
                    
                    <!-- Toggle replies button and replies container -->
                    <?php
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
                            $replies_sql = "SELECT dp.*, u.full_name,
                                        MAX(CASE WHEN r.role_name = 'admin' THEN 1 ELSE 0 END) as is_admin,
                                        MAX(CASE WHEN r.role_name = 'ta' THEN 1 ELSE 0 END) as is_ta_general,
                                        MAX(CASE WHEN ct.ta_id IS NOT NULL THEN 1 ELSE 0 END) as is_ta_for_course
                                        FROM discussion_posts dp
                                        JOIN users u ON dp.user_id = u.user_id
                                        LEFT JOIN user_roles ur ON u.user_id = ur.user_id
                                        LEFT JOIN roles r ON ur.role_id = r.role_id
                                        LEFT JOIN course_tas ct ON u.user_id = ct.ta_id AND ct.course_id = ?
                                        WHERE dp.parent_id = ?
                                        GROUP BY dp.post_id, u.user_id
                                        ORDER BY dp.created_at ASC";
                            $stmt = $conn->prepare($replies_sql);
                            $stmt->bind_param("ii", $course_id, $post['post_id']);
                            $stmt->execute();
                            $replies_result = $stmt->get_result();
                            
                            while ($reply = $replies_result->fetch_assoc()):
                            ?>
                                <div class="reply">
                                    <div class="post-header">
                                        <span>
                                            <?php echo htmlspecialchars($reply['full_name']); ?>
                                            <?php if ($reply['is_admin']): ?>
                                                <span class="user-badge admin-badge">Professor</span>
                                            <?php elseif ($reply['is_ta_for_course'] || $reply['is_ta_general']): ?>
                                                <span class="user-badge ta-badge">TA</span>
                                            <?php endif; ?>
                                        </span>
                                        <span><?php echo date('M j, Y g:i a', strtotime($reply['created_at'])); ?></span>
                                    </div>
                                    <?php if (!empty($reply['title'])): ?>
                                        <h4><?php echo htmlspecialchars($reply['title']); ?></h4>
                                    <?php endif; ?>
                                    <div class="post-content">
                                        <?php echo nl2br(htmlspecialchars($reply['content'])); ?>
                                    </div>
                                    <?php if (!empty($reply['file_path'])): ?>
                                        <div class="post-file">
                                            <?php
                                            $file_ext = strtolower(pathinfo($reply['file_path'], PATHINFO_EXTENSION));
                                            if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])): 
                                                $thumb_path = dirname($reply['file_path']) . '/thumbs/' . basename($reply['file_path']);
                                                if (file_exists($thumb_path)): ?>
                                                    <div class="thumbnail-container">
                                                        <img src="<?php echo htmlspecialchars($thumb_path); ?>" 
                                                             class="thumbnail" 
                                                             onclick="openModal('<?php echo htmlspecialchars($reply['file_path']); ?>')"
                                                             alt="Reply image">
                                                        <div class="zoom-icon">🔍</div>
                                                    </div>
                                                <?php else: ?>
                                                    <img src="<?php echo htmlspecialchars($reply['file_path']); ?>" 
                                                         style="max-width: 200px; max-height: 200px; cursor: pointer;" 
                                                         onclick="openModal('<?php echo htmlspecialchars($reply['file_path']); ?>')"
                                                         alt="Reply image">
                                                <?php endif; ?>
                                            <?php elseif ($file_ext === 'pdf'): ?>
                                                <div class="pdf-preview">
                                                    <span class="pdf-icon">📄</span>
                                                    <a href="<?php echo htmlspecialchars($reply['file_path']); ?>" class="pdf-link" target="_blank">View PDF</a>
                                                </div>
                                            <?php endif; ?>
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
        // Modal functions for image viewing
        function openModal(src) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            modal.style.display = "block";
            modalImg.src = src;
            document.body.style.overflow = "hidden"; // Prevent scrolling
        }

        function closeModal() {
            document.getElementById('imageModal').style.display = "none";
            document.body.style.overflow = "auto"; // Re-enable scrolling
        }

        // Close modal when clicking outside the image
        window.onclick = function(event) {
            const modal = document.getElementById('imageModal');
            if (event.target == modal) {
                closeModal();
            }
        }

        // Close modal with ESC key
        document.addEventListener('keydown', function(event) {
            if (event.key === "Escape") {
                closeModal();
            }
        });

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