<?php
session_start();
include('../fmb/users/connection.php');
include('header.php');

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$is_admin = $_SESSION['is_admin'];

// Verify user exists in database
$stmt = $link->prepare("SELECT id, its, full_name, mobile_no, is_admin FROM poona_users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
if (!$user) {
    session_destroy();
    header("Location: index.php?error=user_not_found");
    exit();
}
$full_name = $user['full_name'];
$is_admin = $user['is_admin'];

// Get all counter types
$counter_types = [];
$result = $link->query("SELECT * FROM poona_counter_types ORDER BY category ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $counter_types[] = $row;
    }
}

// Get total users
$result = $link->query("SELECT * FROM poona_users");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
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

<header class="header">
    <div class="container py-2">
        <div class="row align-items-center">
            <div class="col-4">
                <a href="/poona_ashara_ohbat/index.php"><img class="img-fluid" src="ya-hussain.png" alt="Ya Hussain" width="121" height="121" /></a>
            </div>
            <div class="col-8 text-end">
                <p class="text-capitalize m-0 fw-bold fst-italic">Salaam, <?php echo strtolower($full_name); ?></p>
                <p class="m-0"><strong>ITS: <?php echo htmlspecialchars($_SESSION['its']); ?></strong></p>
            </div>
        </div>
    </div>
    <nav class="navbar">
        <div class="container">
            <a class="navbar-brand" href="/poona_ashara_ohbat/dashboard.php">Poona Ashara Ohbat</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#headernavbar"
                aria-controls="headernavbar" aria-expanded="false" aria-label="Toggle navigation">
                <i class="bi bi-list"></i>
            </button>
            <div class="collapse navbar-collapse" id="headernavbar">
                <ul class="navbar-nav me-auto mx-xl-auto">
                    <?php if ($is_admin): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_report.php">View Admin Report</a>
                        </li> 
                        <li class="nav-item">
                            <a class="nav-link" href="admin_user_report.php">View User Report</a>
                        </li> 
                        <li class="nav-item">
                            <a class="nav-link" href="admin_counters.php">Manage Counters</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="update_details.php">Update Details</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="change_password.php">Change Password</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</header>
<div class="content mt-4">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h2 class="text-center mb-4">Admin Report - User Recitations</h2>  
                        <div class="table-responsive">
                            <table id="report" class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ITS No</th>
                                        <th>Full Name</th>
                                        <th>Mobile No</th>
                                        <?php foreach ($counter_types as $type) { 
                                            echo '<th>'.$type['name'].'</th>';
                                        } ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user['its']); ?></td>
                                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                            <td><?php echo $user['mobile_no']; ?></td>
                                            <?php foreach ($counter_types as $type) {
                                                $stmt = $link->prepare("SELECT count FROM poona_recitations WHERE counter_type_id = ? AND user_id = ?");
                                                $stmt->bind_param("ii", $type['id'], $user['id']);
                                                $stmt->execute();
                                                $result = $stmt->get_result();
                                                $row = $result->fetch_assoc();
                                                echo (isset($row['count']) ? '<td>'.$row['count'].'</td>' : '<td>0</td>');
                                            } ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include('footer.php'); ?>
