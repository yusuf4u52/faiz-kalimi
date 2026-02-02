<?php
//DEFINE('NO_TEMPLATE', true);

$id = getAppData('arg1');
if (is_null($id)) {
    do_redirect_with_message('/home', 'Invalid receipt ID');
}

//content_display();

function content_display()
{
    $id = getAppData('arg1');
    $hijri_year = get_current_hijri_year();
    $receipt_data = get_collection_record($id, $hijri_year);

    if (is_null($receipt_data)) {
        do_redirect_with_message('/home', 'Invalid receipt ID');
    } else {
        setAppData('receipt_record', $receipt_data);
    }

    function __display_receipt_section_3() {
        $receipt_record = getAppData('receipt_record');
        $payment_mode = $receipt_record->payment_mode;
        $receipt_id =  substr(strtoupper($payment_mode), 0, 1)
        . "-" . $receipt_record->id;

        $hofid = $receipt_record->hof_id;
        $name = $receipt_record->full_name;
        $paid = $receipt_record->paid_amount;
        $receipt_amount = $receipt_record->amount;
        $takhmeen = $receipt_record->takhmeen;
        $date_created = $receipt_record->created;
        $date_cr=date_create($date_created);
        $date_format = date_format($date_cr,"j/F/Y");
        $pending = $takhmeen - $paid;
        $transaction_ref = $receipt_record->transaction_ref;
        $hijri_year = get_current_hijri_year();
        ?>
        <table class='table table-bordered'>
            <tr>
                <td>Receipt : <?= $receipt_id ?></td>
                <td class="text-right ">Date : <?= $date_format; ?></td>
            </tr>
            <tr>
                <td colspan="2" class="text-center "><h3>Shehrullah Mubarak - <?=$hijri_year?>H</h3></td>
            </tr>
            <tr>
                <td colspan="2">Received with thanks from </td>
            </tr>
            <tr>
                <td colspan="2"><?= $hofid . ' - ' . $name ?> </td>
            </tr>
            <tr>
                <td colspan="2">a voluntary contribution of Rs.<?= $receipt_amount ?>/-</td>
            </tr>
            <tr>
                <td colspan="2">on account of Shehrullah <?=$hijri_year?>H. </td>
            </tr>
            <tr rowspan="3">
            <td>HOF Signature : __________________</td>
            <td>Auth Signature: __________________</td>
            </tr>
        
        <!-- <tr>
                <td>
                    <div class="row">
                        <div class="col-6">Receipt : <?= $receipt_id ?></div>
                        <div class="col-6">Date : <?= $date_format; ?></div>
                    </div>
                    <div class="row">
                        <div class="col-12"><h3>Shehrullah Mubarak - <?=$hijri_year?>H</h3></div>                    
                    </div>
                    <hr/>
                    <div class="row">
                        <div class="col-12">
                        Received with thanks from __<?= $hofid . ' - ' . $name ?> 
                        a voluntary contribution of Rs.<?= $receipt_amount ?>/-
                        on account of Shehrullah <?=$hijri_year?>H. 
                        </div>                    
                    </div>
                    <div class="row">
                        <div class="col-6">HOF Signature : __________________</div>
                        <div class="col-6">Auth Signature: __________________</div>
                    </div>
                </td>
            </tr> -->
        </table>
        <?php
    }

    function __display_receipt_section()
    {
        $receipt_record = getAppData('receipt_record');
        $payment_mode = $receipt_record->payment_mode;
        $receipt_id =  substr(strtoupper($payment_mode), 0, 1)
        . "-" . $receipt_record->id;

        $hofid = $receipt_record->hof_id;
        $name = $receipt_record->full_name;
        $paid = $receipt_record->paid_amount;
        $receipt_amount = $receipt_record->amount;
        $takhmeen = $receipt_record->takhmeen;
        $date_created = $receipt_record->created;
        $date_cr=date_create($date_created);
        $date_format = date_format($date_cr,"j/F/Y");
        $pending = $takhmeen - $paid;
        $transaction_ref = $receipt_record->transaction_ref;
        ?>
        <form action='' method='post'>

            <table>
                <tr align=center>                    
                    <td align=center>
                        <h1>Shehrullah Mubarak - 1446H</h1>
                    </td>
                </tr>
                <tr>
                    <td>Date: <?= $date_format; ?></td>
                    <td><label class="col-form-label">Receipt Number : </label><?= $receipt_id ?></td>
                </tr>
                <tr>
                    <td colspan="2"><label class="col-form-label">HOF</label>: <?= $hofid . ' - ' . $name ?></td>
                </tr>
                <tr>
                    <td><label class="col-form-label">Recieved as a voluntary contribution&nbsp;</label>Rs.<?= $receipt_amount ?>/-</td>
                </tr>
                <tr align=center>
                    <td><br /></td>
                </tr>
                <tr>
                    <th>HOF Signature</th>
                    <th>Auth. Signature</th>
                </tr>
                <tr align=center>
                    <td><br /></td>
                </tr>
                <tr align=center>
                    <td><br /></td>
                </tr>
                <tr>
                    <th>_________________________________</th>
                    <th>_________________________________</th>
                </tr>
                <tr align=center>
                    <td><br /></td>
                </tr>
            </table>
        </form>
        <?php
    }
    ?>
    <div class="card">
        <div class='card-body' id="printableArea">
            <?php
            echo show_section_content('Receipt', '__display_receipt_section_3');
            echo '<br/><br/><hr/><br/><br/>';
            echo show_section_content('Copy', '__display_receipt_section_3');
            ?>
        </div>
        <div class='card-footer row' id='print_button_section'>
            <div class='col-6'>
                <button class='btn btn-primary' id='Print'>Print</button>
            </div>
            <div class='col-6'>
                <a class='btn btn-primary' href='<?= getAppData('BASE_URI') ?>/receipts.receipt'>Go to Receipt</a>
            </div>
        </div>
    </div>


    <script>
        function the_script() {
            $('#Print').click(function () {
                var printContents = document.getElementById('printableArea').innerHTML;
                var originalContents = document.body.innerHTML;

                var htmlToPrint = '' +
        '<style type="text/css">' +
        'table th, table tr, table td {' +
        'border:1px solid #000;' +
        'padding:0.5em;' +
        '}' +
        '</style>';
    htmlToPrint += printContents;

                document.body.innerHTML = htmlToPrint;
                window.print();
                document.body.innerHTML = originalContents;
            });
        }
    </script> 
    <?php

}