<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'student') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

// Handle New Complaint Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_complaint'])) {
    $title = $conn->real_escape_string($_POST['title']);
    $description = $conn->real_escape_string($_POST['description']);
    $category = $_POST['category'];
    $priority = $_POST['priority']; // Added priority selection for student as per some requirement, or usually admin sets it? Prompt says "put according to priority" for admin. Let's let student set initial urgency or maybe just default to Low. Prompt says "priority-based handling" but doesn't explicitly say who sets it. Let's allow student to suggest it.

    $sql = "INSERT INTO complaints (user_id, title, description, category, priority) VALUES ('$user_id', '$title', '$description', '$category', '$priority')";
    
    if ($conn->query($sql) === TRUE) {
        $message = "Complaint submitted successfully!";
    } else {
        $message = "Error: " . $conn->error;
    }
}

// Handle Feedback Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_feedback'])) {
    $complaint_id = $_POST['complaint_id'];
    $rating = $_POST['rating'];
    $comment = $conn->real_escape_string($_POST['comment']);

    $sql = "INSERT INTO feedback (complaint_id, user_id, rating, comment) VALUES ('$complaint_id', '$user_id', '$rating', '$comment')";
    if ($conn->query($sql) === TRUE) {
        // Optionally update complaint status to Closed if feedback is given? 
        // Prompt says "if the problem is solved then show completed and then a side thing which shows feedback"
        $conn->query("UPDATE complaints SET status = 'Closed' WHERE id = '$complaint_id'");
        $message = "Feedback submitted successfully!";
    } else {
        $message = "Error: " . $conn->error;
    }
}

// Fetch Student Complaints
$sql_complaints = "SELECT * FROM complaints WHERE user_id = '$user_id' ORDER BY created_at DESC";
$result_complaints = $conn->query($sql_complaints);

// Fetch Completed Complaints for Feedback
$sql_completed = "SELECT * FROM complaints WHERE user_id = '$user_id' AND status = 'Resolved' AND id NOT IN (SELECT complaint_id FROM feedback)";
$result_completed = $conn->query($sql_completed);

// Fetch Feedback History
$sql_feedback_history = "SELECT f.*, c.title FROM feedback f JOIN complaints c ON f.complaint_id = c.id WHERE f.user_id = '$user_id'";
$result_feedback = $conn->query($sql_feedback_history);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - HostelEase</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>HostelEase</h2>
            </div>
            <div class="user-info">
                <p><strong>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></strong></p>
                <p>Hostel: <?php echo htmlspecialchars($_SESSION['hostel_name']); ?></p>
            </div>
            <ul class="sidebar-menu">
                <li class="menu-item"><a href="#new-complaint" class="menu-link active">New Complaint</a></li>
                <li class="menu-item"><a href="#my-complaints" class="menu-link">My Complaints</a></li>
                <li class="menu-item"><a href="#feedback" class="menu-link">Feedback</a></li>
                <li class="menu-item"><a href="logout.php" class="menu-link" style="color: var(--danger);">Logout</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Student Dashboard</h1>
            </div>

            <?php if ($message): ?>
                <div style="background-color: #dcfce7; color: #166534; padding: 1rem; border-radius: 0.5rem; margin-bottom: 2rem;">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- New Complaint Section -->
            <section id="new-complaint" class="card">
                <h3>Add New Complaint</h3>
                <form method="POST" action="">
                    <input type="hidden" name="submit_complaint" value="1">
                    <div class="form-group">
                        <label>Complaint Title</label>
                        <input type="text" name="title" class="form-control" required placeholder="e.g., Leaking Tap in Room 101">
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" class="form-control" required>
                            <option value="Plumbing">Plumbing</option>
                            <option value="Electricity">Electricity</option>
                            <option value="Cleanliness">Cleanliness</option>
                            <option value="Internet">Internet</option>
                            <option value="Room Issues">Room Issues</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Priority (Urgency)</label>
                        <select name="priority" class="form-control" required>
                            <option value="Low">Low</option>
                            <option value="Medium">Medium</option>
                            <option value="High">High</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="4" required placeholder="Describe the issue in detail..."></textarea>
                    </div>
                    <button type="submit" class="btn">Submit Complaint</button>
                </form>
            </section>

            <!-- My Complaints List -->
            <section id="my-complaints" class="card">
                <h3>My Complaints</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>IDs</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result_complaints->num_rows > 0): ?>
                                <?php while($row = $result_complaints->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $row['id']; ?></td>
                                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                                        <td><?php echo htmlspecialchars($row['category']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                        <td>
                                            <span class="badge status-<?php echo strtolower(str_replace(' ', '', $row['status'])); ?>">
                                                <?php echo $row['status']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5">No complaints found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

             <!-- Pending Feedback -->
             <?php if ($result_completed->num_rows > 0): ?>
             <section id="pending-feedback" class="card" style="border: 2px solid var(--warning);">
                <h3>Give Feedback on Resolved Complaints</h3>
                <?php while($row = $result_completed->fetch_assoc()): ?>
                    <div style="padding: 1rem; border-bottom: 1px solid #eee;">
                        <h4><?php echo htmlspecialchars($row['title']); ?></h4>
                        <p><?php echo htmlspecialchars($row['description']); ?></p>
                        <form method="POST" action="" style="margin-top: 1rem;">
                            <input type="hidden" name="submit_feedback" value="1">
                            <input type="hidden" name="complaint_id" value="<?php echo $row['id']; ?>">
                            <div class="form-group">
                                <label>Rating (1-5)</label>
                                <select name="rating" class="form-control" style="width: 100px;">
                                    <option value="5">5 - Excellent</option>
                                    <option value="4">4 - Good</option>
                                    <option value="3">3 - Average</option>
                                    <option value="2">2 - Poor</option>
                                    <option value="1">1 - Very Poor</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <input type="text" name="comment" class="form-control" placeholder="Optional comments...">
                            </div>
                            <button type="submit" class="btn" style="width: auto;">Submit Feedback</button>
                        </form>
                    </div>
                <?php endwhile; ?>
            </section>
            <?php endif; ?>

            <!-- Completed Feedback History -->
            <section id="feedback" class="card">
                <h3>My Feedback History</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Complaint</th>
                                <th>Rating</th>
                                <th>Comment</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result_feedback->num_rows > 0): ?>
                                <?php while($row = $result_feedback->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                                        <td><?php echo $row['rating']; ?>/5</td>
                                        <td><?php echo htmlspecialchars($row['comment']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3">No feedback given yet.</td></tr>
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
