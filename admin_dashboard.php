<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: index.php");
    exit();
}

$message = "";

// Handle Staff Assignment
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_staff'])) {
    $complaint_id = $_POST['complaint_id'];
    $staff_id = $_POST['staff_id'];
    $admin_id = $_SESSION['user_id'];

    if (!empty($staff_id)) {
        // Create Assignment with prepared statements
        $stmt_assign = $conn->prepare("INSERT INTO assignments (complaint_id, staff_id, assigned_by) VALUES (?, ?, ?)");
        $stmt_assign->bind_param("iii", $complaint_id, $staff_id, $admin_id);

        if ($stmt_assign->execute()) {
            // Update Complaint Status
            $stmt_update = $conn->prepare("UPDATE complaints SET status = 'Assigned' WHERE id = ?");
            $stmt_update->bind_param("i", $complaint_id);
            $stmt_update->execute();
            $stmt_update->close();

            $message = "Staff assigned successfully!";
        } else {
            $message = "Error assigning staff: " . $conn->error;
        }
        $stmt_assign->close();
    }
}

// Fetch Stats
$total_sql = "SELECT COUNT(*) as count FROM complaints";
$pending_sql = "SELECT COUNT(*) as count FROM complaints WHERE status = 'Submitted'";
$resolved_sql = "SELECT COUNT(*) as count FROM complaints WHERE status = 'Resolved' OR status = 'Closed'";

$total_complaints = $conn->query($total_sql)->fetch_assoc()['count'];
$pending_complaints = $conn->query($pending_sql)->fetch_assoc()['count'];
$resolved_complaints = $conn->query($resolved_sql)->fetch_assoc()['count'];

// Fetch All Complaints with Student Info
// Priority logic: High > Medium > Low
$sql_complaints = "
    SELECT c.*, u.name as student_name, u.room_number,
           (SELECT name FROM users WHERE id = (SELECT staff_id FROM assignments WHERE complaint_id = c.id LIMIT 1)) as assigned_staff
    FROM complaints c
    JOIN users u ON c.user_id = u.id
    ORDER BY FIELD(c.priority, 'High', 'Medium', 'Low'), c.created_at ASC
";
$result_complaints = $conn->query($sql_complaints);

// Fetch Staff Members for Dropdown
$sql_staff = "SELECT * FROM users WHERE role = 'staff'";
$staff_members = $conn->query($sql_staff);
$staff_options = [];
while ($row = $staff_members->fetch_assoc()) {
    $staff_options[] = $row;
}

// Fetch Feedback for Admin
$sql_feedback = "
    SELECT f.*, u.name as student_name, c.title as complaint_title 
    FROM feedback f
    JOIN users u ON f.user_id = u.id
    JOIN complaints c ON f.complaint_id = c.id
    ORDER BY f.created_at DESC
";
$result_feedback = $conn->query($sql_feedback);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warden Admin Dashboard - HostelEase</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="logo.jpeg" alt="Logo"
                    style="width: 50px; height: 50px; border-radius: 50%; margin-bottom: 0.5rem;">
                <h2>HostelEase</h2>
                <span class="badge badge-high">ADMIN</span>
            </div>
            <div class="user-info">
                <p><strong>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></strong></p>
                <p>Warden Panel</p>
            </div>
            <ul class="sidebar-menu">
                <li class="menu-item"><a href="#overview" class="menu-link active">Overview</a></li>
                <li class="menu-item"><a href="#complaints" class="menu-link">Manage Complaints</a></li>
                <li class="menu-item"><a href="#feedback" class="menu-link">Student Feedback</a></li>
                <li class="menu-item"><a href="logout.php" class="menu-link" style="color: var(--danger);">Logout</a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Admin Dashboard</h1>
            </div>

            <?php if ($message): ?>
                <div
                    style="background-color: #dcfce7; color: #166534; padding: 1rem; border-radius: 0.5rem; margin-bottom: 2rem;">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Stats Overview -->
            <section id="overview" class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $total_complaints; ?></div>
                    <div class="stat-label">Total Complaints</div>
                </div>
                <div class="stat-card" style="border-left-color: var(--warning);">
                    <div class="stat-value"><?php echo $pending_complaints; ?></div>
                    <div class="stat-label">Pending / Submitted</div>
                </div>
                <div class="stat-card" style="border-left-color: var(--success);">
                    <div class="stat-value"><?php echo $resolved_complaints; ?></div>
                    <div class="stat-label">Resolved / Closed</div>
                </div>
            </section>

            <!-- Complaints Management -->
            <section id="complaints" class="card">
                <h3>All Complaints (Priority View)</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Priority</th>
                                <th>Student</th>
                                <th>Room</th>
                                <th>Complaint</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Assigned To</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result_complaints->num_rows > 0): ?>
                                <?php while ($row = $result_complaints->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <span class="badge badge-<?php echo strtolower($row['priority']); ?>">
                                                <?php echo $row['priority']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($row['student_name']); ?>
                                            <div style="font-size: 0.75rem; color: #6b7280;">ID: <?php echo $row['user_id']; ?>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['room_number']); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($row['title']); ?></strong><br>
                                            <span
                                                style="font-size: 0.85rem; color: #4b5563;"><?php echo htmlspecialchars($row['category']); ?></span>
                                        </td>
                                        <td><?php echo date('M d', strtotime($row['created_at'])); ?></td>
                                        <td>
                                            <span
                                                class="badge status-<?php echo strtolower(str_replace(' ', '', $row['status'])); ?>">
                                                <?php echo $row['status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo $row['assigned_staff'] ? htmlspecialchars($row['assigned_staff']) : '<span style="color:#9ca3af;">--</span>'; ?>
                                        </td>
                                        <td>
                                            <?php if ($row['status'] == 'Submitted' || $row['status'] == 'Assigned'): ?>
                                                <form method="POST" action="" style="display: flex; gap: 0.5rem;">
                                                    <input type="hidden" name="assign_staff" value="1">
                                                    <input type="hidden" name="complaint_id" value="<?php echo $row['id']; ?>">
                                                    <select name="staff_id"
                                                        style="padding: 0.25rem; border: 1px solid #ccc; border-radius: 0.25rem; width: 100px;">
                                                        <option value="">Assign...</option>
                                                        <?php foreach ($staff_options as $staff): ?>
                                                            <option value="<?php echo $staff['id']; ?>">
                                                                <?php echo htmlspecialchars($staff['name']); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="submit"
                                                        style="padding: 0.25rem 0.5rem; background: var(--primary-color); color: white; border: none; border-radius: 0.25rem; cursor: pointer;">OK</button>
                                                </form>
                                            <?php else: ?>
                                                <button disabled
                                                    style="opacity: 0.5; padding: 0.25rem 0.5rem; border: 1px solid #ccc;">Done</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8">No complaints found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Feedback Management -->
            <section id="feedback" class="card">
                <h3>Student Feedback</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Student</th>
                                <th>Complaint</th>
                                <th>Rating</th>
                                <th>Comment</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result_feedback->num_rows > 0): ?>
                                <?php while ($row = $result_feedback->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['complaint_title']); ?></td>
                                        <td>
                                            <span style="color: var(--warning); font-weight: bold;">
                                                <?php echo str_repeat('★', $row['rating']) . str_repeat('☆', 5 - $row['rating']); ?>
                                            </span>
                                            (<?php echo $row['rating']; ?>/5)
                                        </td>
                                        <td><?php echo htmlspecialchars($row['comment']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">No feedback received yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
    <script src="script.js"></script>
</body>

</html>