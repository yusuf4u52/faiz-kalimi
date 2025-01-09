<?php

$id = getAppData('arg1') ?? '-1';
$name = '';
$details = '';
$starttime = '';
$endtime = '';
$survey_for = 'All';

if( is_post() ) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $details = $_POST['details'];
    $starttime = $_POST['starttime'];
    $endtime = $_POST['endtime'];
    $survey_for = $_POST['survey_for'];

    if( $id > 0 ) {
        $result = edit_miqaat($id, $name, $details, $starttime, $endtime, $survey_for);       
    } else {
        $result = add_miqaat($name, $details, $starttime, $endtime, $survey_for);
    }

    if( $result->count > 0 ) {
        do_redirect_with_message('/miqaats', 'Miqaat added successfully.');
    } else {
        setSessionData('transit_data', 'Failed to add miqaat.');        
    }
}

setAppData('FORM_DATA', ['id' => $id,'name' => $name, 'details' => $details, 'starttime' => $starttime, 'endtime' => $endtime, 'survey_for' => $survey_for]);

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
                <input type="text" required class="form-control-plaintext" 
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
                    <input type="datetime" required class="form-control" 
                    id="starttime" name="starttime" value="<?=$form_data['starttime']?>">
                </div>
            </div>
            <div class="mb-3 row">
                <label for="endtime" class="col-sm-3 col-form-label">End Time</label>
                <div class="col-sm-9">
                    <input type="datetime" required class="form-control" 
                    id="endtime" name="endtime" value="<?=$form_data['endtime']?>">
                </div>
            </div>
            <input type="hidden" required name="survey_for" value="All">
            <!-- <div class="mb-3 row">
                <label for="survey_for" class="col-sm-3 col-form-label">RSVP For</label>
                <div class="col-sm-9">
                <select class="form-select" name="survey_for" id="survey_for">
                    <option>All</option>
                    <option>Family</option>
                    <option>Hof</option>
                    <option>NonHof</option>
                    <option>OnlyAdult</option>
                    <option>OnlyKids</option>
                </select>
                </div>
            </div> -->                        
            <div class="form-group" style="font-weight:20px;margin-top: 25px;">
                <button type="submit" class="btn btn-success">Save</button>
            </div>
        </div>
    </form>
<?php } ?>