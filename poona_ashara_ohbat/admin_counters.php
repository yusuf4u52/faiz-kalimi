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

$error = '';
$success = '';

// Handle add or update counter type
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    if ($name === '') {
        $error = "Counter name cannot be empty.";
    } else {
        if ($id) {
            // Update existing
            $stmt = $link->prepare("UPDATE poona_counter_types SET name = ? AND category = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $category, $id);
            if ($stmt->execute()) {
                $success = "Counter updated successfully.";
            } else {
                $error = "Failed to update counter.";
            }
        } else {
            // Insert new
            $stmt = $link->prepare("INSERT INTO poona_counter_types (name) VALUES (?)");
            $stmt->bind_param("s", $name);
            if ($stmt->execute()) {
                $success = "Counter added successfully.";
            } else {
                $error = "Failed to add counter.";
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    $stmt = $link->prepare("DELETE FROM poona_counter_types WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $success = "Counter deleted successfully.";
    } else {
        $error = "Failed to delete counter.";
    }
}

// Fetch all counters
$result = $link->query("SELECT * FROM poona_counter_types ORDER BY id ASC");
$counters = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $counters[] = $row;
    }
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
                        <h2 class="text-center mb-4">Manage Counters</h2>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>
                        <form method="POST" class="mb-4">
                            <input type="hidden" name="id" id="counter-id" value="" />
                            <div class="mb-3 row">
                                <label for="name" class="control-label col-4">Category</label>
                                <div class="col-8">
                                    <select name="category" id="category-name" class="form-select" required>
                                        <option value="">Select Category</option>
                                        <option value="Quran">Quran</option>
                                        <option value="Dua">Dua</option>
                                        <option value="Tasbih">Tasbih</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label for="name" class="control-label col-4">Counter Name</label>
                                <div class="col-8">
                                    <input type="text" name="name" id="counter-name" class="form-control" required />
                                </div>
                            </div>
                            <div class="mb-3 row" style="display:none;">
                                <label for="icon" class="control-label col-4">Icon (FontAwesome class)</label>
                                <div class="col-8">
                                    <input type="text" name="icon" id="counter-icon" class="form-control" placeholder="e.g. fas fa-pray" />
                                    <small class="form-text text-muted">Use FontAwesome icon classes.</small>
                                </div>
                            </div>
                            <div class="mb-3 row">
		                        <div class="col-8 offset-4">
                                    <button type="submit" class="btn btn-light me-2">Save Counter</button>
                                    <button type="button" class="btn btn-light" onclick="clearForm()">Clear</button>
                                </div>
                            </div>
                        </form>
                        <table class="table table-striped display" width="100%">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($counters as $counter): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($counter['name']); ?></td>
                                        <td><?php echo htmlspecialchars($counter['category']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-light" onclick="editCounter(<?php echo $counter['id']; ?>, '<?php echo htmlspecialchars(addslashes($counter['name'])); ?>', '<?php echo htmlspecialchars(addslashes($counter['category'])); ?>')"><i class="bi bi-pencil-square"></i></button>
                                            <a href="?delete=<?php echo $counter['id']; ?>" class="btn btn-sm btn-light" onclick="return confirm('Are you sure you want to delete this counter?');"><i class="bi bi-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($counters)): ?>
                                    <tr><td colspan="2" class="text-center">No counters found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    function editCounter(id, name, category) {
        document.getElementById('counter-id').value = id;
        document.getElementById('category-name').value = category;
        document.getElementById('counter-name').value = name;
        window.scrollTo(0, 0);
    }
    function clearForm() {
        document.getElementById('counter-id').value = '';
        document.getElementById('category-name').value = '';
        document.getElementById('counter-name').value = '';
    }
</script>

<?php include('footer.php'); ?>
