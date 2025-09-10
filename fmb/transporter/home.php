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
                        <?php echo $values['Name']; ?>
                    </li>
                    <li class="list-group-item">
                        <div class="fw-bold">Contact</div>
                        <?php echo $values['Mobile']; ?>
                    </li>
                    <li class="list-group-item">
                        <div class="fw-bold">Email</div>
                        <?php echo $values['Email']; ?>
                    </li>
                    <?php $count = mysqli_query($link, "SELECT count(*) as count FROM thalilist WHERE Transporter LIke '%".$_SESSION['transporter']."%'");
                    if($count->num_rows > 0) {
                        $count = mysqli_fetch_assoc($count)['count']; ?>
                        <li class="list-group-item">
                            <div class="fw-bold">Total Thalis</div>
                            <?php echo $count; ?>
                        </li>
                    <?php } ?>
                    <li class="list-group-item">
                        <div class="fw-bold">Society</div>
                        <?php $details = mysqli_query($link, "SELECT DISTINCT Society FROM thalilist WHERE Transporter LIke '%".$_SESSION['transporter']."%' ORDER BY Society ASC");
                        if($details->num_rows > 0) {
                            echo '<ul class="list-unstyled">';
                                while ($values = mysqli_fetch_assoc($details)) { ?>
                                    <?php echo '<li>'.$values['Society'].'</li>'; ?>
                                <?php } 
                            echo '</ul>';
                        } ?>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>