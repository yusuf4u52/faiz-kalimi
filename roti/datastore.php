<?php

function get_current_miqaat()
{
    $query = 'SELECT id,name,details,start_datetime,end_datetime FROM miqaat 
    WHERE TIMESTAMPDIFF(SECOND, start_datetime, now()) >= 0 and
    TIMESTAMPDIFF(SECOND, end_datetime, now()) <= 0 and id > 0 limit 1';

    return fetch_data($query);
}

function get_miqaat_byid($id)
{
    $query = "SELECT id,name,details,start_datetime,end_datetime FROM miqaat 
    WHERE id = '$id'";
    return fetch_data($query);
}

function get_thaali_by_sabeel($sabeel)
{
    //$query = "SELECT * FROM thalilist WHERE Thali =  '$sabeel'";
    $query = "Select
thali as SabilNo,
name as SabilHolderName,
ITS_no,
contact as SabilHolderContact,
whatsapp,
Sec.sectorits as Sector,
Sub.sub_sectorits as SubSectorITS,
wingflat,
society,
Full_address,
Sub.incharge_female_fullname as MusaidName,
Sub.female_mobile_No as MusaidaContact,
Sec.incharge_female_fullname as MasoolName,
Sec.female_mobile_No as MasoolaContact,
Transporter
from thalilist T
left Join subsector Sub on T.subsector = Sub.subsector 
left join subsector Sec on Sec.sub_sectorits = 'Masoolin' and Sec.sector = T.sector
where thali ='$sabeel'";
    return fetch_data($query);
}

function get_rotimaker_by_sabeel($sabeel)
{
    $query = "SELECT * FROM roti_maker WHERE sabeel =  '$sabeel'";
    return fetch_data($query);
}

function get_roti_data_for($sabeel, $miqaat)
{
    $query = "SELECT roti_count FROM roti_data WHERE sabeel =  '$sabeel' and event = '$miqaat'";
    return fetch_data($query);
}

function register_roti_maker($sabeel, $itsid, $full_name, $contact, $roti_count, $miqaat_id)
{
    $query = "INSERT INTO roti_maker (itsid,full_name,mobile,roti_count,sabeel) 
    VALUES ('$itsid','$full_name','$contact','$roti_count','$sabeel');";

    $rotimaker_result = change_data($query);
    if (is_record_found($rotimaker_result)) {
        return set_roti_count_for_miqaat($sabeel, $roti_count, $miqaat_id);
    } else {
        return 'negative';
    }
}

function set_roti_count_for_miqaat($sabeel, $roti_count, $miqaat_id)
{
    $query = "INSERT INTO roti_data (sabeel, event, roti_count) 
    VALUES ('$sabeel','$miqaat_id','$roti_count') ON DUPLICATE KEY UPDATE roti_count='$roti_count';";
    $rotidata_result = change_data($query);
    if (is_record_found($rotidata_result)) {
        return 'pass';
    } else {
        return 'fail';
    }

}
