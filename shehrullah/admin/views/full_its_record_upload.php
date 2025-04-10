<?php
if_not_post_redirect('/home');

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
    $home_link = '/home';
    $message = '';
    $target_file = null;
    try {
        $target_dir = "./uploads/";
        $target_file = $target_dir . basename($_FILES["itsdatafile"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
        // Check if file already exists
        if (file_exists($target_file)) {
            do_redirect_with_message($home_link, 'Sorry/, file already exists.');
        }
    
        $file_size = $_FILES["itsdatafile"]["size"];
        // Check file size
        if ($file_size > 900000) {
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
    
                delete_transit_data();

                
                //$rslt = DataStore::execute('DELETE FROM ITS_DB_TRANSIT; INSERT INTO ITS_DB_TRANSIT SELECT * FROM ITS_DB;UPDATE ITS_DB_TRANSIT SET Jamaat=?;', 'Other');
    
                //$its_id,  $hof_id,    $sabeel_no,$full_name,$age,$email,$address,$gender,$sector,$subsector,$mohallah,$Zone
                //ITS_ID 0	HOF_ID 1	TanzeemFile_No	Full_Name	Age	Gender	Mobile	Email	WhatsApp_No	Address	Sector	Sub_Sector	Sector_Incharge_ITSID	Sector_Incharge_Name	Sector_Incharge_Female_ITSID	Sector_Incharge_Female_Name	Sub_Sector_Incharge_ITSID	Sub_Sector_Incharge_Name	Sub_Sector_Incharge_Female_ITSID	Sub_Sector_Incharge_Female_Name
                $error_found = 0;
                foreach ($xlsx->rows() as $row) {
                    if ($first) {
                        $first = false;
                        $page_data .= '<tr><th>Error</th><th>' . implode('</th><th>', $row) . '</th></tr>';
    
                        // add_to_transit($row[0],$row[1],$row[2],$row[3],$row[4]
                        // ,$row[7],$row[9],$row[5],$row[10],$row[11]);

                        $query = 'INSERT INTO ITS_RECORD_TRANSIT (' . implode(',', $row) . ')
                                VALUES (' . str_repeat("?,", count($row) - 1) . '?)';
                        array_shift($row);
                        $query .= ' ON DUPLICATE KEY UPDATE
                                ' . implode('=?,', $row) . '=?;';
                    } else {
                        // if 3rd colum is blank, means Mumin has taken transfer out but not transfer in elsewhere.
                        if (strlen($row[2]) == 0) {
                            $page_data .= '<tr><td>IGNORED : ' . implode('</td><td>', $row) . '</td></tr>';
                            continue;
                        }
    
                        $row2 = $row;
                        array_shift($row);
    
                        $finalData = array_merge($row2, $row);
    
                        // $rslt = add_to_transit($row[0],$row[1],$row[2],$row[3]
                        // ,$row[4],$row[7],$row[9],$row[5],$row[10],$row[11]);
                        // $rslt = add_to_transit($row[0],$row[1],$row[2],$row[3]
                        // ,$row[4],$row[6],$row[11],$row[5],$row[14],$row[15]);

                        $rslt = run_statement($query, $finalData);//DataStore::execute($query, $finalData);
                        if (!$rslt->success) {
                            $error_found++;
                            $msg = $rslt->message ?? 'Unknown Error';
                            $page_data .= "<tr><td>$msg</td><td>" . implode('</td><td>', $row) . '</td></tr>';
                        } else {
                            $page_data .= "<tr><td>SUCCESS</td><td>" . implode('</td><td>', $row) . '</td></tr>';
                        }
                    }
                }
                $page_data .= '</table>';
    
                if( $error_found == 0 ) {
                    transit_to_main();
                    $page_data .= '<h2>ITS Data Updated Successfully</h2>';
                } else {
                    $page_data .= '<h2>ITS Data Updated with Errors</h2>';
                }

                // $rslt = DataStore::execute('DELETE FROM ITS_DB; INSERT INTO ITS_DB SELECT * FROM ITS_DB_TRANSIT;');
                // if (!$rslt->success) {
                //     //redirectWithMessage('dashboard', $rslt->message);
                //     echo $rslt->message;
                // } else {
                    //redirectWithMessage('dashboard', $page_data);
                    //echo $page_data;
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

function delete_transit_data() {
	
	$query = 'DELETE FROM ITS_RECORD_TRANSIT;';
    $result = run_statement($query);
    return $result->success ? true : false;
}


function transit_to_main()
{
    $query = 'DELETE FROM ITS_RECORD; INSERT INTO ITS_RECORD SELECT * FROM ITS_RECORD_TRANSIT;';
    $result = run_statement($query);
    return $result->success ? true : false;

}

?>