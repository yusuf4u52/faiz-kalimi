<?php
session_start();
include('../fmb/users/connection.php');
include('header.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$is_admin = $_SESSION['is_admin'];

// Verify user exists in database
$stmt = $link->prepare("SELECT id, full_name, is_admin FROM poona_users WHERE id = ?");
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
<header class="header">
    <div class="container py-2">
        <div class="row align-items-center">
            <div class="col-4">
                <a href="/poona_ashara_ohbat/dashboard.php"><img class="img-fluid" src="ya-hussain.png" alt="Ya Hussain" width="121" height="121" /></a>
            </div>
            <div class="col-8 text-end">
                <p class="text-capitalize m-0 fw-bold fst-italic">Salaam, <?php echo strtolower($full_name); ?></p>
                <p class="m-0"><strong>ITS: <?php echo htmlspecialchars($_SESSION['its']); ?></strong></p>
            </div>
        </div>
    </div>
    <nav class="navbar">
        <div class="container">
            <a class="navbar-brand" href="/fmb/users/index.php">Poona Ashara Ohbat</a>
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
                            <a class="nav-link" href="admin_counters.php">Manage Counters</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</header>
<div class="content mt-4">
    <div class="container">
        <div class="row">
            <div class="col-12 offset-sm-1 col-sm-10 offset-lg-2 col-lg-8">
                <div class="card"> 
                    <div class="card-body">
                        <h2 class="text-center mb-4">Your Recitations</h2>  
                        <?php if ($message): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                        <?php endif; ?>
                        <form method="POST" action="">
                            <div class="text-center mb-4">
                                <button type="submit" class="btn btn-light">Update Recitations</button>
                            </div>
                            <?php foreach ($counter_types as $type): ?>
                                <div class="mb-3 row">
                                    <label for="label-<?php echo $type['id']; ?>" class="col-6 control-label" id="label-<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></label>
                                    <div class="col-6">     
                                        <div class="input-group">
                                            <button class="btn btn-light btn-counter" type="button" onclick="changeCount(<?php echo $type['id']; ?>, -1)">-</button>
                                            <input type="number" class="form-control" name="counter_<?php echo $type['id']; ?>" id="input-<?php echo $type['id']; ?>" value="<?php echo $recitations[$type['id']]; ?>" min="0" readonly>
                                            <button class="btn btn-light btn-counter" type="button" onclick="changeCount(<?php echo $type['id']; ?>, 1)">+</button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-light">Update Recitations</button>
                            </div>
                        </form>
                    </div>
                    <script>
                        function changeCount(type, delta) {
                            const input = document.getElementById('input-' + type);
                            let current = parseInt(input.textContent);
                            current += delta;
                            if (current < 0) current = 0;
                            input.value = current;
                        }
                    </script>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>
