<?php
if_not_post_redirect('/dashboard');

do_for_post('_manage_upload');

function content_display() {
    //$data = getAppData('display_data');
    ?>
    <div class="card">
        <div class="card-header row">
            <div class="col-6">Data Upload Result</div>
            <div class="card-body">
                <?=getAppData('page_data')?>
            </div>
        </div>
    </div>
    <?php
}

function _manage_upload() {
    $home_link = '/dashboard';
    $message = '';
    $target_file = null;
    try {
        $target_dir = "./its_data_files/";
        $target_file = $target_dir . basename($_FILES["itsdatafile"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
        // Check if file already exists
        if (file_exists($target_file)) {
            do_redirect_with_message($home_link, 'Sorry/, file already exists.');
        }
    
        $file_size = $_FILES["itsdatafile"]["size"];
        // Check file size
        if ($file_size > 600000) {
            do_redirect_with_message($home_link, 'Sorry, your file is too large (' . $file_size . ') expected is 600000.');
        }
    
        // Allow certain file formats
        // if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
        if ($imageFileType != "xlsx") {
            do_redirect_with_message($home_link, "Sorry, only xlsx file is allowed. ($imageFileType)");
        }
    
        if (move_uploaded_file($_FILES["itsdatafile"]["tmp_name"], $target_file)) {
    
            include_once './simple_xlsx/SimpleXLSX.php';
    
            $page_data = '<h1>ITS Record Updates</h1><pre>';
    
            if ($xlsx = SimpleXLSX::parse($target_file)) {
                $first = true;
                $page_data .= '<table border=1>';
                $query = '';
    
                $query = "DELETE FROM its_data_transit; INSERT INTO its_data_transit SELECT * FROM its_data; UPDATE its_data_transit SET mohallah='Other';";
                $result = execute_query($query,true, true);
                    
                $error_found = 0;
                foreach ($xlsx->rows() as $row) {
                    if ($first) {
                        $first = false;
                        //$page_data .= '<tr><th>Error</th><th>' . implode('</th><th>', $row) . '</th></tr>';
    
                        // add_to_transit($row[0],$row[1],$row[2],$row[3],$row[4]
                        // ,$row[7],$row[9],$row[5],$row[10],$row[11]);

                        // $query = 'INSERT INTO ITS_DB_TRANSIT (' . implode(',', $row) . ')
                        //         VALUES (' . str_repeat("?,", count($row) - 1) . '?)';
                        // array_shift($row);
                        // $query .= ' ON DUPLICATE KEY UPDATE
                        //         ' . implode('=?,', $row) . '=?;';
                    } else {
                        // if 3rd colum is blank, means Mumin has taken transfer out but not transfer in elsewhere.
                        if (strlen($row[2]) == 0) {
                            //$page_data .= '<tr><td>IGNORED : ' . implode('</td><td>', $row) . '</td></tr>';
                            continue;
                        }
    
                        // $row2 = $row;
                        // array_shift($row);
    
                        // $finalData = array_merge($row2, $row);
                        
                        /**
                         * A- ITS_ID
                         * B-HOF_ID
                         * C-TanzeemFile_No
                         * D-Full_Name
                         * E-Age
                         * F-Gender
                         * G-Misaq
                         * H,I,j,K
                         * L-Address
                         * M,N,O,P
                         * Q-Sector
                         * R-Sub_Sector
                         * 
                         */
                        // $rslt = add_to_transit($row[0],$row[1],$row[2],$row[3]
                        // ,$row[4],$row[7],$row[9],$row[5],$row[10],$row[11]);
                        // $rslt = add_to_transit($row[0],$row[1],$row[2],$row[3]
                        // ,$row[4],$row[6],$row[11],$row[5],$row[14],$row[15]);
                        $itsid = $row[0];
                        $hofid = $row[1];
                        $sabeel = $row[2];
                        $name = $row[3];
                        $age = $row[4];
                        $gender = $row[5];
                        $misaq = $row[6];
                        $address = $row[7];
                        $sector = $row[8];
                        $subsector = $row[9];
                        $mohalla = 'Kalimi';

                        $query = "INSERT INTO its_data_transit (its_id,hof_id,sabeel_no,full_name,age,
                        misaq,address,gender,sector,subsector,mohallah) 
                VALUES ('$itsid','$hofid','$sabeel','$name','$age','$misaq','$address','$gender','$sector','$subsector','$mohalla') 
                ON DUPLICATE KEY UPDATE hof_id='$hofid',sabeel_no='$sabeel',full_name='$name',
                age='$age',misaq='$misaq',address='$address',
                gender='$gender',sector='$sector',subsector='$subsector',mohallah='$mohalla';";

                        $resp = execute_query($query, true);
                        if( $resp->count > 0) {
                            $page_data .= "<tr><td>DONE : $itsid - $name</td></tr>";
                        } else {
                            $page_data .= "<tr><td>NO CHANGE : $itsid - $name</td></tr>";
                        }                        
                    }
                }
                $page_data .= '</table>';
    
                // if( $error_found == 0 ) {
                //     //move_from_transit_to_its_data();
                    $page_data .= '<h2>ITS Data Updated Successfully</h2>';
                // } else {
                //     $page_data .= '<h2>ITS Data Updated with Errors</h2>';
                // }

                    $query = 'DELETE FROM ITS_DB; INSERT INTO ITS_DB SELECT * FROM ITS_DB_TRANSIT;';
                    $resp = execute_query($query, true, true);

                    setAppData('page_data', $page_data);
                // }
            } else {
                do_redirect_with_message($home_link, SimpleXLSX::parseError());
                //redirectWithMessage('dashboard', SimpleXLSX::parseError());
            }
        } else {
            do_redirect_with_message($home_link, "Sorry, there was an error uploading your file.");
        }
    } catch (Exception $e) {
        do_redirect_with_message($home_link, 'Exception: ' . $e->getMessage());
    } finally {
        if (isset($target_file)) {
            unlink($target_file);
        }
    }
}

?>