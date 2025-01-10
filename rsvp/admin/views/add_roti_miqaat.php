<?php

$id = getAppData('arg1') ?? '-1';
$name = '';
$details = '';
$starttime = '';
$endtime = '';
$roti_target = '0';

$edit_failed = false;
if( is_post() ) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $details = $_POST['details'];
    $starttime = $_POST['starttime'];
    $endtime = $_POST['endtime'];
    $roti_target = $_POST['roti_target'];

    if( $id > 0 ) {
        $result = edit_roti_miqaat($id, $name, $details, $starttime, $endtime, $roti_target);       
    } else {
        $result = add_roti_miqaat($name, $details, $starttime, $endtime, $roti_target);
    }

    if( $result->count > 0 ) {
        do_redirect_with_message('/roti_miqaats', 'Miqaat added successfully.');
    } else {
        $edit_failed = true;
        setSessionData('transit_data', 'Failed to save miqaat details.');        
    }
}

if( $id > 0 && !$edit_failed ) {
    $result = get_roti_miqaat_by_id($id);
    $miqaat = $result->data[0];
    $name = $miqaat['name'];
    $details = $miqaat['details'];
    $starttime = $miqaat['start_datetime'];
    $endtime = $miqaat['end_datetime'];
    $roti_target = $miqaat['roti_target'];
}

setAppData('FORM_DATA', ['id' => $id,'name' => $name, 'details' => $details, 'starttime' => $starttime, 'endtime' => $endtime, 'roti_target' => $roti_target]);

function content_display()
{
    $form_data = getAppData('FORM_DATA');
    $title = $form_data['id'] > 0 ? 'Edit Miqaat' : 'Add Miqaat';
    ?>    
    <div class="row">
        <div class="col"><h6><?=$title?></h6></div>
    </div>        
    <form method="post">
        <input type="hidden" name="id" value="<?=$form_data['id']?>">
        <div class='col-xs-12'>
            <div class="mb-3 row">
                <label for="name" class="col-sm-3 col-form-label">Name</label>
                <div class="col-sm-9">
                <input type="text" required class="form-control" 
                id="name" name="name" value="<?=$form_data['name']?>">                
                </div>
            </div>
            <div class="mb-3 row">
                <label for="details" class="col-sm-3 col-form-label">Details</label>
                <div class="col-sm-9">
                    <input type="text" class="form-control" id="details" name="details"  
                    value="<?=$form_data['details']?>">
                </div>
            </div>
            <div class="mb-3 row">
                <label for="starttime" class="col-sm-3 col-form-label">Start Time</label>
                <div class="col-sm-9">
                    <input type="datetime-local" required class="form-control" 
                    id="starttime" name="starttime" value="<?=$form_data['starttime']?>">
                </div>
            </div>
            <div class="mb-3 row">
                <label for="endtime" class="col-sm-3 col-form-label">End Time</label>
                <div class="col-sm-9">
                    <input type="datetime-local" required class="form-control" 
                    id="endtime" name="endtime" value="<?=$form_data['endtime']?>">
                </div>
            </div>
            <div class="mb-3 row">
                <label for="roti_target" class="col-sm-3 col-form-label">Roti target</label>
                <div class="col-sm-9">
                <input type="text" required class="form-control" 
                id="roti_target" name="roti_target" value="<?=$form_data['roti_target']?>">                
                </div>
            </div>                                   
            <div class="form-group" style="font-weight:20px;margin-top: 25px;">
                <button type="submit" class="btn btn-success">Save</button>
            </div>
        </div>
    </form>
<?php } ?>