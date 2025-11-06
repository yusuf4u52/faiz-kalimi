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

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $its = $_POST['its'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $mobile_no = $_POST['mobile_no'] ?? '';

    if (strlen($its) == 8 && preg_match('/^\d{8}$/', $its)) { 
        $stmt = $link->prepare("UPDATE poona_users SET `its` = ?, `full_name` = ?, `mobile_no` = ? WHERE `id` = ?");
        $stmt->bind_param("sssi", $its, $full_name, $mobile_no, $user_id);      
        if ($stmt->execute()) {
            $success = "Your details are successfully updated.";
        } else {
            $error = "Update failed. Please try again.";
        }
    } else {
        $error = "ITS must be exactly 8 digits.";
    }
}

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
    <div class="container">
        <div class="row">
            <div class="col-12 offset-md-1 col-md-10 offset-xl-2 col-xl-8">
                <div class="card">
                    <div class="card-body">
                        <h2 class="text-center mb-4">Update Details</h2>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        <form method="POST" action="/poona_ashara_ohbat/update_details.php">
                            <div class="mb-3 row">
                                <label for="its" class="col-4 control-label">ITS (8 digit ID)</label>
                                <div class="col-8">
                                    <input type="text" id="its" name="its" maxlength="8" pattern="\d{8}" class="form-control" value="<?php echo(!empty($user['its']) ? $user['its'] : ''); ?>" required />
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label for="full_name" class="col-4 control-label">Full Name</label>
                                <div class="col-8">
                                    <input type="text" id="full_name" name="full_name" pattern="[A-Za-z ]+" class="form-control" value="<?php echo(!empty($user['full_name']) ? $user['full_name'] : ''); ?>" required />
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label for="mobile_no" class="col-4 control-label">Mobile No</label>
                                <div class="col-8">
                                    <input type="number" id="mobile_no" name="mobile_no" maxlength="10" pattern="[0-9]{10}" class="form-control" value="<?php echo(!empty($user['mobile_no']) ? $user['mobile_no'] : ''); ?>" required />
                                </div>
                            </div>                            
                            <div class="mb-3 row">
                                <div class="col-8 offset-4 d-grid">
                                    <button type="submit" class="btn btn-light">Update</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>
