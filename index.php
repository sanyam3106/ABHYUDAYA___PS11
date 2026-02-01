<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - HostelEase</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <h1>HostelEase</h1>
                <p>Complaint Management System</p>
            </div>

            <?php if (isset($_GET['error'])): ?>
                <div style="color: red; text-align: center; margin-bottom: 1rem;">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <form action="auth_login.php" method="POST">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" required
                        placeholder="Enter your email">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required
                        placeholder="Enter your password">
                </div>

                <div class="form-group">
                    <label>Select Role</label>
                    <div class="checkbox-group">
                        <select name="role" class="form-control" required>
                            <option value="">-- Select Role --</option>
                            <option value="student">Student</option>
                            <option value="admin">Warden (Admin)</option>
                            <option value="staff">Staff</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn">Login</button>
            </form>

            <div style="margin-top: 1rem; text-align: center; font-size: 0.8rem; color: #6b7280;">
                <p>Default Credentials (for testing):</p>
                <p>Student: student@test.com / password123</p>
                <p>Admin: admin@test.com / admin123</p>
                <p>Staff: plumber@test.com / staff123</p>
            </div>
        </div>
    </div>
</body>

</html>