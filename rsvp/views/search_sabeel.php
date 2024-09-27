<?php
if_not_post_redirect('/data_entry');


$miqaat_id = $_POST['miqaat_id'];
$hof_id = $_POST['hof_id'];
$records = [];
$sabeel_search_result = get_family_details($hof_id,   $miqaat_id);
if (!is_record_found($sabeel_search_result)) {
    do_redirect_with_message('/data_entry', "No records found for HOF ID ($hof_id).");
} else {
    $records = $sabeel_search_result->data;
    setAppData('records', $records);
    $record = $records[0];
    setAppData('sabeel', $record['sabeel_no']);
    if ($sabeel_search_result->count == 1) {
        if (!isset($record['its_id'])) {
            $hof_results = add_family_hof($hof_id);
            if (is_record_found($hof_results)) {
                auto_post_redirect(
                    'search_sabeel',
                    ['hof_id' => $hof_id, 'miqaat_id' => $miqaat_id]
                );
            } else {
                do_redirect_with_message('/data_entry', 'Oops! Failed to add HOF record, contact the administrator.');
            }
        }
    }
}

function content_display()
{
    $miqaat_id = $_POST['miqaat_id'];
    $hof_id = $_POST['hof_id'];
    $records = getAppData('records');
    $sabeel = getAppData('sabeel');

    $hdr = ['Select', 'Name'];
    $cols = ['full_name'];
    ?>
    <h5>Family Records</h5>
    <div class='col-xs-12'>
        <form action="member_entry" method="post">
            <input type="hidden" name="sabeel" id="sabeel" value="<?= $sabeel ?>">
            <input type="hidden" name="miqaat_id" id="miqaat_id" value="<?= $miqaat_id ?>">
            <input type="hidden" name="hof_id" id="hof_id" value="<?= $hof_id ?>">
            <input type="hidden" name="action" id="action" value="show">
            <div class="form-group">
                <button type="submit" class="btn btn-warning">Add mehman</button>
            </div>
        </form>
        <br />
        <form action="mark_attendance" method="post">
        <input type="hidden" name="sabeel" id="sabeel" value="<?= $sabeel ?>">
        <input type="hidden" name="miqaat_id" id="miqaat_id" value="<?= $miqaat_id ?>">
            <input type="hidden" name="hof_id" id="hof_id" value="<?= $hof_id ?>">

            <div class="table-responsive">
                <table class="table">
                    <?php
                    echo '<tr><th>' . implode('</th><th>', $hdr) . '</th></tr>';
                    foreach ($records as $row) {
                        $selected = $row['its_id'] === $row['attendee'] ? ' checked' : ''; 
                        echo "<tr><td><input type='checkbox' $selected value='{$row['its_id']}'
                name='family_its_list[]' id='family_its_list[]'></td>";
                        foreach ($cols as $col) {
                            echo "<td>{$row["$col"]}</td>";
                        }                        
                        echo '</tr>';
                    }
                    ?>
                </table>
            </div>
            <div class="form-group" style="text-align: right; vertical-align: middle; font-weight:20px;margin-top: 25px;">
                <button type="submit" class="btn btn-success">Save</button>
            </div>
        </form>
    </div>
    <?php
} ?>