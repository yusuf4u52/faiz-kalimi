<?php
session_start();
include('../fmb/users/connection.php');

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: index.php");
    exit();
}

// Get all counter types
$counter_types = [];
$result = $link->query("SELECT * FROM poona_counter_types ORDER BY id ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $counter_types[] = $row;
    }
}

// Get total users
$total_users = 0;
$result = $link->query("SELECT COUNT(*) as total FROM poona_users");
if ($result) {
    $row = $result->fetch_assoc();
    $total_users = $row['total'];
}

// Get total recitations
$totals = [];
foreach ($counter_types as $type) {
    $stmt = $link->prepare("SELECT SUM(count) as total FROM poona_recitations WHERE counter_type_id = ?");
    $stmt->bind_param("i", $type['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $totals[$type['id']] = $row['total'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include('../fmb/users/header.php'); ?>
    <title>Poona Ashara Ohbat - Admin Report</title>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header text-center">
                        <h2>Admin Report - Total Recitations</h2>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                        </div>
                        <div class="alert alert-info">
                            <strong>Total Users:</strong> <?php echo $total_users; ?>
                        </div>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Recitation Type</th>
                                    <th>Total Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($counter_types as $type): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($type['name']); ?></td>
                                        <td><?php echo $totals[$type['id']] ?? 0; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
