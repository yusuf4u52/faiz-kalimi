<?php
session_start();
include('../fmb/users/connection.php');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $its = $_POST['its'] ?? '';
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
                $stmt = $link->prepare("INSERT INTO poona_users (its, password) VALUES (?, ?)");
                $stmt->bind_param("ss", $its, $hashed_password);
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

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include('../fmb/users/header.php'); ?>
    <title>Poona Ashara Ohbat - Register</title>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header text-center">
                        <h2>Register for Poona Ashara Ohbat</h2>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="its" class="form-label">ITS (8 digit ID)</label>
                                <input type="text" id="its" name="its" maxlength="8" required pattern="\d{8}" class="form-control" />
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" id="password" name="password" required class="form-control" />
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" required class="form-control" />
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Register</button>
                            </div>
                        </form>
                        <div class="text-center mt-3">
                            <p>Already have an account? <a href="index.php">Login here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
