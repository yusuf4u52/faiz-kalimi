<?php
if (!is_user_a(SUPER_ADMIN, RECEPTION)) {
    do_redirect_with_message('/home', 'Redirected as tried to access unauthorized area.');
}

$itsid = getAppData('arg1');
$action = is_null($itsid) ? 'list' : ($itsid > 0 ? 'edit' : 'new');
setAppData('action', $action);

$user_data = (object) [];
if ($itsid > 0) {
    $result = get_slot_details($itsid);
    if (is_null($result)) {
        do_redirect_with_message('/vjb.slot', "Slot ID ($itsid) not found.");
    }
    $user_data = $result;
}

setAppData('user_data', $user_data);

do_for_post('_handle_add_user');

function content_display()
{
    $itsid = getAppData('arg1');
    $action = getAppData('action');
    switch ($action) {
        case 'list':
            show_user_list();
            break;
        case 'edit':
        case 'new':
            show_input_user();
            break;
    }
}

function show_input_user()
{
    $itsid = getAppData('arg1');
    $action = getAppData('action');

    $user_data = getAppData('user_data');
    ?>
    <div class="card">
        <div class="card-body">
            <form action="" method="post">
                <input type='hidden' name='req_itsid' value="<?= $itsid ?>" />
                <div class="mb-3 row">
                    <label for="itsid" class="col-4 form-label">ID</label>
                    <div class="col-8">
                        <input type='hidden' name='id' value="<?= $user_data->id ?? -1 ?>" />
                        <?= $user_data->id ?? -1 ?>
                    </div>
                </div>
                <?php 
                get_text_field('title', 'Title', $user_data->title ?? '', 'text',true);
                get_text_field('date', 'Date', $user_data->date ?? '', 'date',true); 
                get_text_field('capacity', 'Capacity', $user_data->capacity ?? '', 'text',true);
                ?>                                                
                <div class="mb-3">
                    <button type="submit" class="btn btn-light">Save</button>
                </div>
            </form>
        </div>
    </div>
    <?php
}

function show_user_list()
{
    $user_data = get_slot_records();
    $url = getAppData('BASE_URI');
    ?>
    <div class="card">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-6">
                    <h2 class="mb-3">Slot Data</h2>
                </div>
                <div class="col-6 text-end">
                    <a href="<?= $url ?>/vjb.slot/0" class="btn btn-light mb-3">Add New</a>
                </div>
            </div>
            <?= __display_user_list([$user_data]) ?>
        </div>
    </div>
    <?php
}

function __display_user_list($data)
{
    $records = $data[0];
    util_show_data_table($records, [
        '__show_row_sequence' => 'S/no',
        // 'id' => 'ID',
        'title' => 'Title',
        'capacity' => 'Capacity',
        'registered' => 'Registered',
        'date' => 'Date',
        '__show_link2markaz_list' => 'Action'
    ]);
}

function __show_link2markaz_list($row, $index)
{
    $id = $row->id;
    $uri = getAppData('BASE_URI');
    
    return "<a href='$uri/vjb.slot/$id' class='btn btn-light'>Edit</a>
    <a href='$uri/report/vjb_registration/$id' class='btn btn-light'>Report</a>
    ";
}


function _handle_add_user()
{
    $req_itsid = $_POST['req_itsid'];

    $id = $_POST['id'];
    $title = $_POST['title'];
    $date = $_POST['date'];
    $capacity = $_POST['capacity'];

    $msg = 'User added successfully!';
    $result = add_vajebaat_slot($id, $title, $date, $capacity);
    if (!is_null($result)) {
        $msg = 'Failed with error: ' . $result;
    }
    do_redirect_with_message('/vjb.slot', $msg);

}
