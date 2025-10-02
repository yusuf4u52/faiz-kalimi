<?php
session_start();
include('../fmb/users/connection.php');

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

// Handle add or update counter type
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
        $error = "Counter name cannot be empty.";
    } else {
        if ($id) {
            // Update existing
            $stmt = $link->prepare("UPDATE poona_counter_types SET name = ? WHERE id = ?");
            $stmt->bind_param("si", $name, $id);
            if ($stmt->execute()) {
                $success = "Counter updated successfully.";
            } else {
                $error = "Failed to update counter.";
            }
        } else {
            // Insert new
            $stmt = $link->prepare("INSERT INTO poona_counter_types (name) VALUES (?)");
            $stmt->bind_param("s", $name);
            if ($stmt->execute()) {
                $success = "Counter added successfully.";
            } else {
                $error = "Failed to add counter.";
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    $stmt = $link->prepare("DELETE FROM poona_counter_types WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $success = "Counter deleted successfully.";
    } else {
        $error = "Failed to delete counter.";
    }
}

// Fetch all counters
$result = $link->query("SELECT * FROM poona_counter_types ORDER BY id ASC");
$counters = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $counters[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include('../fmb/users/header.php'); ?>
    <title>Admin - Manage Counters</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
</head>
<body>
<div class="container mt-5">
    <h2>Manage Counters</h2>
    <p><a href="dashboard.php" class="btn btn-secondary mb-3">Back to Dashboard</a></p>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form method="POST" class="mb-4">
        <input type="hidden" name="id" id="counter-id" value="" />
        <div class="mb-3">
            <label for="name" class="form-label">Counter Name</label>
            <input type="text" name="name" id="counter-name" class="form-control" required />
        </div>
    <div class="mb-3" style="display:none;">
        <label for="icon" class="form-label">Icon (FontAwesome class)</label>
        <input type="text" name="icon" id="counter-icon" class="form-control" placeholder="e.g. fas fa-pray" />
        <small class="form-text text-muted">Use FontAwesome icon classes.</small>
    </div>
        <button type="submit" class="btn btn-primary">Save Counter</button>
        <button type="button" class="btn btn-secondary" onclick="clearForm()">Clear</button>
    </form>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Name</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($counters as $counter): ?>
                <tr>
                    <td><?php echo htmlspecialchars($counter['name']); ?></td>
                    <td>
                        <button class="btn btn-sm btn-info" onclick="editCounter(<?php echo $counter['id']; ?>, '<?php echo htmlspecialchars(addslashes($counter['name'])); ?>')">Edit</button>
                        <a href="?delete=<?php echo $counter['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this counter?');">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($counters)): ?>
                <tr><td colspan="2" class="text-center">No counters found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function editCounter(id, name) {
    document.getElementById('counter-id').value = id;
    document.getElementById('counter-name').value = name;
    window.scrollTo(0, 0);
}
function clearForm() {
    document.getElementById('counter-id').value = '';
    document.getElementById('counter-name').value = '';
}
</script>
</body>
</html>
