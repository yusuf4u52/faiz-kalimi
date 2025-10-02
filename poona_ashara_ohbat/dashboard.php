<?php
session_start();
include('../fmb/users/connection.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$is_admin = $_SESSION['is_admin'];

// Verify user exists in database
$stmt = $link->prepare("SELECT id, is_admin FROM poona_users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
if (!$user) {
    session_destroy();
    header("Location: index.php?error=user_not_found");
    exit();
}
$is_admin = $user['is_admin'];

// Get all counter types
$counter_types = [];
$result = $link->query("SELECT * FROM poona_counter_types ORDER BY id ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $counter_types[] = $row;
    }
}

// Get current recitations for user
$recitations = [];
foreach ($counter_types as $type) {
    $stmt = $link->prepare("SELECT count FROM poona_recitations WHERE user_id = ? AND counter_type_id = ?");
    $stmt->bind_param("ii", $user_id, $type['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $recitations[$type['id']] = $row ? $row['count'] : 0;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    foreach ($counter_types as $type) {
        $count = (int)($_POST['counter_' . $type['id']] ?? 0);
        $stmt = $link->prepare("INSERT INTO poona_recitations (user_id, counter_type_id, count) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE count = ?");
        $stmt->bind_param("iiii", $user_id, $type['id'], $count, $count);
        $stmt->execute();
        $recitations[$type['id']] = $count;
    }
    $message = "Recitations updated successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include('../fmb/users/header.php'); ?>
    <title>Poona Ashara Ohbat - Dashboard</title>
    <style>
        .counter-card {
            margin-bottom: 20px;
        }
        .counter-display {
            font-size: 2rem;
            font-weight: bold;
            color: #007bff;
        }
        .btn-counter {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-size: 1.2rem;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Welcome</h2>
        <p>ITS: <?php echo htmlspecialchars($_SESSION['its']); ?> | <a href="logout.php">Logout</a></p>
        <?php if ($is_admin): ?>
            <p><a href="admin_report.php">View Admin Report</a> | <a href="admin_counters.php">Manage Counters</a></p>
        <?php endif; ?>
        <?php if ($message): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="row">
                <?php foreach ($counter_types as $type): ?>
                    <div class="col-md-6 counter-card">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <h5 class="card-title mb-0"><?php echo htmlspecialchars($type['name']); ?></h5>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <button type="button" class="btn btn-outline-danger btn-counter me-2" onclick="changeCount(<?php echo $type['id']; ?>, -1)">-</button>
                                        <span class="counter-display" id="count-<?php echo $type['id']; ?>"><?php echo $recitations[$type['id']]; ?></span>
                                        <button type="button" class="btn btn-outline-success btn-counter ms-2" onclick="changeCount(<?php echo $type['id']; ?>, 1)">+</button>
                                    </div>
                                </div>
                                <input type="hidden" name="counter_<?php echo $type['id']; ?>" id="input-<?php echo $type['id']; ?>" value="<?php echo $recitations[$type['id']]; ?>" />
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-4">
                <button type="submit" class="btn btn-primary btn-lg">Update Recitations</button>
            </div>
        </form>
        <script>
            function changeCount(type, delta) {
                const countSpan = document.getElementById('count-' + type);
                const input = document.getElementById('input-' + type);
                let current = parseInt(countSpan.textContent);
                current += delta;
                if (current < 0) current = 0;
                countSpan.textContent = current;
                input.value = current;
            }
        </script>
    </div>
</body>
</html>
