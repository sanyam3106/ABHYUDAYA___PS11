<?php
session_start();
require 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Use prepared statements for security
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
    $stmt->bind_param("ss", $email, $role);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        // Since passwords are currently plaintext in the DB, we compare directly.
        // In a production app, we should use password_hash and password_verify.
        if ($password === $row['password']) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_name'] = $row['name'];
            $_SESSION['user_role'] = $row['role'];
            $_SESSION['hostel_name'] = $row['hostel_name'];

            if ($role == 'student') {
                header("Location: student_dashboard.php");
            } elseif ($role == 'admin') {
                header("Location: admin_dashboard.php");
            } elseif ($role == 'staff') {
                header("Location: staff_dashboard.php");
            }
            exit();
        } else {
            header("Location: index.php?error=Invalid password");
            exit();
        }
    } else {
        header("Location: index.php?error=User not found with this role");
        exit();
    }
    $stmt->close();
}
?>