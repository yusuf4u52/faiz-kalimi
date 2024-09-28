<?php
if_not_post_redirect('/data_entry');

$miqaat_id = $_POST['miqaat_id'];
$its_id = $_POST['its_id'];
$hof_id = $_POST['hof_id'];
$action = $_POST['action'];

if ($action === 'delete') {
    delete_member($its_id, $miqaat_id);

    setSessionData(TRANSIT_DATA, "Deleted successfully");
    auto_post_redirect('search_sabeel', [
        'hof_id' => $hof_id,
        'miqaat_id' => $miqaat_id
    ]);
} elseif ($action === 'cancel') {
    auto_post_redirect('search_sabeel', [
        'hof_id' => $hof_id,
        'miqaat_id' => $miqaat_id
    ]);
}

function content_display()
{
    $miqaat_id = $_POST['miqaat_id'];
    $its_id = $_POST['its_id'];
    $hof_id = $_POST['hof_id'];
    ?>
    <div class='col-xs-12'>
        <h5>Are you sure to delete mehman with ITS ID [<?= $its_id ?>]</h5>
        <div class="row">
            <div class="col-6">
                <form method="post" action="delete_member">
                    <input type="hidden" value="<?= $miqaat_id ?>" name="miqaat_id" id="miqaat_id">
                    <input type="hidden" value="<?= $hof_id ?>" name="hof_id" id="hof_id">
                    <input type="hidden" value="<?= $its_id ?>" name="its_id" id="its_id">
                    <input type="hidden" value="delete" name="action" id="action">

                    <button type="submit" class="btn btn-warning">Delete</button>
                </form>

            </div>
            <div class="col-6">
                <form method="post" action="delete_member">
                    <input type="hidden" value="<?= $miqaat_id ?>" name="miqaat_id" id="miqaat_id">
                    <input type="hidden" value="<?= $hof_id ?>" name="hof_id" id="hof_id">
                    <input type="hidden" value="<?= $its_id ?>" name="its_id" id="its_id">
                    <input type="hidden" value="cancel" name="action" id="action">

                    <button type="submit" class="btn btn-warning">Cancel</button>
                </form>
            </div>
        </div>
    </div>
    <?php
}
