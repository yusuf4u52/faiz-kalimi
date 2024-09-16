<?php
function content_display()
{
    $itsid = '';
    if (is_post()) {
        $itsid = $_POST['itsid'];
        if( $itsid === '99999999' ) {
            $itsid = null;
        }
    }

    $miqaat_result = get_current_or_last_miqaat();
    $miqaat = 0;
    if (is_record_found($miqaat_result)) {
        $miqaat = $miqaat_result->data[0];
    }
    $miqaat_id = $miqaat['id'] ?? 0;
    $miqaat_name = $miqaat['name'] ?? 'Error';

    $result = get_roti_report($miqaat_id, $itsid);
    $data = $result->data;
    $hdr = ['Sabeel Num', 'Packet Count', 'Name', 'Mobile', 'Sector', 'Sub Sector'];
    $cols = ['sabeel','roti_count', 'full_name', 'mobile', 'sector_its', 'subsector'];
    ?>
    <h5>Roti Report for <?= $miqaat_name ?></h5>
    <div class='col-xs-12'>
        <form action="" method="post">
            <div class="input-group">
                <input required type="text" class="form-control" name="itsid" id="itsid" placeholder="ITS ID" pattern="^[0-9]{8}$" value="<?=$itsid??''?>">                
                <div class="input-group-append">
                    <button class="btn btn-success" type="submit">Search</button>
                </div>
            </div>
        </form>
        <br/>
        <div class="table-responsive">
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
        </div>
        <h4>Total Packet Count - <?= $packet_count ?></h4>
    </div>
    <?php
} ?>