<?php

function content_display()
{
    $uri = getAppData('BASE_URI');
    ?>
    <div class="row">
        <div class="col-8"><h5>Miqaats Report</h5></div>
        <div class="col-4" style="text-align: right;"><a href="<?=$uri?>/add_miqaat" class="btn btn-warning">Add</a></div>
    </div>        
    <?php
    // id,name,details,
    // CONVERT_TZ(start_datetime, "+00:00","+05:30") as start_datetime,
    // CONVERT_TZ(end_datetime, "+00:00","+05:30") as end_datetime,
    // survey_for
    $result = get_miqaat_list();
    if( is_record_found($result) ) {
        $data = $result->data;
        $hdr = ['ID', 'Name', 'Details', 'Start', 'End', 'Action'];
        $cols = ['id', 'name', 'details', 'start_datetime', 'end_datetime'];
        ?>
        <div class='col-xs-12'>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <?php
                    echo '<tr><th>' . implode('</th><th>', $hdr) . '</th></tr>';
                    foreach ($data as $row) {
                        $id = $row['id'];
                        echo '<tr>';
                        foreach ($cols as $col) {
                            echo "<td>{$row["$col"]}</td>";
                        }                        
                        echo "<td><a href='$uri/add_miqaat/$id'>Edit</a></td>";
                        echo '</tr>';
                    }
                    ?>
                </table>
            </div>
        </div>
        <?php
    } else {
        echo 'Ops! no record found.';
    }    
} ?>