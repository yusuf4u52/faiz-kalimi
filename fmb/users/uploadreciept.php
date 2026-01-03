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
                <h2 class="mb-3">Upload FMB Reciept</h2>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <?php if (in_array($_SESSION['email'], array('tinwalaabizer@gmail.com', 'moizagasiyawala@gmail.com'))) { ?>
                    <form id="uploadreciept" class="form-horizontal my-3" method="POST"
                        action="uploadreciept.php" enctype="multipart/form-data" autocomplete="off">
                        <div class="mb-3 row">
                            <label for="import_reciept" class="col-4 control-label">Import FMB Reciept</label>
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
                        $Receipt_No  = 'FMB-'.$row[0];
                        $its = $row[2];
                        $date = date('Y-m-d', strtotime($row[1]));
                        $thalilist = mysqli_query($link, "SELECT * FROM thalilist WHERE `ITS_No` = '" . $its . "' LIMIT 1");
                        if ($thalilist->num_rows > 0) {
                            $thali = mysqli_fetch_assoc($thalilist);
                            $receipts = mysqli_query($link, "SELECT * FROM receipts WHERE `Receipt_No` = '" . $Receipt_No . "' LIMIT 1");
                            if ($receipts->num_rows > 0) {
                                $rec = mysqli_fetch_assoc($receipts);  
                                $sql = "UPDATE receipts SET `Thali_No` = '" . $thali['Thali'] . "', `userid` = '" . $thali['id'] . "', `name` = '" . $thali['NAME'] . "', `Amount` = '" . $row[6] . "', `Date` = '" . $date . "', `received_by` = 'saminabarnagarwala2812@gmail.com', `payment_type` = '" . $row[9] . "' WHERE `Receipt_No ` = '".$rec['Receipt_No']."'";
                                mysqli_query($link,$sql) or die(mysqli_error($link));
                                echo '<h4>'.$its.' reciept updated successfully</h4>';
                            } else {
                                $sql = "INSERT INTO receipts (`Receipt_No`, `Thali_No`, `userid`, `name`, `Amount`, `Date`, `received_by`, `payment_type`) VALUES ('" . $Receipt_No . "', '" . $thali['Thali'] . "', '" . $thali['id'] . "', '" . $thali['NAME'] . "', '" . $row[6] . "', '" . $date . "', 'saminabarnagarwala2812@gmail.com', '" . $row[9] . "')";
                                mysqli_query($link,$sql) or die(mysqli_error($link));
                                echo '<h4>'.$its.' reciept inserted successfully</h4>';
                            }
                        }
                    }
                } else {
                    //echo "No file uploaded.";
                } ?>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>
