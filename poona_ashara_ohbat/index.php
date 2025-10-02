<?php
session_start();
include('../fmb/users/connection.php');

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $its = $_POST['its'] ?? '';
    $password = $_POST['password'] ?? '';

    if (strlen($its) == 8 && !empty($password)) {
        $stmt = $link->prepare("SELECT id, password, is_admin FROM poona_users WHERE its = ?");
        $stmt->bind_param("s", $its);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['its'] = $its;
                $_SESSION['is_admin'] = $row['is_admin'];
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid ITS or password.";
            }
        } else {
            $error = "Invalid ITS or password.";
        }
    } else {
        $error = "Please enter valid ITS and password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include('../fmb/users/header.php'); ?>
    <title>Poona Ashara Ohbat - Login</title>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header text-center">
                        <h2>Login</h2>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
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
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Login</button>
                            </div>
                        </form>
                        <div class="text-center mt-3">
                            <p>Don't have an account? <a href="register.php">Register here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
