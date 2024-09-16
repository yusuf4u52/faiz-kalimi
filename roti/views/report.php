<?php
function content_display()
{
    $roti_report_by_type = null;
    $roti_report_by_value = null;
    if (is_post()) {
        $filter = $_POST['sub_sector'];
        list($roti_report_by_type, $roti_report_by_value) = explode("~", $filter);
    }

    $miqaat_result = get_current_or_last_miqaat();
    $miqaat = 0;
    if (is_record_found($miqaat_result)) {
        $miqaat = $miqaat_result->data[0];
    }
    $miqaat_id = $miqaat['id'] ?? 0;
    $miqaat_name = $miqaat['name'] ?? 'Error';

    $result = get_roti_report($miqaat_id, $roti_report_by_value, $roti_report_by_type);
    $data = $result->data;
    $hdr = ['Sabeel Num', 'Packet Count', 'Name', 'Sector', 'Sub Sector', 'Incharge'];
    $cols = ['thali', 'roti_count', 'full_name', 'sectorits', 'subsector', 'incharge_female_fullname'];

    $sector_result = get_sector_list();
    $sector_data = $sector_result->data;
    ?>
    <h5>Report for <?= $miqaat_name ?></h5>
    <div class='col-xs-12'>
        <form action="" method="post">
            <div class="input-group">
                <select class="form-select custom_select" name="sub_sector" id="sub_sector">
                    <?php
                    foreach ($sector_data as $row) {
                        $is_masool = $row['sub_sectorits'] === 'Masoolin' ? true : false;
                        if ($is_masool) {
                            $key = "S~{$row['sector']}";
                        } else {
                            $key = "SS~{$row['subsector']}";
                        }
                        //$value = $row['sectorits'] . ' - ' . $row['sub_sectorits'];
                        $value = $row['sector'] . '-' . $row['subsector'] . ' (' . $row['sectorits'] . ' - ' . $row['sub_sectorits'] . ')';
                        echo "<option value='$key'>$value</option>";
                    }
                    ?>
                </select>
                <div class="input-group-append">
                    <button class="btn btn-success" type="submit">Search</button>
                </div>
            </div>
        </form>
        <br/>
        <table class="table">
            <?php
            echo '<tr><th>' . implode('</th><th>', $hdr) . '</th></tr>';
            $packet_count = 0;
            foreach ($data as $row) {
                echo '<tr>';
                $packet_count += $row['roti_count'];
                foreach ($cols as $col) {
                    echo "<td>{$row["$col"]}</td>";
                }
                echo '</tr>';
            }
            ?>
        </table>
        <h4>Total Packet Count - <?= $packet_count ?></h4>
    </div>
    <?php
} ?>
