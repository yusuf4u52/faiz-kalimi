<?php


function content_display() {
    $hijri_year = get_current_hijri_year();
    $query = 'SELECT i.its_id as ITSNo, "Sherullah Niyaz" as Hoobname, 
    r.id as ReceiptNo, DATE_FORMAT(r.created, "%d/%m/%Y") as ReceiptDate, r.year, i.full_name as ReceiptName, 
    r.amount as Amount, r.payment_mode as PayType, r.transaction_ref as ChequeNo, 
    r.remarks, u.name as CreatedBy 
    FROM kl_shehrullah_collection_record r 
    JOIN its_data i ON i.its_id = r.hof_id 
    LEFT JOIN kl_shehrullah_roles u ON u.itsid = r.createdby
    WHERE r.year=?;';
    
    $result = run_statement($query, $hijri_year);
    $report_data = $result->data;
    $uri = getAppData('BASE_URI');
    ?>
<div class="row">
        <div class="col-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-header row">
                    <div class="col-12">Shehrullah Hub Report</div>                    
                </div>
                <div class="card-body">
                <p><a href="<?=$uri?>/report.hoob_list_download">Download</a></p>
                <?php __display_table_records([$report_data]) ?>
                </div>
            </div>
        </div>
    </div>    
    <?php
}

function __display_table_records($data)
{
    $records = $data[0];
    util_show_data_table($records, [
        '__show_row_sequence' => 'SN#',
        'ITSNo' => 'ITSNo',
        'Hoobname' => 'Hoobname',
        'ReceiptNo' => 'ReceiptNo',
        'ReceiptDate' => 'ReceiptDate',
        'year' => 'year',
        'ReceiptName' => 'ReceiptName',
        'Amount' => 'Amount',
        'PayType' => 'PayType',
        'ChequeNo' => 'ChequeNo',
        'remarks' => 'Remarks',
        'CreatedBy' => 'Created By'
    ]);
}