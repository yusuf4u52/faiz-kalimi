<?php
if_not_post_redirect('/data_entry');

$miqaat_id = $_POST['miqaat_id'];
$sabeel = $_POST['sabeel'];

$miqaat_result = get_miqaat_byid($miqaat_id);
if (!is_record_found($miqaat_result)) {
    do_redirect_with_message('/error', 'Ops! No active miqaat for "Mohabbat Ni Roti". Visit us later.');
}
setAppData('miqaat_data', $miqaat_result->data[0]);

$thaali_result = get_thaali_by_sabeel($sabeel);
if (!is_record_found($thaali_result)) {
    do_redirect_with_message('/error', 'Ops! This sabeel number "(' . $sabeel . ')"is not found.');
} 
setAppData('thaali_data', $thaali_result->data[0]);

$rotimaker_result = get_rotimaker_by_sabeel($sabeel);
if (!is_record_found($rotimaker_result)) {
    setSessionData('sabeel', $sabeel);
    setSessionData('miqaat_id', $miqaat_id);

    do_redirect_with_message('/register', 'Please register as Roti Maker for "Mohabbat Ni Roti"');
}
setAppData('rotimaker_data', $rotimaker_result->data[0]);
$rotimaker_record = $rotimaker_result->data[0];
$roti_count = $rotimaker_record['roti_count'];

$rotidata_result = get_roti_data_for($sabeel, $miqaat_id);
if (is_record_found($rotidata_result)) {
    $rotidata_record = $rotidata_result->data[0];
    $roti_count = $rotidata_record['roti_count'];
}

setAppData('roti_count', $roti_count);

function content_display()
{
    $miqaat_data = getAppData('miqaat_data');
    $thaali_data = getAppData('thaali_data');
    $rotimaker_data = getAppData('rotimaker_data');
    $roti_count = getAppData('roti_count');
    ?>

    <h4>Summary</h4>
        <div class='col-xs-12'>            
        <table class="table">
                <tr>
                    <th>Roti Packets Count (1 Packet = 4 Roti)</th>
                    <td><?= $roti_count . ($roti_count == 1 ? " Packet" : " Packets") ?></td>
                </tr>
                <tr>
                    <th>Roti Maker Details</th>
                    <td><?= $rotimaker_data['itsid'] . ' / ' . $rotimaker_data['full_name'] ?></td>
                </tr>
                <tr>
                    <th>SubSector / Sector</th>
                    <td><?= $thaali_data['SubSectorITS'] . ' / ' . $thaali_data['Sector'] ?></td>
                </tr>
                <tr>
                    <th>Musaida Details</th>
                    <td><?= $thaali_data['MusaidName'] . " ({$thaali_data['MusaidaContact']})"  ?></td>
                </tr>
                <tr>
                    <th>Masoola Details</th>
                    <td><?= $thaali_data['MasoolName'] . " ({$thaali_data['MasoolaContact']})" ?></td>
                </tr>
                <tr>
                    <th>Transporter Details</th>
                    <td><?= $thaali_data['Transporter'] ?></td>
                </tr>                          
            </table>
        
        </div>
    <?php
} ?>