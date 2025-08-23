<?php
function content_display()
{
    $result = get_miqaat_stats_report();
    $data = $result->data;
    $hdr = ['', 'Total', 'Mardo', 'Bairao', 'Kids', 'Miqaat', 'Start', 'End'];
    $cols = ['total', 'mardo', 'bairo', 'infant', 'name', 'start_datetime', 'end_datetime'];
    ?>
    <h4 class="mb-3">Report</h5>
    <div class="table-responsive">
        <table class="table table-striped display" width="100%">
            <?php
            echo '<thead><tr><th>' . implode('</th><th>', $hdr) . '</th></tr></thead>';
            echo '<tbody>';
                foreach ($data as $row) {
                    echo "<tr><td><a href='sectorwise_report/{$row['id']}'>Sectorwise</a></td>";
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
} ?>