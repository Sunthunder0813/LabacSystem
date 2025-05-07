<?php
session_start();
require_once 'connection.php';
require_once 'email_sender.php'; // Include the email sender utility

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unique_id'], $_POST['password'])) {
    $unique_id = $_POST['unique_id'];
    $password = $_POST['password'];

    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE unique_id = ? AND password = ? AND user_type_id = 3");
    $stmt->bind_param("ss", $unique_id, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();
        $_SESSION['otp_email'] = $admin['email'];
        $_SESSION['otp'] = rand(100000, 999999); // Generate a 6-digit OTP

        // Send OTP using the sendEmail function
        $subject = "Your OTP Code";
        $body = $_SESSION['otp'];
        $username = $admin['username']; // Fetch the admin's username
        if (sendEmail($admin['email'], $subject, $body, $username, $error)) {
            $_SESSION['login_success'] = true; // Set flag for dashboard notification
            header('Location: verify_otp.php');
            exit();
        } else {
            $error = "Failed to send OTP. " . $error;
        }
    } else {
        $error = "Invalid unique ID or password.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 300px;
        }
        .login-container h1 {
            margin-bottom: 20px;
            font-size: 24px;
            text-align: center;
        }
        .login-container input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .login-container button {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .login-container button:hover {
            background-color: #0056b3;
        }
        .error {
            color: red;
            font-size: 14px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Admin Login</h1>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <form method="POST" action="">
            <input type="text" name="unique_id" placeholder="Unique ID" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
