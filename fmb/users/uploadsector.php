<?php
include('header.php');
include('navbar.php');
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
?>

<div class="card">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-6">
                <h2 class="mb-3">Upload Sector List</h2>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <?php if (in_array($_SESSION['email'], array('mulla.moiz@gmail.com', 'moizagasiyawala@gmail.com'))) { ?>
                    <form id="uploadsector" class="form-horizontal my-3" method="POST"
                        action="uploadsector.php" enctype="multipart/form-data" autocomplete="off">
                        <div class="mb-3 row">
                            <label for="import_reciept" class="col-4 control-label">Import Sector List</label>
                            <div class="col-4">
                                <input type="file" class="form-control" name="import_sector" accept=".xls,.xlsx" id="import_sector">
                            </div>
                            <div class="col-4">
                                <button class="btn btn-light" type="submit" name="import">Import</button>
                            </div>
                        </div>
                    </form>
                <?php } ?>
                <?php if (isset($_POST['import']) && isset($_FILES['import_sector'])) {
                    $filePath = $_FILES['import_sector']['tmp_name'];

                    // Load the XLSX file
                    $spreadsheet = IOFactory::load($filePath);
                    $sheet = $spreadsheet->getActiveSheet();
                    $rows = $sheet->toArray();
                    $headers = array_shift($rows);
                    // Skip header row and loop through the data
                    foreach ($rows as $row) {
                        $sql = "UPDATE  thalilist SET `sector` = '" . $row[1] . "', `musaid` = '" . $row[2] . "' WHERE `society` = '".$row[0]."'";
                        mysqli_query($link,$sql) or die(mysqli_error($link));
                        echo '<h4>'.$row[0].' data updated successfully</h4>';
                    }
                } else {
                    //echo "No file uploaded.";
                } ?>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>
