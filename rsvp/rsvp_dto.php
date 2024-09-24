<?php
/**
 * Get the current active miqaat details
 * 
 * @return void
 */
function get_current_miqaat()
{
    $query = 'SELECT id,name,details,start_datetime,end_datetime,survey_for FROM rsvp_miqaat 
    WHERE TIMESTAMPDIFF(SECOND, start_datetime, now()) >= 0 and
    TIMESTAMPDIFF(SECOND, end_datetime, now()) <= 0 and id > 0 limit 1';

    return fetch_data($query);
}

/**
 * Search miqaat by id.
 * 
 * @param mixed $miqaat_id
 * @return mixed
 */
function get_miqaat_by_id($miqaat_id)
{
    $query = "SELECT id,name,details,start_datetime,end_datetime,survey_for FROM rsvp_miqaat 
    WHERE id = '$miqaat_id';";

    return fetch_data($query);
}
/**
 * 
 * Get details of last expired miqaat
 * @return mixed
 */
function get_last_miqaat()
{
    $query = 'SELECT id,name,details,start_datetime,end_datetime,survey_for 
    FROM rsvp_miqaat 
    WHERE TIMESTAMPDIFF(SECOND, end_datetime, now()) >= 0 and id > 0
    order by id desc limit 1';

    return fetch_data($query);
}

/**
 * Get current or last expired miqaat details.
 * @return mixed
 */
function get_current_or_last_miqaat()
{
    $result = get_current_miqaat();
    if (!is_record_found($result)) {
        $result = get_last_miqaat();
    }
    return $result;
}

// function get_sabeel_details($sabeel) {
//     $query = "select distinct s.its_id as hof_id,
// i.its_id,
// s.sabeel_no,
// s.full_name as sabeel_holder,
// s.mobile_no,
// i.full_name,
// i.age,
// i.gender,
// i.sector,
// i.subsector from sabeel_data s
// left join its_data i on s.its_id=i.hof_id
// where s.sabeel_no='$sabeel'";
// return fetch_data($query);
// }

function get_family_details($hof_id, $miqaat_id)
{
    $query = "select distinct 
    sd.its_id as attendee,
    s.ITS_No as hof_id,
    i.its_id,
    s.Thali as sabeel_no,
    s.NAME as sabeel_holder,
    s.CONTACT as mobile,
    s.Full_Address as address,
    s.Email_ID as email_id,
    s.sector,
    s.subsector,
    i.full_name,
    i.age,
    i.gender,
    i.sector,
    i.subsector from thalilist s
    left join its_data i on s.ITS_No =i.hof_id
    left join rsvp_miqaat_survey_data sd ON sd.its_id = i.its_id and sd.miqaat_id = '$miqaat_id'
    WHERE s.ITS_No='$hof_id'";
    return fetch_data($query);
}

// function get_sabeel_details($hof_id, $miqaat_id)
// {
//     $query = "select distinct 
//     sd.its_id as attendee,
//     s.ITS_No as hof_id,
//     i.its_id,
//     s.Thali as sabeel_no,
//     s.NAME as sabeel_holder,
//     s.CONTACT as mobile,
//     s.Full_Address as address,
//     s.Email_ID as email_id,
//     s.sector,
//     s.subsector,
//     i.full_name,
//     i.age,
//     i.gender,
//     i.sector,
//     i.subsector from thalilist s
//     left join its_data i on s.ITS_No =i.hof_id
//     left join rsvp_miqaat_survey_data sd ON sd.its_id = i.its_id and sd.miqaat_id = '$miqaat_id'
//     WHERE s.ITS_No='$hof_id'";
//     return fetch_data($query);
// }

function add_family_member($hof_id, $sabeel, $its_id, $full_name, $gender, $age)
{
    $query = "INSERT INTO its_data (hof_id, sabeel_no,its_id,full_name,gender,age,mohallah) 
    VALUES ('$hof_id',  '$sabeel' ,'$its_id', '$full_name', '$gender', '$age','Other');";
    return change_data($query);
}

// function add_family_hof($hof_id, $sabeel, $full_name, $gender, $age, $email, $address, $sector, $subsector) {
//     $query = "INSERT INTO its_data (hof_id, sabeel_no,its_id,full_name,gender,age,email,address, sector, subsector,mohallah) 
//     VALUES ('$hof_id',  '$sabeel' ,'$hof_id', '$full_name', '$gender', '$age', '$email', '$address', '$sector', '$subsector','Other');";
//     return change_data($query);
// }

// function add_family_hof($sabeel)
// {
//     $query = "INSERT INTO its_data (hof_id,its_id, sabeel_no,full_name,gender,age,email,address, sector, subsector,mohallah) 
//     SELECT ITS_No as hof_id, ITS_No as its_id,Thali as sabeel_no,NAME as full_name,
//     'unknown' as gender,100 as age, Email_ID as email, Full_Address as address, sector, subsector, 'Other' as mohalla    
//     FROM thalilist WHERE Thali = '$sabeel';";

//     //VALUES ('$hof_id',  '$sabeel' ,'$hof_id', '$full_name', '$gender', '$age', '$email', '$address', '$sector', '$subsector','Other')
//     return change_data($query);
// }

function add_family_hof($hof_id)
{
    $query = "INSERT INTO its_data (hof_id,its_id, sabeel_no,full_name,gender,age,email,address, sector, subsector,mohallah) 
    SELECT ITS_No as hof_id, ITS_No as its_id,Thali as sabeel_no,NAME as full_name,
    'Male' as gender,100 as age, Email_ID as email, Full_Address as address, sector, subsector, 'Other' as mohalla    
    FROM thalilist WHERE ITS_No = '$hof_id';";

    return change_data($query);
}

function get_miqaat_stats_report() {
    $query = 'SELECT * FROM vw_miqaat_ginti;';
    return fetch_data($query);
}

function mark_attendance($hof_id, $miqaat_id, $family_its_list) {
    $query = "DELETE FROM rsvp_miqaat_survey_data 
        WHERE hof_id='$hof_id' and miqaat_id='$miqaat_id';";

$count = 0;
if (isset($family_its_list) && count($family_its_list) > 0) {
    $query .= "INSERT INTO rsvp_miqaat_survey_data (its_id, hof_id, miqaat_id) VALUES ";
    foreach ($family_its_list as $its_id) {
        if( $count > 0 ) {
            $query .= ',';
        }
        $query .= "('$its_id','$hof_id','$miqaat_id')";
        $count++;
    }
    $query .= ';';
}

change_multi_data($query);
return $count;
}