<?php
function select_query_for_miqaat() {
    return 'SELECT id,name,details,
    CONVERT_TZ(start_datetime, "+00:00","+05:30") as start_datetime,
    CONVERT_TZ(end_datetime, "+00:00","+05:30") as end_datetime,
    survey_for FROM rsvp_miqaat ';
}

/**
 * Get the current active miqaat details
 * 
 * @return void
 */
function get_current_miqaat()
{
    $query = select_query_for_miqaat() . ' WHERE 
    TIMESTAMPDIFF(SECOND, start_datetime, now()) >= 0 and
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
    $query = select_query_for_miqaat() . " WHERE id = '$miqaat_id';";

    return fetch_data($query);
}
/**
 * 
 * Get details of last expired miqaat
 * @return mixed
 */
function get_last_miqaat()
{
    $query = select_query_for_miqaat() . ' WHERE TIMESTAMPDIFF(SECOND, end_datetime, now()) >= 0 and id > 0
    order by end_datetime desc limit 1';

    return fetch_data($query);
}


function get_miqaat_list()
{
    $query = select_query_for_miqaat() . ' WHERE TIMESTAMPDIFF(DAY, end_datetime, now()) <= 10 and id > 0
    order by start_datetime';

    return fetch_data($query);
}

function add_miqaat($name, $details, $start_datetime, $end_datetime, $survey_for)
{
    $query = "INSERT INTO rsvp_miqaat (name, details, start_datetime, end_datetime, survey_for) 
    VALUES ('$name', '$details', CONVERT_TZ('$start_datetime', '+00:00','-05:30'), CONVERT_TZ('$end_datetime', '+00:00','-05:30'), '$survey_for');";
    return change_data($query);
}

function edit_miqaat($id, $name, $details, $start_datetime, $end_datetime, $survey_for)
{
    $query = "UPDATE rsvp_miqaat SET name='$name', details='$details', start_datetime=CONVERT_TZ('$start_datetime', '+00:00','-05:30'), 
    end_datetime=CONVERT_TZ('$end_datetime', '+00:00','-05:30'), survey_for='$survey_for' WHERE id='$id';";

    return change_data($query);
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


function get_roti_miqaat_by_id($miqaat_id)
{
    $query = "SELECT id,name,details,
    CONVERT_TZ(start_datetime, '+00:00','+05:30') as start_datetime,
    CONVERT_TZ(end_datetime, '+00:00','+05:30') as end_datetime,
    roti_target FROM miqaat WHERE id = '$miqaat_id';";

    return fetch_data($query);
}

function get_roti_miqaat_list()
{
    $query = 'SELECT id,name,details,
    CONVERT_TZ(start_datetime, "+00:00","+05:30") as start_datetime,
    CONVERT_TZ(end_datetime, "+00:00","+05:30") as end_datetime,
    roti_target FROM miqaat WHERE TIMESTAMPDIFF(DAY, end_datetime, now()) <= 10 and id > 0
    order by start_datetime';

    return fetch_data($query);
}

function add_roti_miqaat($name, $details, $start_datetime, $end_datetime, $roti_target)
{
    $query = "INSERT INTO miqaat (name, details, start_datetime, end_datetime, roti_target) 
    VALUES ('$name', '$details', CONVERT_TZ('$start_datetime', '+00:00','-05:30'), CONVERT_TZ('$end_datetime', '+00:00','-05:30'), '$roti_target');";
    return change_data($query);
}

function edit_roti_miqaat($id, $name, $details, $start_datetime, $end_datetime, $roti_target)
{
    $query = "UPDATE miqaat SET name='$name', details='$details', start_datetime=CONVERT_TZ('$start_datetime', '+00:00','-05:30'), 
    end_datetime=CONVERT_TZ('$end_datetime', '+00:00','-05:30'), roti_target='$roti_target' WHERE id='$id';";

    return change_data($query);
}

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
    i.mohallah
    from thalilist s
    left join its_data i on s.ITS_No =i.hof_id
    left join rsvp_miqaat_survey_data sd ON sd.its_id = i.its_id and sd.miqaat_id = '$miqaat_id'
    WHERE s.ITS_No='$hof_id'
    ORDER BY i.mohallah";
    return fetch_data($query);
}

function add_family_member($hof_id, $sabeel, $its_id, $full_name, $gender, $age)
{
    $query = "INSERT INTO its_data (hof_id, sabeel_no,its_id,full_name,gender,age,mohallah) 
    VALUES ('$hof_id',  '$sabeel' ,'$its_id', '$full_name', '$gender', '$age','Other');";
    return change_data($query);
}

function add_family_hof($hof_id)
{
    $query = "INSERT INTO its_data (hof_id,its_id, sabeel_no,full_name,gender,age,email,address, sector, subsector,mohallah) 
    SELECT ITS_No as hof_id, ITS_No as its_id,Thali as sabeel_no,NAME as full_name,
    'Male' as gender,100 as age, Email_ID as email, Full_Address as address, sector, subsector, 'Other' as mohalla    
    FROM thalilist WHERE ITS_No = '$hof_id';";

    return change_data($query);
}

function query_for_miqaat_count()
{
    return 'select m.id AS id,
    m.name AS name,
    m.start_datetime AS start_datetime,
    m.end_datetime AS end_datetime,
    sd.its_id AS its_id,
    i.sector,
    i.subsector,
    sd.created_at AS created_at,
    i.full_name AS full_name,
    i.age AS age,
    i.gender AS gender,
    (case when ((i.age > -(1)) 
    and (i.age < 6)) 
    then "Infant" when ((i.age > 5) and (i.age < 11)) 
    then "Child" when (i.age >= 11) then "Adult" else "NA" end) AS Type 

    FROM rsvp_miqaat m 
    join rsvp_miqaat_survey_data sd on sd.miqaat_id = m.id 
    join its_data i on i.its_id = sd.its_id';
}

function get_miqaat_stats_report()
{
    $inner_query =  query_for_miqaat_count();
    $query = 
    'SELECT id, name AS name,start_datetime AS start_datetime,end_datetime AS end_datetime,
    sum((case when ((gender = "Male") and ((Type = "Child") or (Type = "Adult"))) then 1 else 0 end)) AS mardo,
    sum((case when ((gender = "Female") and ((Type = "Child") or (Type = "Adult"))) then 1 else 0 end)) AS bairo,
    sum((case when ((Type = "Infant") and ((gender = "Male") or (gender = "Female"))) then 1 else 0 end)) AS infant,
    sum((case when (((gender = "Male") or (gender = "Female")) and ((Type = "Child") or (Type = "Adult"))) then 1 else 0 end)) AS total 
    from ('.$inner_query.') x 
    GROUP BY name,start_datetime,end_datetime  ORDER BY end_datetime desc;';

    return fetch_data($query);
}

function get_miqaat_sector_count($id) {
    $inner_query =  query_for_miqaat_count();
    $query = "select 
    name, sector, subsector, count(id) as count
    from ($inner_query) x
    WHERE id = '$id'
    GROUP BY name, sector, subsector";

    return fetch_data($query);
}

function delete_member($its_id, $miqaat_id) {
    $query = "DELETE FROM its_data WHERE its_id='$its_id';
        DELETE FROM rsvp_miqaat_survey_data 
        WHERE its_id='$its_id' and miqaat_id='$miqaat_id';";
    return change_multi_data($query);        
}

function mark_attendance($hof_id, $miqaat_id, $family_its_list)
{
    $query = "DELETE FROM rsvp_miqaat_survey_data 
        WHERE hof_id='$hof_id' and miqaat_id='$miqaat_id';";

    $count = 0;
    if (isset($family_its_list) && count($family_its_list) > 0) {
        $query .= "INSERT INTO rsvp_miqaat_survey_data (its_id, hof_id, miqaat_id, created_at) VALUES ";
        foreach ($family_its_list as $its_id) {
            if ($count > 0) {
                $query .= ',';
            }
            $query .= "('$its_id','$hof_id','$miqaat_id', now())";
            $count++;
        }
        $query .= ';';
    }

    change_multi_data($query);
    return $count;
}

function add_attendance_for($its_id, $hof_id, $miqaat_id)
{
    $query = "INSERT INTO rsvp_miqaat_survey_data (its_id, hof_id, miqaat_id, created_at) 
    VALUES ('$its_id','$hof_id','$miqaat_id', now());";
    return change_data($query);
}
