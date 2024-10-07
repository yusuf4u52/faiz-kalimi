<?php
function content_display()
{
    $result = get_miqaat_stats_report();
    $data = $result->data;
    $hdr = ['', 'Total', 'Mardo', 'Bairao', 'Kids', 'Miqaat', 'Start', 'End'];
    $cols = ['total', 'mardo', 'bairo', 'infant', 'name', 'start_datetime', 'end_datetime'];
    ?>
    <h5>Report</h5>
    <div class='col-xs-12'>
        <div class="table-responsive">
            <table class="table table-bordered">
                <?php
                echo '<tr><th>' . implode('</th><th>', $hdr) . '</th></tr>';
                foreach ($data as $row) {
                    echo "<tr><td><a href='sectorwise_report/{$row['id']}'>Sectorwise</a></td>";
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
} ?>