<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NCKU TA Consultation</title>
    <link rel="shortcut icon" href="logo_red.png" type="image/x-icon">
    <link rel="stylesheet" href="styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
</head>
<body>
    <img class="login-bg" src="./assets/logo_translucent.png">
    <div class="login-body">
        <div class="login-container">
            <h2>Login</h2>
            <?php
                session_start();
                if (isset($_SESSION['error'])) {
                    echo "<p style='color:red;'>".$_SESSION['error']."</p>";
                    unset($_SESSION['error']);
                }
            ?>
            <form id="login-form" action="login.php" method="POST">
                <label for="username">Username</label>
                <input class="login-input" type="text" id="username" name="username" required>
                
                <label for="password">Password</label>
                <input class="login-input" type="password" id="password" name="password" required>
                
                <label for="role">Login as:</label>
                <select class="login-dropbox" id="role" name="role">
                    <option value="student">Student</option>
                    <option value="ta">Teaching Assistant</option>
                    <option value="admin">Admin</option>
                </select>
                
                <button class="login-button" type="submit">Login</button>
            </form>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>
