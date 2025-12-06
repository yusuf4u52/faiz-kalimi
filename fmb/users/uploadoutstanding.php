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
                <h2 class="mb-3">Upload FMB Report</h2>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <?php if (in_array($_SESSION['email'], array('tinwalaabizer@gmail.com', 'moizlife@gmail.com'))) { ?>
                    <form id="uploadreciept" class="form-horizontal my-3" method="POST"
                        action="uploadoutstanding.php" enctype="multipart/form-data" autocomplete="off">
                        <div class="mb-3 row">
                            <label for="import_reciept" class="col-4 control-label">Import FMB Report</label>
                            <div class="col-4">
                                <input type="file" class="form-control" name="import_reciept" accept=".xls,.xlsx" id="import_reciept">
                            </div>
                            <div class="col-4">
                                <button class="btn btn-light" type="submit" name="import">Import</button>
                            </div>
                        </div>
                    </form>
                <?php } ?>
                <?php if (isset($_POST['import']) && isset($_FILES['import_reciept'])) {
                    $filePath = $_FILES['import_reciept']['tmp_name'];

                    // Load the XLSX file
                    $spreadsheet = IOFactory::load($filePath);
                    $sheet = $spreadsheet->getActiveSheet();
                    $rows = $sheet->toArray();
                    $headers = array_shift($rows);
                    // Skip header row and loop through the data
                    foreach ($rows as $row) {
                        $its = $row[0];
                        $outstanding = str_replace(',', '', $row[3]);
                        $outstanding = intval($outstanding);
                        $takmeem = intval($row[6]); 
                        if( $takmeem > $outstanding ) {
                            $prev = 0;
                            $paid = $takmeem - $outstanding;
                        } else {
                            $prev = $outstanding - $takmeem;
                            $paid = 0;
                        }
                        $sql = "UPDATE  thalilist SET `Previous_Due` = '" . $prev . "', `yearly_hub` = '" . $takmeem . "', `Paid` = '" . $paid . "' WHERE `ITS_No` = '".$its."'";
                        mysqli_query($link,$sql) or die(mysqli_error($link));
                        echo '<h4>'.$its.' data updated successfully</h4>';
                    }
                } else {
                    //echo "No file uploaded.";
                } ?>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>
