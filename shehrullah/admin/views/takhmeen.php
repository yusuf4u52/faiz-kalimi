<?php
// Allow GET requests with hof_id parameter, otherwise redirect if not POST
if (!is_post() && !isset($_GET['hof_id'])) {
    do_redirect('/home');
}

if (!is_user_a(SUPER_ADMIN, TAKHMEENER)) {
    do_redirect_with_message('/home', 'Redirected as tried to access unauthorized area.');
}

// Handle GET request - load data for display
if (is_get() && isset($_GET['hof_id'])) {
    $hof_id = $_GET['hof_id'];
    $hijri_year = get_current_hijri_year();
    $hub_data = get_shehrullah_data_for($hijri_year);

    $hof_data = get_hof_data($hof_id);
    setAppData('hof_data', $hof_data);

    $takhmeen_data = get_shehrullah_takhmeen_for($hof_id, $hijri_year);
    if (is_null($takhmeen_data)) {
        do_redirect_with_message('/home', 'Oops! data not found.');
    }

    $receipt_data = get_receipt_data_for($hijri_year, $hof_id);
    setAppData('receipt_data', $receipt_data);
    setAppData('takhmeen_data', $takhmeen_data);
    setAppData('hub_data', $hub_data);
    setAppData('hof_id', $hof_id);
}

do_for_post('__handle_post');

function __handle_post()
{
    $action = $_POST['action'];
    $hof_id = $_POST['hof_id'];
    $hijri_year = get_current_hijri_year();
    $hub_data = get_shehrullah_data_for($hijri_year);

    $hof_data = get_hof_data($hof_id);
    setAppData('hof_data', $hof_data);

    $takhmeen_data = get_shehrullah_takhmeen_for($hof_id, $hijri_year);
    if (is_null($takhmeen_data)) {
        do_redirect_with_message('/home', 'Oops! data not found.');
    }

    $receipt_data = get_receipt_data_for($hijri_year, $hof_id);

    if ($action === 'register') {
        $niyaz_hub = $_POST['niyaz_hub'];
        $iftar_count = $_POST['iftar_count'];
        $zabihat_count = $_POST['zabihat_count'];
        $fateha_count = $_POST['fateha_count'];
        $khajoor_count = $_POST['khajoor_count'];
        $pirsa_count = $_POST['pirsa_count'];
        $chair_count = $_POST['chair_count'];
        $takhmeen = 0;

        $takhmeen = $niyaz_hub + ($iftar_count * $hub_data->iftar)
            + ($zabihat_count * $hub_data->zabihat)
            + ($fateha_count * $hub_data->fateha)
            + ($khajoor_count * $hub_data->khajoor)
            + ($pirsa_count * $hub_data->pirsu)
            + ($chair_count * $hub_data->chair);

        $update_takhmeen = true;
        //If any receipt is created
        if (is_array($receipt_data) && count($receipt_data) > 0) {
            if ($takhmeen_data->takhmeen > $takhmeen) {
                $update_takhmeen = false;
                setSessionData(TRANSIT_DATA , 'Oops! No you can not decrease the takhmeen amount. One or more recipt is created.');
            }
        }

        if ($update_takhmeen) {
            add_shehrullah_takh_hub(
                $hijri_year,
                $hof_id,
                $niyaz_hub,
                $iftar_count,
                $zabihat_count,
                $fateha_count,
                $khajoor_count,
                $pirsa_count,
                $chair_count,
                $takhmeen
            );
        }

        //To get the latest data again
        $takhmeen_data = get_shehrullah_takhmeen_for($hof_id, $hijri_year);
        // if (is_null($takhmeen_data)) {
        //     do_redirect_with_message('/home', 'Oops! data not found.');
        // }
        $receipt_data = get_receipt_data_for($hijri_year, $hof_id);    
    }

    // Post-Redirect-Get: Redirect to GET request to prevent resubmission
    // Redirect to same page with GET parameters
    $redirect_url = '/takhmeen?hof_id=' . urlencode($hof_id) . '&saved=1';
    do_redirect($redirect_url);
    exit();
}

function content_display()
{
    $takhmeen_data = getAppData('takhmeen_data');
    // Get hof_id from GET or POST (GET takes precedence after redirect)
    $hof_id = isset($_GET['hof_id']) ? $_GET['hof_id'] : (isset($_POST['hof_id']) ? $_POST['hof_id'] : getAppData('hof_id'));
    $hof_data = getAppData('hof_data');
    $hub_data = getAppData('hub_data');
    $receipt_data = getAppData('receipt_data');
    ?>
    <form method="post">
        <input type="hidden" name="hof_id" value="<?= $hof_id ?>">
        <input type="hidden" name="action" value="register">
        <div class='col-xs-12'>
            <div class="mb-3 row">
                <label for="hof_id" class="col-sm-3 col-form-label">HOF Name</label>
                <div class="col-sm-9">
                    <input type="text" readonly class="form-control" value="<?= $hof_data->full_name ?>">
                </div>
            </div>
            <div class="mb-3 row">
                <label for="hof_id" class="col-sm-3 col-form-label">Niyaz Hub</label>
                <div class="col-sm-9">
                    <input type="text" pattern="^[0-9]{1,9}$" required class="form-control" id="niyaz_hub" name="niyaz_hub"
                        value="<?= $takhmeen_data->niyaz_hub ?? 0 ?>">
                </div>
            </div>
            <div class="mb-3 row">
                <label for="hof_id" class="col-sm-3 col-form-label">Iftar count (<?= $hub_data->iftar ?>)</label>
                <div class="col-sm-9">
                    <select class="form-control form-control-lg" name="iftar_count" id="iftar_count">
                        <?= getCountDropdown(0, 5, $takhmeen_data->iftar_count ?? 0) ?>
                    </select>
                </div>
            </div>
            <div class="mb-3 row">
                <label for="hof_id" class="col-sm-3 col-form-label">Zabihat count (<?= $hub_data->zabihat ?>)</label>
                <div class="col-sm-9">
                    <select class="form-control form-control-lg" name="zabihat_count" id="zabihat_count">
                        <?= getCountDropdown(0, 5, $takhmeen_data->zabihat_count ?? 0) ?>
                    </select>
                </div>
            </div>
            <div class="mb-3 row">
                <label for="hof_id" class="col-sm-3 col-form-label">Fateha count (<?= $hub_data->fateha ?>)</label>
                <div class="col-sm-9">
                    <select class="form-control form-control-lg" name="fateha_count" id="fateha_count">
                        <?= getCountDropdown(0, 5, $takhmeen_data->fateha_count ?? 0) ?>
                    </select>
                </div>
            </div>
            <div class="mb-3 row">
                <label for="hof_id" class="col-sm-3 col-form-label">Khajoor count (<?= $hub_data->khajoor ?>)</label>
                <div class="col-sm-9">
                    <select class="form-control form-control-lg" name="khajoor_count" id="khajoor_count">
                        <?= getCountDropdown(0, 5, $takhmeen_data->khajoor_count ?? 0) ?>
                    </select>
                </div>
            </div>
            <div class="mb-3 row">
                <label for="hof_id" class="col-sm-3 col-form-label">Pirsa count (<?= $hub_data->pirsu ?>)</label>
                <div class="col-sm-9">
                    <select class="form-control form-control-lg" name="pirsa_count" id="pirsa_count">
                        <?= getCountDropdown(0, 5, $takhmeen_data->pirsa_count ?? 0) ?>
                    </select>
                </div>
            </div>
            <div class="mb-3 row">
                <label for="hof_id" class="col-sm-3 col-form-label">Chair count (<?= $hub_data->chair ?>)</label>
                <div class="col-sm-9">
                    <select class="form-control form-control-lg" name="chair_count" id="chair_count">
                        <?= getCountDropdown(0, 5, $takhmeen_data->chair_count ?? 0) ?>
                    </select>
                </div>
            </div>
            <div class="mb-3 row">
                <label for="hof_id" class="col-sm-3 col-form-label">Final Takhmeen</label>
                <div class="col-sm-9">
                    <input type="text" readonly pattern="^[0-9]{1,9}$" required class="form-control" id="takhmeen"
                        name="takhmeen" value="<?= $takhmeen_data->takhmeen ?? 0 ?>">
                </div>
            </div>
            <div class="form-group" style="font-weight:20px;margin-top: 25px;">
                <button type="submit" class="btn btn-success">Save</button>

                <?php if ($takhmeen_data->takhmeen > 0) { ?>
                    <a id='paynow_link' href="get2post?url=/collection&hof_id=<?= $hof_id ?>" class="btn btn-primary">Pay</a>
                <?php } ?>
            </div>
            <?php if (is_array($receipt_data) && count($receipt_data) > 0) { ?>
                <p>Receipt History</p>
                <div class="form-group row">
                    <?php __display_table_records([$receipt_data]) ?>
                </div>
            <?php } ?>
        </div>
    </form>
    <script>
        function on_change_dropdown() {
            var niyaz_hub = Number($('#niyaz_hub').val());
            var iftar_hub = Number($('#iftar_count').val()) * <?= $hub_data->iftar ?>;
            var zabihat_hub = Number($('#zabihat_count').val()) * <?= $hub_data->zabihat ?>;
            var fateha_hub = Number($('#fateha_count').val()) * <?= $hub_data->fateha ?>;
            var khajoor_hub = Number($('#khajoor_count').val()) * <?= $hub_data->khajoor ?>;
            var pirsa_hub = Number($('#pirsa_count').val()) * <?= $hub_data->pirsu ?>;
            var chair_hub = Number($('#chair_count').val()) * <?= $hub_data->chair ?>;

            var takhmeen = niyaz_hub + iftar_hub + zabihat_hub + fateha_hub + khajoor_hub + pirsa_hub + chair_hub;

            $('#takhmeen').val(takhmeen);
        }

        function the_script() {
            $(".form-control").on("change paste keyup", function () {
                on_change_dropdown();
                $('#paynow_link').hide();
            });
            
            // Scroll to Pay button after form submission (check for saved GET parameter)
            <?php if (isset($_GET['saved']) && $_GET['saved'] == '1') { ?>
                setTimeout(function() {
                    var payButton = document.getElementById('paynow_link');
                    if (payButton) {
                        payButton.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    } else {
                        // If Pay button doesn't exist, scroll to bottom of form
                        var form = document.querySelector('form');
                        if (form) {
                            form.scrollIntoView({ behavior: 'smooth', block: 'end' });
                        }
                    }
                }, 100);
            <?php } ?>
        }
    </script>
    <?php
}

function __display_table_records($data)
{
    //case when sa.masalla is null then '' else sa.masalla end as masalla, 
// case when sa.attendance_type is null then 'Yes' else sa.attendance_type end as attendance_type,
// case when sa.chair_preference is null then 'No' else sa.chair_preference end as chair_preference,
// m.its_id,m.full_name,m.age,m.gender
    $records = $data[0];
    util_show_data_table($records, [
        '__show_row_sequence' => 'SN#',
        'id' => 'Receipt ID',
        'payment_mode' => 'Mode',
        'amount' => 'amount'
    ]);
}