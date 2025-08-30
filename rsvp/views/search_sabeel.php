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
    <div class="row align-items-center">
        <div class="col-7">
            <h4 class="mb-3">Family Records</h4>
        </div>
        <div class="col-5 text-end">
            <form action="member_entry" method="post">
                <input type="hidden" name="sabeel" id="sabeel" value="<?= $sabeel ?>">
                <input type="hidden" name="miqaat_id" id="miqaat_id" value="<?= $miqaat_id ?>">
                <input type="hidden" name="hof_id" id="hof_id" value="<?= $hof_id ?>">
                <input type="hidden" name="action" id="action" value="show">
                <button type="submit" class="btn btn-light mb-3">Add Mehman</button>
            </form>
        </div>
    </div>
    <form action="mark_attendance" method="post">
        <input type="hidden" name="sabeel" id="sabeel" value="<?= $sabeel ?>">
        <input type="hidden" name="miqaat_id" id="miqaat_id" value="<?= $miqaat_id ?>">
        <input type="hidden" name="hof_id" id="hof_id" value="<?= $hof_id ?>">
        <div class="table-responsive mt-4">
            <table class="table table-striped display" width="100%">
                <?php echo '<thead><tr><th>' . implode('</th><th>', $hdr) . '</th></tr></thead>';
                echo '<tbody>';
                    foreach ($records as $row) {
                        echo "<tr><td><div class='form-check mb-3'>";
                            $selected = $row['its_id'] === $row['attendee'] ? ' checked' : ''; 
                            echo "<input class='form-check-input mt-2' type='checkbox' $selected value='{$row['its_id']}' name='family_its_list[]' id='family_its_list[]'></td>";
                            foreach ($cols as $col) {
                                echo "<td><label class='form-check-label'>{$row["$col"]}</label></td>";
                            }
                            if( $row['mohallah'] === 'Other' && $row['hof_id'] != $row['its_id']) { ?>
                                    <td><form method="post" action="delete_member">
                                        <input type="hidden" value="<?= $miqaat_id ?>" name="miqaat_id" id="miqaat_id">
                                        <input type="hidden" value="<?= $hof_id ?>" name="hof_id" id="hof_id">
                                        <input type="hidden" value="<?= $row['its_id'] ?>" name="its_id" id="its_id">
                                        <input type="hidden" value="show" name="action" id="action">
                                        <button type="submit" class="btn btn-light"><i class="bi bi-trash"></i></button>
                                    </form></td>
                            <?php }    
                        echo "</div>";
                    } 
                echo '</tbody>'; ?>
            </table>
        </div>
        <div class="mb-3">
            <button type="submit" class="btn btn-light">Save</button>
        </div>
    </form> 
    <?php
} ?>