<?php
if_not_post_redirect('/home');

do_for_post('_manage_upload');

function content_display() {
    ?>
    <div class="card">
        <div class="card-header row">
            <div class="col-6">Previous Year Vajebaat Data Upload Result</div>
            <div class="card-body">
                <?= getAppData('page_data') ?>
            </div>
        </div>
    </div>
    <?php
}

function _manage_upload() {
    $home_link = '/home';
    $target_file = null;
    try {
        $target_dir = "./uploads/";
        $target_file = $target_dir . basename($_FILES["vajebaat_prev_file"]["name"]);
        $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if (file_exists($target_file)) {
            do_redirect_with_message($home_link, 'Sorry, file already exists.');
        }

        $file_size = $_FILES["vajebaat_prev_file"]["size"];
        if ($file_size > 1000000) {
            do_redirect_with_message($home_link, 'Sorry, your file is too large (' . $file_size . ') expected is <= 1000000.');
        }

        if ($fileType !== "xlsx") {
            do_redirect_with_message($home_link, "Sorry, only xlsx file is allowed. ($fileType)");
        }

        if (move_uploaded_file($_FILES["vajebaat_prev_file"]["tmp_name"], $target_file)) {
            include_once './simple_xlsx/SimpleXLSX.php';

            $page_data = '<h1>Previous Year Vajebaat Data Upload</h1><pre>';

            if ($xlsx = SimpleXLSX::parse($target_file)) {
                $first = true;
                $page_data .= '<table border=1>';
                $query = '';
                $error_found = 0;

                foreach ($xlsx->rows() as $row) {
                    if ($first) {
                        $first = false;
                        if (empty($row)) {
                            continue;
                        }
                        $page_data .= '<tr><th>Status</th><th>' . implode('</th><th>', $row) . '</th></tr>';

                        // Expect header columns to match kl_shehrullah_vajebaat_prev table:
                        // hijri_year,its_id,vajebaat_prev,annual_niyaz_prev,ikram_prev,husaini_scheme_status_prev
                        $query = 'INSERT INTO kl_shehrullah_vajebaat_prev (' . implode(',', $row) . ')
                                VALUES (' . str_repeat("?,", count($row) - 1) . '?)';
                        array_shift($row);
                        $query .= ' ON DUPLICATE KEY UPDATE ' . implode('=?,', $row) . '=?;';
                    } else {
                        // skip completely empty rows
                        $nonEmpty = array_filter($row, function ($v) {
                            return strlen(trim((string)$v)) > 0;
                        });
                        if (empty($nonEmpty)) {
                            continue;
                        }

                        $row2 = $row;
                        array_shift($row);
                        $finalData = array_merge($row2, $row);

                        $rslt = run_statement($query, $finalData);
                        if (!$rslt->success) {
                            $error_found++;
                            $msg = $rslt->message ?? 'Unknown Error';
                            $page_data .= "<tr><td>$msg</td><td>" . implode('</td><td>', $row2) . '</td></tr>';
                        } else {
                            $page_data .= "<tr><td>SUCCESS</td><td>" . implode('</td><td>', $row2) . '</td></tr>';
                        }
                    }
                }

                $page_data .= '</table>';

                if ($error_found === 0) {
                    $page_data .= '<h2>Previous year Vajebaat data uploaded successfully.</h2>';
                } else {
                    $page_data .= '<h2>Previous year Vajebaat data uploaded with errors.</h2>';
                }

                setAppData('page_data', $page_data);
            } else {
                do_redirect_with_message($home_link, SimpleXLSX::parseError());
            }
        } else {
            do_redirect_with_message($home_link, "Sorry, there was an error uploading your file.");
        }
    } catch (Exception $e) {
        do_redirect_with_message($home_link, 'Exception: ' . $e->getMessage());
    } finally {
        if (isset($target_file)) {
            @unlink($target_file);
        }
    }
}

?>

