<?php
include('header.php');
include('navbar.php');
?>

<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-12">
                <h2 class="mb-3">My Details</h2>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <div class="fw-bold">Name</div>
                        <?php echo e((string) $values['Name']); ?>
                    </li>
                    <li class="list-group-item">
                        <div class="fw-bold">Contact</div>
                        <?php echo e((string) $values['Mobile']); ?>
                    </li>
                    <li class="list-group-item">
                        <div class="fw-bold">Email</div>
                        <?php echo e((string) $values['Email']); ?>
                    </li>
                    <?php
                    try {
                        $countResult = db_query(
                            $link,
                            "SELECT count(*) as count FROM `thalilist` WHERE `Transporter` LIKE CONCAT('%', ?, '%')",
                            "s",
                            [$_SESSION['transporter']]
                        );
                        $totalThalis = $countResult->num_rows > 0 ? (int) $countResult->fetch_assoc()['count'] : 0;
                    } catch (RuntimeException $e) {
                        error_log('[home.php] ' . $e->getMessage());
                        echo $e->getMessage();
                        $totalThalis = null;
                    }
                    if ($totalThalis !== null) { ?>
                        <li class="list-group-item">
                            <div class="fw-bold">Total Thalis</div>
                            <?php echo (int) $totalThalis; ?>
                        </li>
                    <?php } ?>
                    <li class="list-group-item">
                        <div class="fw-bold">Society</div>
                        <?php
                        try {
                            $societyResult = db_query(
                                $link,
                                "SELECT DISTINCT `Society` FROM `thalilist` WHERE `Transporter` LIKE CONCAT('%', ?, '%') ORDER BY `Society` ASC",
                                "s",
                                [$_SESSION['transporter']]
                            );
                            if ($societyResult->num_rows > 0) {
                                echo '<ul class="list-unstyled">';
                                while ($societyRow = mysqli_fetch_assoc($societyResult)) {
                                    echo '<li>' . e((string) $societyRow['Society']) . '</li>';
                                }
                                echo '</ul>';
                            }
                        } catch (RuntimeException $e) {
                            error_log('[home.php] ' . $e->getMessage());
                            echo '<p class="text-muted mb-0">Unable to load societies right now.</p>';
                        }
                        ?>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>