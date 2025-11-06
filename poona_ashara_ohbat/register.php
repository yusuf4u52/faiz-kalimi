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
    $full_name = $_POST['full_name'] ?? '';
    $mobile_no = $_POST['mobile_no'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (strlen($its) == 8 && preg_match('/^\d{8}$/', $its)) {
        if (!empty($password) && $password === $confirm_password) {
            // Check if ITS already exists
            $stmt = $link->prepare("SELECT id FROM poona_users WHERE its = ?");
            $stmt->bind_param("s", $its);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 0) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $link->prepare("INSERT INTO poona_users (`its`, `full_name`, `mobile_no`, `password`) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $its, $full_name, $mobile_no, $hashed_password);
                if ($stmt->execute()) {
                    $success = "Registration successful! You can now <a href='index.php'>login</a>.";
                } else {
                    $error = "Registration failed. Please try again.";
                }
            } else {
                $error = "ITS already registered.";
            }
        } else {
            $error = "Passwords do not match.";
        }
    } else {
        $error = "ITS must be exactly 8 digits.";
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
                        <h2 class="text-center mb-4">Register for Poona Ashara Ohbat</h2>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                         <?php if ( isset($_GET['msg']) && $_GET['msg'] == 'user_not_exists'): ?>
                            <div class="alert alert-success">Your are not registered. Please register here.</div>
                        <?php endif; ?>
                        <form method="POST" action="/poona_ashara_ohbat/register.php">
                            <div class="mb-3 row">
                                <label for="its" class="col-4 control-label">ITS (8 digit ID)</label>
                                <div class="col-8">
                                    <input type="text" id="its" name="its" maxlength="8" pattern="\d{8}" class="form-control" required />
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label for="full_name" class="col-4 control-label">Full Name</label>
                                <div class="col-8">
                                    <input type="text" id="full_name" name="full_name" pattern="[A-Za-z ]+" class="form-control" required />
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label for="mobile_no" class="col-4 control-label">Mobile No</label>
                                <div class="col-8">
                                    <input type="number" id="mobile_no" name="mobile_no" maxlength="10" pattern="[0-9]{10}" class="form-control" required />
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label for="password" class="col-4 control-label">Password</label>
                                <div class="col-8">
                                    <input type="password" id="password" name="password" class="form-control" required />
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
                                    <button type="submit" class="btn btn-light">Register</button>
                                </div>
                            </div>
                        </form>
                        <div class="mb-3 row">
                            <div class="col-8 offset-4">
                                <p>Already have an account? <a href="index.php">Login here</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>
