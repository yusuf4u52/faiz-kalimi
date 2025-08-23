<?php

function content_display()
{
    $uri = getAppData('BASE_URI');
    ?>
    <div class="row align-items-center">
        <div class="col-7">
            <h4 class="mb-3">Miqaats Report</h4>
        </div>
        <div class="col-5 text-end">
            <a href="<?=$uri?>/add_miqaat" class="btn btn-light mb-3">Add Miqaat</a>
        </div>
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
        <div class="table-responsive mt-4">
            <table class="table table-striped display" width="100%">
                <?php
                echo '<thead><tr><th>' . implode('</th><th>', $hdr) . '</th></tr></thead>';
                echo '<tbody>';
                foreach ($data as $row) {
                    $id = $row['id'];
                    echo '<tr>';
                        foreach ($cols as $col) {
                            echo "<td>{$row["$col"]}</td>";
                        }                        
                        echo "<td><a class='btn btn-light' href='$uri/add_miqaat/$id'><i class='bi bi-pencil-square'></i></a></td>";
                    echo '</tr>';
                }
                echo '</tbody>';
                ?>
            </table>
        </div>
        <?php
    } else {
        echo '<h5 class="mt-4">Ops! no record found.</h5>';
    }    
} ?>