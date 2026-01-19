<?php

function content_display()
{
    $id = getAppData('arg1');
    ?>
    <div class="row align-items-center">
        <div class="col-7">
            <h4 class="mb-3">Miqaat Attendance Missing</h4>
        </div>
        <div class="col-5 text-end">
            <a href="<?=getAppData('BASE_URI')?>/report" class="btn btn-light">Back</a>
        </div>
    </div>       
    <?php
    $result = get_missing_hof($id);
    if( is_record_found($result) ) {
        $data = $result->data;
        $hdr = ['HOF ID', 'Name', 'Sector', 'Subsector'];
        $cols = ['hof_id','full_name','sector', 'subsector'];
        ?>
        <div class="table-responsive mt-4">
            <table class="table table-striped display" width="100%">
                <?php
                echo '<thead><tr><th>' . implode('</th><th>', $hdr) . '</th></tr></thead>';
                echo '<tbody>';
                    foreach ($data as $row) {
                        echo '<tr>';
                        foreach ($cols as $col) {
                            echo "<td>{$row["$col"]}</td>";
                        }
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