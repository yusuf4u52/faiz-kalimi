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
$stmt = $link->prepare("SELECT `id`, `full_name`, `password`, `is_admin` FROM poona_users WHERE id = ?");
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
$stored_password = $user['password'];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (password_verify($old_password, $stored_password)) {
        if (!empty($new_password) && $new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $link->prepare("UPDATE poona_users SET `password` = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $user_id);      
            if ($stmt->execute()) {
                $success = "password_updated";
                session_start();
                session_destroy();
                header("Location: index.php?msg=password_updated");
                exit();
            } else {
                $error = "Update failed. Please try again.";
            }
        } else {
            $error = "Passwords do not match.";
        }
    } else {
        $error = "Incorrect old password.";
    }
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
                        <h2 class="text-center mb-4">Change Password</h2>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        <form method="POST" action="/poona_ashara_ohbat/change_password.php">
                            <div class="mb-3 row">
                                <label for="password" class="col-4 control-label">Old Password</label>
                                <div class="col-8">
                                    <input type="password" id="old_password" name="old_password" class="form-control" required />
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label for="password" class="col-4 control-label">New Password</label>
                                <div class="col-8">
                                    <input type="password" id="new_password" name="new_password" class="form-control" required />
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label for="confirm_password" class="col-4 control-label">Confirm Password</label>
                                <div class="col-8">
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required />
                                </div>
                            </div>                           
                            <div class="mb-3 row">
                                <div class="col-8 offset-4 d-grid">
                                    <button type="submit" class="btn btn-light">Change Password</button>
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
