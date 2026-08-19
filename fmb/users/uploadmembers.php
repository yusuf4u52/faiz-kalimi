<?php
include('header.php');
include('navbar.php');
require_once('helpers.php');
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$canImport = user_email_in(DATA_IMPORT_EMAILS);
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
                <?php if ($canImport) { ?>
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
                <?php
                // The allowlist above only controlled whether the upload
                // *form* was shown — the code that actually processed a
                // submitted file ran for any request with the right POST
                // fields, regardless of who sent it. Re-checking $canImport
                // here closes that gap.
                if ($canImport && isset($_POST['import']) && isset($_FILES['import_members'])) {
                    $filePath = $_FILES['import_members']['tmp_name'];

                    $spreadsheet = IOFactory::load($filePath);
                    $sheet = $spreadsheet->getActiveSheet();
                    $rows = $sheet->toArray();
                    array_shift($rows); // header row

                    foreach ($rows as $row) {
                        $hof_its = trim((string) ($row[0] ?? ''));
                        $its_no = trim((string) ($row[1] ?? ''));
                        if ($hof_its === '' || $its_no === '') {
                            continue;
                        }

                        $thalilistResult = db_query($link, "SELECT id FROM thalilist WHERE `ITS_No` = ? LIMIT 1", "s", [$hof_its]);
                        if ($thalilistResult->num_rows > 0) {
                            $thali = mysqli_fetch_assoc($thalilistResult);

                            $memberParams = [
                                (int) $thali['id'],
                                (string) ($row[2] ?? ''),
                                (string) ($row[4] ?? ''),
                                (int) ($row[5] ?? 0),
                                (string) ($row[6] ?? ''),
                                (string) ($row[8] ?? ''),
                            ];

                            // Single upsert instead of SELECT-then-branch.
                            // Requires UNIQUE KEY (its_no) on thalilist_members
                            // — see schema-modernization.sql.
                            db_query(
                                $link,
                                "INSERT INTO thalilist_members (`thalilist_id`, `its_no`, `member_type`, `full_name`, `age`, `gender`, `mobile`)
                                 VALUES (?, ?, ?, ?, ?, ?, ?)
                                 ON DUPLICATE KEY UPDATE
                                    `thalilist_id` = VALUES(`thalilist_id`),
                                    `member_type` = VALUES(`member_type`),
                                    `full_name` = VALUES(`full_name`),
                                    `age` = VALUES(`age`),
                                    `gender` = VALUES(`gender`),
                                    `mobile` = VALUES(`mobile`)",
                                "isssiss",
                                [$memberParams[0], $its_no, $memberParams[1], $memberParams[2], $memberParams[3], $memberParams[4], $memberParams[5]]
                            );

                            $wasUpdate = mysqli_affected_rows($link) > 1;
                            echo '<h4>' . e($its_no) . ' details ' . ($wasUpdate ? 'updated' : 'inserted') . ' successfully</h4>';
                        }
                    }
                } ?>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>
