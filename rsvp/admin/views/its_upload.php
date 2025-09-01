<?php
if_not_post_redirect('/dashboard');

do_for_post('_manage_upload');

function content_display() {
    $uri = getAppData('BASE_URI');
    ?>
    <form action="<?=$uri?>/its_upload" method="post">
    <div class="card">
        <div class="card-header row">
            <div class="col-6">Data Upload Result</div>
            <div class="card-body">
                <?=getAppData('page_data')?>
                <input type="hidden" name="action" value="dump"/>
                <h2>Click UPLOAD, if you are sure that data is correct.</h2>
                <button class="btn btn-outline-primary" type="submit" id="button-addon2">UPLOAD</button>
                <a href="<?=$uri?>/dashboard" class="btn btn-outline-danger">CANCEL</a>
            </div>            
        </div>
    </div>
    </form>
    <?php
}

function _manage_upload() {
    $action = $_POST['action'] ?? '';
    if( $action === 'dump') {
         $query = 'DELETE FROM ITS_DB; INSERT INTO ITS_DB SELECT * FROM ITS_DB_TRANSIT;';
         $resp = execute_query($query, true, true);
         do_redirect_with_message('/dashboard', 'Data has been loaded successfully.');
    } else {
        _manage_upload_1();
    }
}

function _manage_upload_1() {
    $home_link = '/dashboard';
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
                        $page_data .= '<tr><th>Status</th><th>' . implode('</th><th>', $row) . '</th></tr>';
                    } else {
                        // if 3rd colum is blank, means Mumin has taken transfer out but not transfer in elsewhere.
                        if (strlen($row[2]) == 0) {
                            $page_data .= '<tr><td><b>IGNORED</b>' . implode('</td><td>', $row) . '</td></tr>';
                            continue;
                        } 
    
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
                            $page_data .= '<tr><td><b>SUCCESS</b>' . implode('</td><td>', $row) . '</td></tr>';
                        } else {
                            $page_data .= '<tr><td><b>NO CHANGE</b>' . implode('</td><td>', $row) . '</td></tr>';
                        }                        
                    }
                }
                $page_data .= '</table>';

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
            unlink($target_file);
        }
    }
}

?>