<?php
session_start();
include('../fmb/users/connection.php');
include('header.php');

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
<div class="content mt-4">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 offset-sm-2 col-sm-8 offset-lg-3 col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <a href="/poona_ashara_ohbat/index.php"><img class="img-fluid mx-auto d-block" src="ya-hussain.png" alt="Ya Hussain" width="253" height="253" /></a>
                        <hr>
                        <h2 class="text-center mb-4">Login</h2>
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
                                <button type="submit" class="btn btn-light">Login</button>
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
</div>

<?php include('footer.php'); ?>
