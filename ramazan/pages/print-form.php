<?php

$en_sabeel = getAppData('arg1');
$sabeel = do_decrypt($en_sabeel);

$sabeel_data = get_thaalilist_data($sabeel);
if (is_null($sabeel_data)) {
    do_redirect('\input-sabeel');
}
setAppData('sabeel_data', $sabeel_data);

$hof_id = $sabeel_data->ITS_No;
$hijri_year = get_current_hijri_year();


$takhmeen_data = get_shehrullah_takhmeen_for($hof_id, $hijri_year);
if (is_null($takhmeen_data)) {
    do_redirect('/');
}
setAppData('takhmeen_data', $takhmeen_data);

function content_display()
{
    $hijri_year = get_current_hijri_year();
    $takhmeen_data = getAppData('takhmeen_data');
    $thalidata = getAppData('sabeel_data');

    $get_only_attends = true;
    $attendees_records = get_attendees_data_for($thalidata->ITS_No, $hijri_year, $get_only_attends);
    $shehrullah_data = get_shehrullah_data_for($hijri_year);

    $date = date("d/m/Y");

    $uri = getAppData('BASE_URI');
    ?>
    <style>
        .smalltext {
            font-size: 11px;
        }
    </style>
    <div class="card" id="printableArea">
        <div class="card-body">
            <table class='table table-bordered'>
                <tr>
                    <td><img src="<?= $uri ?>/assets/images/anjuman_e_kalimi.png" /></td>
                    <td>
                        <table class='table table-bordered'>
                            <tr>
                                <th style='font-size: 12px'>SHEHRULLAH <?= $hijri_year ?>H / Kalimi Masjid, KALIMI MOHALLAH
                                </th>
                                <td><?=$date?></td>
                            </tr>                            
                        </table>
                    </td>

                </tr>
            </table>

            <table class='table table-bordered'>
                <tr>
                    <th style='font-size: 12px'>HOF</th>
                    <td style='font-size: 12px' colspan="4">[<?= $thalidata->ITS_No ?>] <?= $thalidata->NAME ?></td>
                </tr>
                <tr>
                    <th style='font-size: 12px'>Sabil</th>
                    <td style='font-size: 12px'>KL-<?= $thalidata->Thali ?></td>
                    <th style='font-size: 12px'>WApp</th>
                    <td style='font-size: 12px'><?= $takhmeen_data->whatsapp ?></td>
                    <th style='font-size: 12px'>Email</th>
                    <td style='font-size: 12px' colspan="2"><?= $thalidata->Email_ID ?></td>
                </tr>
                <tr>
                    <th style='font-size: 12px'>Addr:</th>
                    <td style='font-size: 12px' colspan="5"><?= $thalidata->Full_Address ?></td>
                </tr>
            </table>

            <table class='table table-bordered'>
                <tr>
                    <th style='font-size: 12px'>SN</th>
                    <th style='font-size: 12px'>ITS - NAME</th>
                    <th style='font-size: 12px'>Gender/Age</th>
                    <th style='font-size: 12px'>Chair</th>
                </tr>
                <?php
                foreach ($attendees_records as $attendees) {
                    $its = $attendees->its_id;
                    $name = $attendees->full_name;
                    $index = ((int) $index) + 1;
                    // return "$index. [$its] $name";
            
                    $age = $attendees->age;
                    $gender = $attendees->gender;
                    $gender = substr($gender, 0, 1);

                    $chair_preference = $attendees->chair_preference;
                    // return "$gender/$age";
            

                    echo "<tr>
                        <td style='font-size: 12px'>$index</td>
                        <td style='font-size: 12px'>$its - $name</td>
                        <td style='font-size: 12px'>$gender/$age</td>
                        <td style='font-size: 12px'>$chair_preference</td>
                        </tr>";
                }
                ?>
            </table>
            
            <?php __display_niyaz_section($takhmeen_data, $shehrullah_data); ?>

            <table class='table table-bordered small-text'>
                <tr>
                    <th style='font-size: 12px' colspan=3>Kindly submit form to receive izan card & carry izan card for our
                        convenience.</th>
                </tr>
                <tr>
                    <th style='font-size: 12px'>Committed Hub Amount</th>
                    <th style='font-size: 12px'>Receipt No.</th>
                    <th style='font-size: 12px'>Date</th>
                </tr>
            </table>
            <table class='table table-bordered'>
                <tr>
                    <th style='font-size: 12px'>HOF Signature</th>
                    <th style='font-size: 12px'>Auth. Signature</th>
                </tr>
            </table>
        </div>
    </div>
    <div class="card">
        <div class='card-footer row' id='print_button_section'>
            <div class='col-12'>
                <button class='btn btn-primary' id='Print'>Print</button>
            </div>            
        </div>
    </div>
    <script>
        function the_script() {
            $('#Print').click(function () {
                var printContents = document.getElementById('printableArea').innerHTML;
                var originalContents = document.body.innerHTML;

                document.body.innerHTML = printContents;
                window.print();
                document.body.innerHTML = originalContents;


                // $('#print_button_section').hide();
                // window.print();
                // $('#print_button_section').show();
            });
        }
    </script>
    <?php
}

function __display_niyaz_section(...$data)
{

    $takhmeen_data = (object) $data[0];
    $markaz_data = $data[1];

    $family_hub = $takhmeen_data->family_hub;
    $niyaz_type = $takhmeen_data->niyaz_type;
    $niyaz_count = $takhmeen_data->niyaz_count;
    $niyaz_hub = get_niyaz_amount_for($niyaz_type, $family_hub, $markaz_data);
    $total_niyaz = $niyaz_hub * $niyaz_count;

    $full_niyaz_count = $niyaz_type == 'full' ? $niyaz_count : 0;
    $half_niyaz_count = $niyaz_type == 'half' ? $niyaz_count : 0;
    $family_niyaz_count = $niyaz_type == 'family' ? $niyaz_count : 0;

    $full_niyaz_total = $markaz_data->full_niyaz * $full_niyaz_count;
    $half_niyaz_total = $markaz_data->half_niyaz * $half_niyaz_count;
    $family_niyaz_total = $family_hub * $family_niyaz_count;


    $sehori_count = $takhmeen_data->sehori_count;
    $sehori_hub = $markaz_data->sehori;//SHEHRULLAH_CONFIG->SEHORI;
    $sehori_total = $sehori_count * $sehori_hub;

    $net_total = $sehori_total + $total_niyaz;

    $zabihat_count = $takhmeen_data->zabihat_count;
    $zabihat_hub = $markaz_data->zabihat;//SHEHRULLAH_CONFIG->ZABIHAT;
    $zabihat_total = $zabihat_count * $zabihat_hub;

    $iftar_count = $takhmeen_data->iftar_count;
    $iftar_hub = $markaz_data->iftar;//SHEHRULLAH_CONFIG->IFTAR;
    $iftar_total = $iftar_count * $iftar_hub;

    $iftar_fadilraat_hub = 0;//SHEHRULLAH_CONFIG->IFTAR_FADILRAAT;
    $iftar_fadilraat_count = $takhmeen_data->iftar_fadilraat;
    $iftar_fadilraat_total = $iftar_fadilraat_count * $iftar_fadilraat_hub;

    $khajoor_count = $takhmeen_data->khajoor_count;
    $khajoor_hub = $markaz_data->khajoor;//SHEHRULLAH_CONFIG->KHAJOOR;
    $khajoor_total = $khajoor_count * $khajoor_hub;

    $pirsa_count = $takhmeen_data->pirsa_count;
    $pirsa_hub = $markaz_data->pirsu;//SHEHRULLAH_CONFIG->PIRSA;
    $pirsa_total = $pirsa_count * $pirsa_hub;
    $pirsa_selection = $pirsa_count > 0 ? 'Yes' : 'No';

    $fateha_count = $takhmeen_data->fateha_count;
    $fateha_hub = $markaz_data->fateha;//SHEHRULLAH_CONFIG->CHAIR;
    $fateha_total = $fateha_count * $fateha_hub;

    $chair_count = $takhmeen_data->chair_count;
    $chair_hub = $markaz_data->chair;//SHEHRULLAH_CONFIG->CHAIR;
    $chair_total = $chair_count * $chair_hub;

    $niyaz_section_total = $sehori_total + $total_niyaz;
    $other_section_total = $chair_total + $pirsa_total + $khajoor_total + $iftar_fadilraat_total
        + $iftar_total + $zabihat_total;

    $net_total = $chair_total + $pirsa_total + $khajoor_total + $iftar_fadilraat_total
        + $iftar_total + $zabihat_total + $sehori_total + $total_niyaz;
    $takhmeen = $takhmeen_data->takhmeen;

    echo "
    <table class='table table-bordered'>
        <tr>                            
            <th style='font-size: 12px' colspan=9>Niyaz/Other Khdimat</th>
        </tr>
        <tr>            
            <th style='font-size: 12px'>Full Niyaz</th><td style='font-size: 12px'>$markaz_data->full_niyaz</td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
            <th style='font-size: 12px'>Half Niyaz</th><td style='font-size: 12px'>$markaz_data->half_niyaz</td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
            <th style='font-size: 12px'>Family Niyaz</th><td style='font-size: 12px'>$family_hub</td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
        </tr>
        <tr>            
            <th style='font-size: 12px'>Zabihat</th><td style='font-size: 12px'>($zabihat_hub)</td><td>&nbsp;</td>
            <th style='font-size: 12px'>Iftar</th><td style='font-size: 12px'>$iftar_hub</td><td>&nbsp;</td>
            <th style='font-size: 12px'>Fateha</th><td style='font-size: 12px'>$fateha_hub</td><td>&nbsp;</td>
        </tr>
        <tr>            
            <th style='font-size: 12px'>Khajoor</th><td style='font-size: 12px'>$khajoor_hub</td><td style='font-size: 12px'>&nbsp;</td>
            <th style='font-size: 12px'>Pirsa</th><td style='font-size: 12px'>$pirsa_hub</td><td style='font-size: 12px'>$pirsa_selection</td>
            <th style='font-size: 12px'>Chair</th><td style='font-size: 12px'>$chair_hub x $chair_count</td><td style='font-size: 12px'>$chair_total</td>
        </tr>        
    </table>
    ";
}

function __display_niyaz_section_old(...$data)
{

    $takhmeen_data = (object) $data[0];
    $markaz_data = $data[1];

    $family_hub = $takhmeen_data->family_hub;
    $niyaz_type = $takhmeen_data->niyaz_type;
    $niyaz_count = $takhmeen_data->niyaz_count;
    $niyaz_hub = get_niyaz_amount_for($niyaz_type, $family_hub, $markaz_data);
    $total_niyaz = $niyaz_hub * $niyaz_count;

    $full_niyaz_count = $niyaz_type == 'full' ? $niyaz_count : 0;
    $half_niyaz_count = $niyaz_type == 'half' ? $niyaz_count : 0;
    $family_niyaz_count = $niyaz_type == 'family' ? $niyaz_count : 0;

    $full_niyaz_total = $markaz_data->full_niyaz * $full_niyaz_count;
    $half_niyaz_total = $markaz_data->half_niyaz * $half_niyaz_count;
    $family_niyaz_total = $family_hub * $family_niyaz_count;


    $sehori_count = $takhmeen_data->sehori_count;
    $sehori_hub = $markaz_data->sehori;//SHEHRULLAH_CONFIG->SEHORI;
    $sehori_total = $sehori_count * $sehori_hub;

    $net_total = $sehori_total + $total_niyaz;

    $zabihat_count = $takhmeen_data->zabihat_count;
    $zabihat_hub = $markaz_data->zabihat;//SHEHRULLAH_CONFIG->ZABIHAT;
    $zabihat_total = $zabihat_count * $zabihat_hub;

    $iftar_count = $takhmeen_data->iftar_count;
    $iftar_hub = $markaz_data->iftar;//SHEHRULLAH_CONFIG->IFTAR;
    $iftar_total = $iftar_count * $iftar_hub;

    $iftar_fadilraat_hub = 0;//SHEHRULLAH_CONFIG->IFTAR_FADILRAAT;
    $iftar_fadilraat_count = $takhmeen_data->iftar_fadilraat;
    $iftar_fadilraat_total = $iftar_fadilraat_count * $iftar_fadilraat_hub;

    $khajoor_count = $takhmeen_data->khajoor_count;
    $khajoor_hub = $markaz_data->khajoor;//SHEHRULLAH_CONFIG->KHAJOOR;
    $khajoor_total = $khajoor_count * $khajoor_hub;

    $pirsa_count = $takhmeen_data->pirsa_count;
    $pirsa_hub = $markaz_data->pirsa;//SHEHRULLAH_CONFIG->PIRSA;
    $pirsa_total = $pirsa_count * $pirsa_hub;
    $pirsa_selection = $pirsa_count > 0 ? 'Yes' : 'No';

    $fateha_count = $takhmeen_data->fateha_count;
    $fateha_hub = $markaz_data->fateha;//SHEHRULLAH_CONFIG->CHAIR;
    $fateha_total = $fateha_count * $fateha_hub;

    $chair_count = $takhmeen_data->chair_count;
    $chair_hub = $markaz_data->chair;//SHEHRULLAH_CONFIG->CHAIR;
    $chair_total = $chair_count * $chair_hub;

    $niyaz_section_total = $sehori_total + $total_niyaz;
    $other_section_total = $chair_total + $pirsa_total + $khajoor_total + $iftar_fadilraat_total
        + $iftar_total + $zabihat_total;

    $net_total = $chair_total + $pirsa_total + $khajoor_total + $iftar_fadilraat_total
        + $iftar_total + $zabihat_total + $sehori_total + $total_niyaz;
    $takhmeen = $takhmeen_data->takhmeen;

    echo "
    <table class='table table-bordered'>
        <tr>                            
            <th colspan=2>Niyaz Khdimat</th>
            <th colspan=2>Other Khidmat</th>
        </tr>
        <tr>            
            <th>Full Niyaz</th><td>$markaz_data->full_niyaz</td><td>&nbsp;</td>
            <th>Zabihat</th><td>($zabihat_hub)</td><td>&nbsp;</td>
        </tr>
        <tr>            
            <th>Half Niyaz</th><td>$markaz_data->half_niyaz</td><td>&nbsp;</td>
            <th>Iftar</th><td>$iftar_hub</td><td>&nbsp;</td>
        </tr>
        <tr>            
            <th>Family Niyaz</th><td>$family_hub</td><td>&nbsp;</td>
            <th>Fateha</th><td>$fateha_hub</td><td>&nbsp;</td>
        </tr>
        <tr>            
            <th>Sehori</th><td>$sehori_hub</td><td>&nbsp;</td>
            <th>Khajoor</th><td>$khajoor_hub</td><td>&nbsp;</td>
        </tr>        
        <tr> 
            <th>Pirsa</th><td>$pirsa_hub</td><td>$pirsa_selection</td>
            <th>Chair</th><td>$chair_hub x $chair_count</td><td>$chair_total</td>
        </tr>        
    </table>
    ";
}

function __show_its_and_name($row, $index)
{
    $its = $row->its_id;
    $name = $row->full_name;
    $index = ((int) $index) + 1;
    return "$index. [$its] $name";
}

function __show_gender_and_age($row, $index)
{
    $age = $row->age;
    $gender = $row->gender;
    $gender = substr($gender, 0, 1);

    return "$gender/$age";
}