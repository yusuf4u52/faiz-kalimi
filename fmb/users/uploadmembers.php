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
                <h2 class="mb-3">Upload FMB Members</h2>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <?php if (in_array($_SESSION['email'], array('tinwalaabizer@gmail.com', 'moizagasiyawala@gmail.com'))) { ?>
                    <form id="uploadmembers" class="form-horizontal my-3" method="POST"
                        action="uploadmembers.php" enctype="multipart/form-data" autocomplete="off">
                        <div class="mb-3 row">
                            <label for="import_members" class="col-4 control-label">Import FMB Members</label>
                            <div class="col-4">
                                <input type="file" class="form-control" name="import_members" accept=".xls,.xlsx" id="import_members">
                            </div>
                            <div class="col-4">
                                <button class="btn btn-light" type="submit" name="import">Import</button>
                            </div>
                        </div>
                    </form>
                <?php } ?>
                <?php if (isset($_POST['import']) && isset($_FILES['import_members'])) {
                    $filePath = $_FILES['import_members']['tmp_name'];

                    // Load the XLSX file
                    $spreadsheet = IOFactory::load($filePath);
                    $sheet = $spreadsheet->getActiveSheet();
                    $rows = $sheet->toArray();
                    $headers = array_shift($rows);
                    // Skip header row and loop through the data
                    foreach ($rows as $row) {
                        $hof_its = $row[0];
						$its_no = $row[1];
                        $date = date('Y-m-d', strtotime($row[1]));
                        $thalilist = mysqli_query($link, "SELECT * FROM thalilist WHERE `ITS_No` = '" . $hof_its . "' LIMIT 1");
                        if ($thalilist->num_rows > 0) {
                            $thali = mysqli_fetch_assoc($thalilist);
                            $thalilist_members = mysqli_query($link, "SELECT * FROM thalilist_members WHERE `its_no` = '" . $its_no . "' LIMIT 1");
                            if ($thalilist_members->num_rows > 0) {
                                $members = mysqli_fetch_assoc($thalilist_members);  
                                $sql = "UPDATE thalilist_members SET `thalilist_id` = '" . $thali['id'] . "', `member_type` = '" . $row[2] . "', `full_name` = '" . $row[4] . "', `age` = '" . $row[5] . "', `gender` = '" . $row[6] . "', `mobile` = '" . $row[8] . "' WHERE `its_no` = '" . $its_no . "'";
                                mysqli_query($link,$sql) or die(mysqli_error($link));
                                echo '<h4>'.$its_no.' details updated successfully</h4>';
                            } else {
                                $sql = "INSERT INTO thalilist_members (`thalilist_id`, `its_no`, `member_type`, `full_name`, `age`, `gender`, `mobile`) VALUES ('" . $thali['id'] . "', '" . $its_no . "', '" . $row[2] . "', '" . $row[4] . "', '" . $row[5] . "', '" . $row[6] . "', '" . $row[8] . "')";
                                mysqli_query($link,$sql) or die(mysqli_error($link));
                                echo '<h4>'.$its_no.' details inserted successfully</h4>';
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
