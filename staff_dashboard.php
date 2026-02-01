<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'staff') {
    header("Location: index.php");
    exit();
}

$staff_id = $_SESSION['user_id'];
$message = "";

// Handle Status Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $complaint_id = $_POST['complaint_id'];
    $new_status = $_POST['status'];

    // Use prepared statements for security
    $stmt = $conn->prepare("UPDATE complaints SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $complaint_id);

    if ($stmt->execute()) {
        $message = "Status updated to " . htmlspecialchars($new_status);
    } else {
        $message = "Error updating status: " . $conn->error;
    }
    $stmt->close();
}

// Fetch Assigned Complaints
$sql_tasks = "
    SELECT c.*, u.name as student_name, u.hostel_name, u.room_number, a.assigned_at
    FROM complaints c
    JOIN assignments a ON c.id = a.complaint_id
    JOIN users u ON c.user_id = u.id
    WHERE a.staff_id = '$staff_id'
    ORDER BY FIELD(c.status, 'Assigned', 'In Progress', 'Resolved', 'Closed'), c.priority = 'High' DESC, c.created_at ASC
";
$result_tasks = $conn->query($sql_tasks);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - HostelEase</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="logo.jpeg" alt="Logo"
                    style="width: 40px; height: 40px; border-radius: 50%; margin-bottom: 0.5rem;">
                <h2>HostelEase</h2>
                <span class="badge" style="background-color: #e2e8f0; color: #475569;">STAFF</span>
            </div>
            <div class="user-info">
                <p><strong>
                        <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    </strong></p>
                <p>Maintenance Staff</p>
            </div>
            <ul class="sidebar-menu">
                <li class="menu-item"><a href="#tasks" class="menu-link active">My Tasks</a></li>
                <li class="menu-item"><a href="logout.php" class="menu-link" style="color: var(--danger);">Logout</a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Staff Dashboard</h1>
            </div>

            <?php if ($message): ?>
                <div
                    style="background-color: #dcfce7; color: #166534; padding: 1rem; border-radius: 0.5rem; margin-bottom: 2rem;">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Assigned Tasks -->
            <section id="tasks" class="card">
                <h3>Assigned Maintenance Requests</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Priority</th>
                                <th>Issue Details</th>
                                <th>Location</th>
                                <th>Assigned Date</th>
                                <th>Current Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result_tasks->num_rows > 0): ?>
                                <?php while ($row = $result_tasks->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <span class="badge badge-<?php echo strtolower($row['priority']); ?>">
                                                <?php echo $row['priority']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong>
                                                <?php echo htmlspecialchars($row['title']); ?>
                                            </strong><br>
                                            <span style="font-size: 0.85rem; color: #4b5563;">
                                                <?php echo htmlspecialchars($row['description']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($row['hostel_name']); ?><br>
                                            Room:
                                            <?php echo htmlspecialchars($row['room_number']); ?>
                                        </td>
                                        <td>
                                            <?php echo date('M d, H:i', strtotime($row['assigned_at'])); ?>
                                        </td>
                                        <td>
                                            <span
                                                class="badge status-<?php echo strtolower(str_replace(' ', '', $row['status'])); ?>">
                                                <?php echo $row['status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($row['status'] != 'Resolved' && $row['status'] != 'Closed'): ?>
                                                <form method="POST" action="">
                                                    <input type="hidden" name="update_status" value="1">
                                                    <input type="hidden" name="complaint_id" value="<?php echo $row['id']; ?>">

                                                    <?php if ($row['status'] == 'Assigned'): ?>
                                                        <button type="submit" name="status" value="In Progress" class="btn"
                                                            style="padding: 0.25rem 0.5rem; font-size: 0.8rem; width: auto; background-color: var(--warning);">Start
                                                            Work</button>
                                                    <?php elseif ($row['status'] == 'In Progress'): ?>
                                                        <button type="submit" name="status" value="Resolved" class="btn"
                                                            style="padding: 0.25rem 0.5rem; font-size: 0.8rem; width: auto; background-color: var(--success);">Mark
                                                            Resolved</button>
                                                    <?php endif; ?>
                                                </form>
                                            <?php else: ?>
                                                <span style="color: var(--success); font-size: 1.2rem;">&#10003;</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6">No tasks assigned yet.</td>
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