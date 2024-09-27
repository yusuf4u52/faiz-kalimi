<?php

function content_display()
{
    $id = getAppData('arg1');
    ?>
    <div class="row">
        <div class="col-8"><h5>Sector/Subsector Report</h5></div>
        <div class="col-4" style="text-align: right;"><a href="<?=getAppData('BASE_URI')?>/report" class="btn btn-warning">Back</a></div>
    </div>        
    <?php
    $result = get_miqaat_sector_count($id);
    if( is_record_found($result) ) {
        $data = $result->data;
        $hdr = ['Sector', 'Subsector', 'Count'];
        $cols = ['sector', 'subsector', 'count'];
        ?>
        <div class='col-xs-12'>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <?php
                    echo '<tr><th>' . implode('</th><th>', $hdr) . '</th></tr>';
                    foreach ($data as $row) {
                        echo '<tr>';
                        foreach ($cols as $col) {
                            echo "<td>{$row["$col"]}</td>";
                        }
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