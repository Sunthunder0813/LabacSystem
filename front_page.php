<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Labac System - Front Page</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin-top: 100px;
        }
        .container {
            max-width: 400px;
            margin: auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        button {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            font-size: 16px;
            cursor: pointer;
            border: none;
            border-radius: 5px;
        }
        .teacher {
            background-color: #4CAF50;
            color: white;
        }
        .student {
            background-color: #2196F3;
            color: white;
        }
        button:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome to Labac System</h1>
        <p>Please select your role:</p>
        <form action="role_handler.php" method="POST">
            <button type="submit" name="role" value="teacher" class="teacher">I am a Teacher</button>
            <button type="submit" name="role" value="student" class="student">I am a Student</button>
        </form>
        <p id="selected-role"></p>
    </div>
</body>
</html>