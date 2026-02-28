
<?php
if_not_post_redirect('/quran.its');
// $url = $home_uri;
do_for_post('_handle_form_submit');

function _handle_form_submit() { 
    $itsid = $_POST['its_id'];
    $click = $_POST['click'] ?? '';
    if( $click != 'search' ) {
        $quran_count = $_POST['quran_count'];
        $quran_count = $click === 'add' ? $quran_count + 1 : $quran_count;

        $tilawat_data = (object) [];
        $tilawat_data->quran_count = $quran_count;

        for ($index = 1; $index <= $quran_count; $index++) {
            $sipara_key = 'sipara_' . $index;
            $sipara_list = isset($_POST[$sipara_key]) ? $_POST[$sipara_key] : [];
            implode(',', $sipara_list);
            $tilawat_data->$sipara_key = $sipara_list;
        }

    
        $result = save_quran_recite_data($its_id, $quran_count, $tilawat_data);
        if( isset($result) ) {
            $message = 'Error: Tilawat data not recorded. ' . $result;
        } else {
            $message = 'Success: Your tilawat data is submitted.';
        }
        define('NO_TEMPLATE', true);
        $uri = getAppData('BASE_URI');
        echo '<body onload="setTimeout(\'document.forms[0].submit()\', 2000)">
  <form action="'.$uri.'/quran.tilawat" method="POST">
    <!-- your form inputs (can be hidden) -->
    <input type="hidden" name="its_id" value="'.$itsid.'">
    <input type="hidden" name="action" value="tilawat">
    <h2>'.$message.'</h2>
    <br/>
    <h4>You will be automatically re-directed in 5 seconds or less.</h4>
    <p>If you do not get re-directed in a few seconds, press the button below.</p>
    <button type="submit" name="click" value="Search">Continue</button>
  </form>
</body>';
exit();
        
        
    // } else if( $click === 'add' ) {
    //     $result = add_new_quran($_POST);
    //     if( isset($result) ) {
    //         do_redirect_with_message('/quran.its', 'Error: New Quran not added. ' . $result);
    //     } else {
    //         do_redirect_with_message('/quran.its', 'Success: New Quran added. Please update the tilawat data for the new Quran.');            
    //     }
    } else {
        $its_data = get_hof_data($itsid);
        if (is_null($its_data)) {
            do_redirect_with_message('/quran.its', "This ITS ID is not found");
        }

        $hijri_year = get_current_hijri_year();
        setAppData('hijri_year', $hijri_year);
        setAppData('its_id', $itsid);
        setAppData('its_data', $its_data);

        $result = get_quran_recite_data($itsid);
        if( is_null($result) ) {
            setAppData('quran_count', value: 3);
            setAppData('tilawat_data', (object) ['quran_count' => 3, 'sipara_1' => [], 'sipara_2' => [], 'sipara_3' => []]);
            setAppData('record_exist', value: false);
        
            } else {
            setAppData('quran_count', $result->quran_count);
            $tilawat_data = json_decode($result->tilawat_data);
            setAppData('tilawat_data', $tilawat_data);
            setAppData('record_exist', value: true);
        }
    }
    
}
function content_display() {
        $its_id = getAppData('its_id');
        $url = getAppData('BASE_URI');

    $quran_count = getAppData('quran_count');
    $tilawat_data = getAppData('tilawat_data');
    $record_exist = getAppData('record_exist');
?>
<div class="container-fluid px-2">
    <h2 class="h5 mb-3">Tilawat Record for <?= $its_id ?></h2>
</div>
<form method="post" action="<?= $url ?>/quran.tilawat">
    <div class="container-fluid px-2">
    <input type="hidden" name="action" value="tilawat" />
    <input type="hidden" name="its_id" value="<?= $its_id ?>">
    <input type="hidden" name="quran_count" value="<?= $quran_count ?>">

    <?php
    for($index=1; $index<=$quran_count; $index++){
        $sipara_key = 'sipara_' . $index;
        $sipara_list = is_array($tilawat_data->$sipara_key) ? $tilawat_data->$sipara_key : explode(',', $tilawat_data->$sipara_key);
    ?>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Quran <?= $index ?> - (Select Recited Sipara)</h5>
            <div class="form-check form-check-primary mb-0">
                <input class="form-check-input select-all" type="checkbox" id="select_all_<?= $index ?>">
                <label class="form-check-label" for="select_all_<?= $index ?>">Select All</label>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <?php
                $counter = 1;
                for ($x = 1; $x < 7; $x++) {
                    for ($y = 1; $y < 6; $y++) {
                        $selected = in_array($counter, $sipara_list) ? 'checked' : '';
                        echo "<div class='col-6 col-sm-4 col-md-2 mb-2'>
                                <div class='form-check form-check-flat form-check-primary'>
                                    <label class='form-check-label'>
                                        <input class='form-check-input' type='checkbox' value='$counter' name='{$sipara_key}[]' id='{$sipara_key}[]' $selected> $counter
                                    </label>
                                </div>
                              </div>";
                        $counter++;
                    }
                }
                ?>
            </div>
        </div>
    </div>
    <?php } ?>

    <div class="form-group" style="font-weight:20px;margin-top: 25px;">
        <button type="submit" name='click' value='save' id="save_button"
        class="btn btn-success w-100">Save</button>
    </div>
<?php if($record_exist && $quran_count <= 30) { ?>

    <div class="form-group" style="font-weight:20px;margin-top: 25px;">
        <button type="submit" name='click' value='add' id="add_button" class="btn btn-warning w-100">Add New Quran</button>
    </div>
    <?php } ?>
    </div> <!-- /container-fluid -->
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.select-all').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            var card = this.closest('.card');
            if (!card) return;
            var checked = this.checked;
            card.querySelectorAll('.card-body input[type="checkbox"]').forEach(function(ch) {
                ch.checked = checked;
            });
        });
    });
});
</script>
<?php
}