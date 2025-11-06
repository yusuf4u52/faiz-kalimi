<?php
session_start();
include('../fmb/users/connection.php');
include('header.php');

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $its = $_POST['its'] ?? '';
    $mobile_no = $_POST['mobile_no'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $stmt = $link->prepare("SELECT id FROM poona_users WHERE its = ? AND mobile_no = ?");
    $stmt->bind_param("ss", $its, $mobile_no);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        if (!empty($new_password) && $new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $link->prepare("UPDATE poona_users SET `password` = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $row['id']);      
            if ($stmt->execute()) {
                $success = "password_updated.";
                header("Location: index.php?msg=password_updated");
                exit();
            } else {
                $error = "Update failed. Please try again.";
            }
        } else {
            $error = "Passwords do not match.";
        }
    } else {
        header("Location: register.php?msg=user_not_exists");
        exit();
    }
}
?>
<div class="content mt-4">
    <div class="container">
        <div class="row">
            <div class="col-12 offset-md-2 col-md-8 offset-xl-3 col-xl-6">
                <div class="card">
                    <div class="card-body">
                        <a href="/poona_ashara_ohbat/index.php"><img class="img-fluid mx-auto d-block" src="ya-hussain.png" alt="Ya Hussain" width="253" height="253" /></a>
                        <hr>
                        <h2 class="text-center mb-4">Forgot Password</h2>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        <form method="POST" action="/poona_ashara_ohbat/forgot_password.php">
                            <div class="mb-3 row">
                                <label for="its" class="col-4 control-label">ITS (8 digit ID)</label>
                                <div class="col-8">
                                    <input type="text" id="its" name="its" maxlength="8" pattern="\d{8}" class="form-control" required />
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label for="mobile_no" class="col-4 control-label">Mobile No</label>
                                <div class="col-8">
                                    <input type="number" id="mobile_no" name="mobile_no" maxlength="10" pattern="[0-9]{10}" class="form-control" required />
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
