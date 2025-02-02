<?php
if( !is_user_a(ROLE->SA) ) {
    do_redirect_with_message('/home' , 'Redirected as tried to access unauthorized area.');
}

$year = getAppData('arg1');
$action = is_null($year) ? 'list' : ($year > 0 ? 'edit' : 'new');
setAppData('action', $action);

$hijri_data = (object) [];
if ($year > 0) {
    $result = get_hijri_record_for($year);
    if (is_null($result)) {
        do_redirect_with_message('/users', "Year ($year) not found.");
    }
    $hijri_data = $result;
}

setAppData('hijri_data', $hijri_data);

do_for_post('_handle_add_year_data');

function content_display()
{
    $action = getAppData('action');
    switch ($action) {
        case 'list':
            show_year_list();
            break;
        case 'edit':
        case 'new':
            show_input_year();
            break;
    }
}

function show_input_year()
{
    $year = getAppData('arg1');

    $hijri_data = getAppData('hijri_data');
    ?>
    <div class="card">
        <div class="card-body">
            <form action="" method="post">
                <input type='hidden' name='req_year' value="<?= $year ?>" />
                <div class="form-group row">
                    <label for="year" class="col-sm-2 col-form-label">HIJRI YEAR</label>
                    <div class="col-sm-10">
                        <?php if ($year > 0) { ?>
                            <input type='hidden' name='year' value="<?= $hijri_data->year ?>" />
                            <?= $hijri_data->year ?>
                        <?php } else { ?>
                            <input type='text' pattern="^[0-9]{4}$" class='form-control' name='year'
                                value="<?= $hijri_data->year ?? '' ?>" required />
                        <?php } ?>
                    </div>
                </div>
                <div class="form-group row">
                    <label for="start_eng_date" class="col-sm-2 col-form-label">Hijri English Start Date</label>
                    <div class="col-sm-10">
                        <input type='date' class='form-control' name='start_eng_date'  value="<?=$hijri_data->start_eng_date?>" required />
                    </div>
                </div>
                <div class="form-group row">
                    <label for="full_niyaz" class="col-sm-2 col-form-label">Full Niyaz Hub</label>
                    <div class="col-sm-10">
                        <input type='text' pattern="^[0-9]{1,8}$" class='form-control' name='full_niyaz' value="<?=$hijri_data->full_niyaz?>" required />
                    </div>
                </div>
                <div class="form-group row">
                    <label for="full_niyaz" class="col-sm-2 col-form-label">Half Niyaz Hub</label>
                    <div class="col-sm-10">
                        <input type='text' pattern="^[0-9]{1,8}$" class='form-control' name='half_niyaz' value="<?=$hijri_data->half_niyaz?>" required />
                    </div>
                </div>
                <div class="form-group row">
                    <label for="full_niyaz" class="col-sm-2 col-form-label">Per Head Hub</label>
                    <div class="col-sm-10">
                        <input type='text' pattern="^[0-9]{1,8}$" class='form-control' name='per_person_niyaz' value="<?=$hijri_data->family_niyaz?>" required />
                    </div>
                </div>
                <div class="form-group row">
                    <label for="per_kid_niyaz" class="col-sm-2 col-form-label">Kids Hub</label>
                    <div class="col-sm-10">
                        <input type='text' pattern="^[0-9]{1,8}$" class='form-control' name='per_kid_niyaz' value="<?=$hijri_data->per_kid_niyaz?>" required />
                    </div>
                </div>
                <div class="form-group row">
                    <label for="half_hub_age" class="col-sm-2 col-form-label">Kids Hub Max Age</label>
                    <div class="col-sm-10">
                        <input type='text' pattern="^[0-9]{1,2}$" class='form-control' name='half_hub_age' value="<?=$hijri_data->half_hub_age?>" required />
                    </div>
                </div>
                <div class="form-group row">
                    <label for="zero_hub_age" class="col-sm-2 col-form-label">Zero Hub Age</label>
                    <div class="col-sm-10">
                        <input type='text' pattern="^[0-9]{1,2}$" class='form-control' name='zero_hub_age' value="<?=$hijri_data->zero_hub_age?>" required />
                    </div>
                </div>
                <div class="form-group row">
                    <label for="full_niyaz" class="col-sm-2 col-form-label">Sehori Hub</label>
                    <div class="col-sm-10">
                        <input type='text' pattern="^[0-9]{1,8}$" class='form-control' name='sehori' value="<?=$hijri_data->sehori?>" required />
                    </div>
                </div>
                <div class="form-group row">
                    <label for="full_niyaz" class="col-sm-2 col-form-label">Iftari Hub</label>
                    <div class="col-sm-10">
                        <input type='text' pattern="^[0-9]{1,8}$" class='form-control' name='iftar' value="<?=$hijri_data->iftar?>" required />
                    </div>
                </div>
                <div class="form-group row">
                    <label for="full_niyaz" class="col-sm-2 col-form-label">Zabihat Hub</label>
                    <div class="col-sm-10">
                        <input type='text' pattern="^[0-9]{1,8}$" class='form-control' name='zabihat' value="<?=$hijri_data->zabihat?>" required />
                    </div>
                </div>
                <div class="form-group row">
                    <label for="full_niyaz" class="col-sm-2 col-form-label">Fateha Hub</label>
                    <div class="col-sm-10">
                        <input type='text' pattern="^[0-9]{1,8}$" class='form-control' name='fateha' value="<?=$hijri_data->fateha?>" required />
                    </div>
                </div>
                <div class="form-group row">
                    <label for="full_niyaz" class="col-sm-2 col-form-label">Khajoor Hub</label>
                    <div class="col-sm-10">
                        <input type='text' pattern="^[0-9]{1,8}$" class='form-control' name='khajoor' value="<?=$hijri_data->khajoor?>" required />
                    </div>
                </div>
                <div class="form-group row">
                    <label for="full_niyaz" class="col-sm-2 col-form-label">Chair Hub</label>
                    <div class="col-sm-10">
                        <input type='text' pattern="^[0-9]{1,8}$" class='form-control' name='chair' value="<?=$hijri_data->chair?>" required />
                    </div>
                </div>
                <div class="form-group row">
                    <label for="full_niyaz" class="col-sm-2 col-form-label">Parking Hub</label>
                    <div class="col-sm-10">
                        <input type='text' pattern="^[0-9]{1,8}$" class='form-control' name='parking' value="<?=$hijri_data->parking?>" required />
                    </div>
                </div>
                <div class="form-group row">
                    <label for="full_niyaz" class="col-sm-2 col-form-label">Pirsu Hub</label>
                    <div class="col-sm-10">
                        <input type='text' pattern="^[0-9]{1,8}$" class='form-control' name='pirsa' value="<?=$hijri_data->pirsu?>" required />
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-sm-10">
                        <button type="submit" class="btn btn-gradient-success btn-rounded btn-fw">Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php
}
function show_year_list()
{
    $hijri_data = get_hijri_records();
    $url = getAppData('BASE_URI');
    ?>
    <div class="card">
        <div class="card-header row">
            <div class="col-6">Hijri Data</div>
            <div class="col-6"><a href="<?= $url ?>/years/0" class="btn btn-gradient-success btn-rounded btn-fw">Add New</a></div>
            </di>
            <div class="card-body">
                <?= __display_hijri_list([$hijri_data]) ?>
            </div>
        </div>
    </div>
    <?php
}

function __display_hijri_list($data)
{
    $records = $data[0];
    util_show_data_table($records, [
        '__show_row_sequence' => 'S/no',
        'year' => 'Hijri Year',
        'start_eng_date' => 'Start Date (English)',
        '__show_link2markaz_list' => 'Action'
    ]);
    // util_show_data_table($records, [
    //     '__show_row_sequence' => 'S/no',
    //     'itsid' => 'ITS ID',
    //     'name' => 'Name',
    //     'roles' => 'Roles',
    //     '__show_link2markaz_list' => 'Action'
    // ]);
}

function __show_link2markaz_list($row, $index)
{
    $year = $row->year;
    $uri = getAppData('BASE_URI');
    return "<a href='$uri/years/$year' class='btn btn-gradient-success btn-rounded btn-fw'>Edit</a>";
}


function _handle_add_year_data()
{
    $year = $_POST['year'];
    $start_eng_date = $_POST['start_eng_date'];
    $full_niyaz = $_POST['full_niyaz'];
    $half_niyaz = $_POST['half_niyaz'];
    $per_person_niyaz = $_POST['per_person_niyaz'];
    $per_kid_niyaz = $_POST['per_kid_niyaz'];
    $zero_hub_age = $_POST['zero_hub_age'];
    $half_hub_age = $_POST['half_hub_age'];
    $sehori = $_POST['sehori'];
    $iftar = $_POST['iftar'];
    $zabihat = $_POST['zabihat'];
    $fateha = $_POST['fateha'];
    $khajoor = $_POST['khajoor'];
    $chair = $_POST['chair'];
    $parking = $_POST['parking'];
    $pirsa = $_POST['pirsa'];

    $msg = 'Year data added successfully!';
    $result = add_markaz_hub_data(
        $year,
        $start_eng_date,
        $full_niyaz,
        $half_niyaz,
        $per_person_niyaz,
        $per_kid_niyaz,
        $zero_hub_age,
        $half_hub_age,
        $sehori,
        $iftar,
        $zabihat,
        $fateha,
        $khajoor,
        $chair,
        $parking,
        $pirsa
    );
    if (!is_null($result)) {
        $msg = 'Failed with error: ' . $result;
    }
    do_redirect_with_message('/years', $msg);
}