<?php

DEFINE('NO_TEMPLATE', true);


header('Content-Disposition: attachment; filename="filename.csv";');

$hijri_year = get_current_hijri_year();
    $query = 'SELECT i.its_id as ITSNo, "Sherullah Niyaz" as Hoobname, 
    r.id as ReceiptNo, DATE_FORMAT(r.created, "%d/%m/%Y") as ReceiptDate, r.year, i.full_name as ReceiptName, 
    r.amount as Amount, r.payment_mode as PayType, r.transaction_ref as ChequeNo, 
    r.remarks as Remarks, u.name as CreatedBy 
    FROM kl_shehrullah_collection_record r 
    JOIN its_data i ON i.its_id = r.hof_id 
    LEFT JOIN kl_shehrullah_roles u ON u.itsid = r.createdby
    WHERE r.year=?;';
    
    $result = run_statement($query, $hijri_year);
    $report_data = $result->data;        
    echo "S/No,ITSNo,Hoobname,ReceiptNo,ReceiptDate,year,ReceiptName,Amount,PayType,ChequeNo,Remarks,CreatedBy" . PHP_EOL;
    $sno = 0;
    foreach($report_data as $row) {
        $sno++;
        echo "$sno,{$row->ITSNo},{$row->Hoobname},{$row->ReceiptNo},{$row->ReceiptDate},{$row->year},{$row->ReceiptName},{$row->Amount},{$row->PayType},{$row->ChequeNo},{$row->Remarks},{$row->CreatedBy}" . PHP_EOL;
    }