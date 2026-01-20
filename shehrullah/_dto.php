<?php
DEFINE('SUPER_ADMIN', 'super_admin');
DEFINE('DATA_ENTRY', 'data_entry');
DEFINE('TAKHMEENER', 'takhmeen');
DEFINE('RECEPTION', 'reception');

DEFINE('ROLE', [SUPER_ADMIN, DATA_ENTRY, TAKHMEENER, RECEPTION]);

function is_user_role($role)
{
    $userData = getSessionData(THE_SESSION_ID);
    $roles = $userData->roles;
    return in_array($role, $roles);
}

function is_user_a(...$expected)
{
    $userData = getSessionData(THE_SESSION_ID);
    $roles = $userData->roles;

    $result = array_intersect($expected, $roles);
    return !empty($result) ? true : false;
}

function is_super_admin()
{
    return is_user_role('super_admin');
}

function is_data_entry()
{
    return is_user_role('data_entry');
}

//--------------------------------------------
// DATABASE AREA
//--------------------------------------------

function add_markaz_hub_data(
    $year,
    $start_eng_date,
    $full_niyaz,
    $half_niyaz,
    $family_niyaz,
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
    $pirsu
) {
    $query = 'INSERT INTO kl_shehrullah_config (year, start_eng_date, full_niyaz, half_niyaz, family_niyaz, per_kid_niyaz,zero_hub_age, half_hub_age,sehori, iftar, zabihat, fateha, khajoor, chair, parking, pirsu)
    values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE start_eng_date=?,full_niyaz=?, half_niyaz=?, family_niyaz=?, per_kid_niyaz=?,zero_hub_age=?, half_hub_age=?,sehori=?, iftar=?, zabihat=?, fateha=?, khajoor=?, chair=?, parking=?, pirsu=?;';
    $result = run_statement($query, $year, $start_eng_date, $full_niyaz, $half_niyaz, $family_niyaz, $per_kid_niyaz, $zero_hub_age, $half_hub_age, $sehori, $iftar, $zabihat, $fateha, $khajoor, $chair, $parking, $pirsu, $start_eng_date, $full_niyaz, $half_niyaz, $family_niyaz, $per_kid_niyaz, $zero_hub_age, $half_hub_age, $sehori, $iftar, $zabihat, $fateha, $khajoor, $chair, $parking, $pirsu);
    return $result->success ? null : $result->message;
}

function get_user_records()
{
    $query = 'SELECT itsid, name, roles FROM kl_shehrullah_roles;';
    $result = run_statement($query);
    return $result->success && $result->count > 0 ? $result->data : [];
}

function get_user_record_for($itsid)
{
    $query = 'SELECT * FROM kl_shehrullah_roles WHERE itsid=?;';
    $result = run_statement($query, $itsid);
    return $result->success && $result->count > 0 ? $result->data[0] : null;
}

function add_user_record($itsid, $name, $password, $roles)
{
    $query = 'INSERT INTO kl_shehrullah_roles (itsid, name, passwd, roles)
    VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE name=?, passwd=?, roles=?;';
    $result = run_statement($query, $itsid, $name, $password, $roles, $name, $password, $roles);
    return $result->success ? null : $result->message;
}

///////

function update_vjb_form_data($hof_id,$gents_count, $ladies_count, $kids_count, $amwat_count, $hamal_count) {
    $year = get_current_hijri_year();
    $query = 'INSERT INTO kl_shehrullah_vjb_formdata 
    (hof_id, hijri_year, gents_count, ladies_count, kids_count, amwat_count, hamal_count, created)
    VALUES (?,?,?,?,?,?,?,now()) ON DUPLICATE KEY UPDATE
    gents_count=?, ladies_count=?, kids_count=?, amwat_count=?, hamal_count=?';
    $result = run_statement($query, $hof_id,$year, $gents_count, $ladies_count, $kids_count, $amwat_count, $hamal_count, $gents_count, $ladies_count, $kids_count, $amwat_count, $hamal_count);
    return $result->success ? null : $result->message;
}

function get_slot_details($slot_id) {
    $year = get_current_hijri_year();
    $query = 'SELECT * FROM kl_shehrullah_vjb_slots WHERE id=? and hijri_year=?;';
    $result = run_statement($query, $slot_id, $year);
    return $result->success && $result->count > 0 ? $result->data[0] : null;
}

function get_slot_records() {
    $year = get_current_hijri_year();
    $query = 'SELECT * FROM kl_shehrullah_vjb_slots WHERE hijri_year=?;';
    $result = run_statement($query, $year);
    return $result->success && $result->count > 0 ? $result->data : [];
}

function get_slot_for_registration() {
    $year = get_current_hijri_year();
    $query = 'SELECT * FROM kl_shehrullah_vjb_slots WHERE hijri_year=? 
    and registered != capacity
    and date >= DATE_FORMAT(now(), "%Y-%m-%d")    
    ';
    $result = run_statement($query, $year);
    return $result->success && $result->count > 0 ? $result->data : [];
}

function add_vajebaat_slot($id, $title, $date, $capacity) {
    $year = get_current_hijri_year();
    $query = 'INSERT INTO kl_shehrullah_vjb_slots (id, title, date, capacity, hijri_year)
    VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE title=?, date=?, capacity=?;';
    $result = run_statement($query, $id, $title, $date, $capacity, $year, $title, $date, $capacity);
    return $result->success ? null : $result->message;
}
///////

function get_booking_details($hof_id) {
    $year = get_current_hijri_year();
    $query = 'SELECT a.*, s.* FROM kl_shehrullah_vjb_allocation a
    JOIN kl_shehrullah_vjb_slots s ON s.id = a.slot_id    
    WHERE a.hof_id=? and a.hijri_year=?;';
    $result = run_statement($query, $hof_id,$year);
    return $result->success && $result->count > 0 ? $result->data[0] : null;
}


function add_booking($hof_id, $slot_id) {
    $year = get_current_hijri_year();

    $query = 'UPDATE kl_shehrullah_vjb_slots SET registered = registered + 1 
        WHERE id=? and hijri_year=? and capacity > registered';
    $result = run_statement($query, $slot_id, $year); 
    if( $result->count == 0 ) {
        return 'Oops! This slot is full. Please select another slot.';
    }

    $query = 'INSERT INTO kl_shehrullah_vjb_allocation (hof_id, slot_id, hijri_year)
    VALUES(?,?,?);';
    $result = run_statement($query, $hof_id, $slot_id, $year);
    return $result->success ? null : $result->message;
}

//////

function family_stats_for($hof_id) {
    $year = get_current_hijri_year();
    $query = 'SELECT 
    count(case when Gender = "Male" and Misaq = "Done" then 1 else null end) mardo ,
    count(case when Gender = "Female" and Misaq = "Done" then 1 else null end) bairao ,
    count(case when Misaq = "Not Done" then 1 else null end) kids 
    FROM ITS_RECORD WHERE HOF_ID = ?;';

    $result = run_statement($query, $hof_id);
    return $result->success && $result->count > 0 ? $result->data[0] : null;

}

// function family_stats_for($hof_id) {
//     $year = get_current_hijri_year();
//     $query = 'SELECT 
//     count(case when gender = "Male" and misaq = "Done" then 1 else null end) mardo ,
//     count(case when gender = "Female" and misaq = "Done" then 1 else null end) bairao ,
//     count(case when misaq = "Not Done" then 1 else null end) kids 
//     FROM its_data WHERE hof_id = ?;';

//     $result = run_statement($query, $hof_id);
//     return $result->success && $result->count > 0 ? $result->data[0] : null;

// }

function get_hijri_records()
{
    $query = 'SELECT * FROM kl_shehrullah_config 
    ORDER BY start_eng_date desc limit 10;';
    $result = run_statement($query);
    return $result->success && $result->count > 0 ? $result->data : [];
}

function get_hijri_record_for($hijri_year)
{
    $query = 'SELECT * FROM kl_shehrullah_config where year=?;';
    $result = run_statement($query, $hijri_year);
    return $result->success && $result->count > 0 ? $result->data[0] : null;
}

function add_hijri_record($hijri_year, $english_date)
{
    $query = 'INSERT INTO kl_shehrullah_config (year, start_eng_date) 
    values(?,?);';
    $result = run_statement($query, $hijri_year, $english_date);
    return $result->success ? true : false;
}

function get_sabeel_data($sabeel)
{
    $query = 'SELECT * FROM kl_shehrullah_sabeel_data WHERE sabeel=?;';
    $result = run_statement($query, $sabeel);
    return $result->success && $result->count > 0 ? $result->data[0] : null;
}

function get_thaalilist_data($sabeel_hof)
{

    $query = 'SELECT Thali, NAME, CONTACT, sabeelType, ITS_No, 
    Email_ID,Full_Address,WhatsApp, sector
    FROM thalilist WHERE ITS_No=? or Thali=?;';
    $result = run_statement($query, $sabeel_hof, $sabeel_hof);
    return $result->success && $result->count > 0 ? $result->data[0] : null;
}

/**
 * Returns the hijri year which is 
 * 
 * @return mixed
 */
function get_current_hijri_year()
{
    $query = 'SELECT year FROM kl_shehrullah_config 
    WHERE start_eng_date < now() ORDER BY year DESC LIMIT 1;';
    $result = run_statement($query);

    if ($result->success && $result->count > 0) {
        return $result->data[0]->year;
    }
    return 0;
}

function get_receipt_data_for($year, $hof_id) {
    $query = 'SELECT * FROM kl_shehrullah_collection_record WHERE year=? and hof_id=?;';
    $result = run_statement($query, $year, $hof_id);
    if ($result->success && $result->count > 0) {
        return $result->data;
    }
    return [];
}

function get_all_receipt_data_for($year) {
    $query = 'SELECT * FROM kl_shehrullah_collection_record WHERE year=? ORDER BY id DESC;';
    $result = run_statement($query, $year);
    if ($result->success && $result->count > 0) {
        return $result->data;
    }
    return [];
}

function save_collection_record($year, $hof_id, $amount, $payment_mode, $transaction_ref, $remarks) {
    $query = 'INSERT INTO kl_shehrullah_collection_record (year, hof_id, amount, payment_mode, transaction_ref, remarks, created)
    VALUES (?,?,?,?,?,?,now()); UPDATE kl_shehrullah_takhmeen SET paid_amount = paid_amount + ? WHERE year=? and hof_id=?;';

    $result = run_statement($query,$year, $hof_id, $amount, $payment_mode, $transaction_ref, $remarks, $amount, $year, $hof_id);

    if ($result->success && $result->count > 0) {
        return $result->insertedID;
    }
    return -1;
}


function get_collection_record($id, $year) {
    $query = 'select cr.id,cr.hof_id, cr.amount,cr.payment_mode, cr.transaction_ref, cr.created, cr.remarks,t.takhmeen,
    t.paid_amount,m.full_name
    FROM kl_shehrullah_collection_record cr 
    JOIN kl_shehrullah_takhmeen t ON t.hof_id = cr.hof_id
    JOIN its_data m  ON t.hof_id = m.its_id
    where cr.id = ? and cr.year=?';
    
    //$query = 'SELECT * FROM  kl_shehrullah_collection_record WHERE id=?;';

    $result = run_statement($query, $id, $year);

    if ($result->success && $result->count > 0) {
        return $result->data[0];
    }
    return null;
}

function get_members_for($hof_id)
{
    $query = 'SELECT * FROM its_data WHERE hof_id=? and mohallah="Other" and shehrullah="N";';
    $result = run_statement($query, $hof_id);
    return $result->success && $result->count > 0 ? $result->data : [];
}

function allow_members_for_shehrullah($its_id)
{
    $query = 'UPDATE its_data SET shehrullah="Y" WHERE its_id=? and mohallah="Other";';
    $result = run_statement($query, $its_id);
    return $result->success && $result->count > 0 ? true : $result->message;
}

function get_its_record_for($hof_id) {
    $query = 'SELECT * FROM ITS_RECORD WHERE ITS_ID = ? and HOF_ID = ?;';
    $result = run_statement($query, $hof_id, $hof_id);
    if ($result->count > 0) {
        return $result->data[0];
    }   
    return null;
}

function get_hof_data($hof_id)
{
    $query = 'SELECT     
    m.its_id,m.hof_id,m.full_name,m.age,m.gender,m.sector,m.subsector,m.mohallah,m.email,m.address
    FROM its_data m    
    WHERE m.its_id = ?;';
    $result = run_statement($query, $hof_id);
    if ($result->count > 0) {
        return $result->data[0];
    }
    return null;
}

function get_attendees_data_for_nonsab($hof_id, $hijri_year, $attends = false)
{
    $query = 'SELECT 
    case when sa.masalla is null then "" else sa.masalla end as masalla, 
    case when sa.attendance_type is null then "Y" else sa.attendance_type end as attendance_type,
    case when sa.chair_preference is null then "N" else sa.chair_preference end as chair_preference,
    m.its_id,m.full_name,m.age,m.gender,m.sector,m.subsector,m.mohallah,m.photo_pending
    FROM its_data m
    LEFT JOIN  kl_shehrullah_attendees sa ON m.its_id = sa.its_id and sa.year = ?
    WHERE m.hof_id = ?  ';

    $params = [];
    if ($attends) {
        $query .= ' and sa.attendance_type="Y";';
    }
    
    $result = run_statement($query, $hijri_year, $hof_id);

    if ($result->count > 0) {
        return $result->data;
    }
    return null;
}

function get_attendees_data_for($hof_id, $hijri_year, $attends = false)
{
    $query = 'SELECT 
    case when sa.masalla is null then ? else sa.masalla end as masalla, 
    case when sa.attendance_type is null then ? else sa.attendance_type end as attendance_type,
    case when sa.chair_preference is null then ? else sa.chair_preference end as chair_preference,
    m.its_id,m.full_name,m.age,m.gender,m.sector,m.subsector,m.mohallah
    FROM its_data m
    LEFT JOIN  kl_shehrullah_attendees sa ON m.its_id = sa.its_id and sa.year = ?
    WHERE m.hof_id = ? and (m.mohallah = "Kalimi" OR m.shehrullah="Y") ';

    $params = [];
    if ($attends) {
        $query .= ' and sa.attendance_type=?;';
        $result = run_statement($query, '', 'Y', 'N', $hijri_year, $hof_id, 'Y');
    } else {
        $result = run_statement($query, '', 'Y', 'N', $hijri_year, $hof_id);
    }

    if ($result->count > 0) {
        return $result->data;
    }
    return null;
}

function add_shehrullah_takh_hub($year,$hof_id,$niyaz_hub,$iftar_count,$zabihat_count,$fateha_count,$khajoor_count,$pirsa_count,$chair_count,$takhmeen) {
    $query = "UPDATE kl_shehrullah_takhmeen SET
    niyaz_hub=?,iftar_count=?,zabihat_count=?,fateha_count=?,khajoor_count=?,pirsa_count=?,chair_count=?,takhmeen=?
    WHERE year=? and hof_id=?";

    $result = run_statement($query,$niyaz_hub,$iftar_count,$zabihat_count,$fateha_count,$khajoor_count,$pirsa_count,$chair_count,$takhmeen, $year,$hof_id);
    return $result->success;
}


function add_shehrullah_takhmeen(
    $year,
    $hof_id,
    $family_hub,
    $pirsa_count,
    $chair_count,
    $parking_count,
    $venue,
    $whatsapp,
    $sabeel
) {
    //$year = HIJRI_YEAR;
    $query = 'INSERT INTO kl_shehrullah_takhmeen
    (hof_id, year, family_hub,pirsa_count,chair_count,parking_count,venue,whatsapp,sabeel) 
    values(?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE 
    family_hub=?,pirsa_count=?,chair_count=?,
    parking_count=?,venue=?,whatsapp=?,sabeel=?,updated=now();';

    $result = run_statement(
        $query,
        $hof_id,
        $year,
        $family_hub,
        $pirsa_count,
        $chair_count,
        $parking_count,
        $venue,
        $whatsapp,
        $sabeel,
        $family_hub,
        $pirsa_count,
        $chair_count,
        $parking_count,
        $venue,
        $whatsapp,
        $sabeel
    );
    return $result->success ? true : false;
}


function add_shehrullah_attendees(
    $year,
    $its_id,
    $hof_id,
    $attendance_type,
    $chair_preference
) {
    $query = 'INSERT INTO kl_shehrullah_attendees (its_id, hof_id, year,
    attendance_type,chair_preference,created) 
    values(?,?,?,?,?,now()) 
    ON DUPLICATE KEY UPDATE attendance_type=?,
    chair_preference=?,updated=now();';

    $result = run_statement(
        $query,
        $its_id,
        $hof_id,
        $year,
        $attendance_type,
        $chair_preference,
        $attendance_type,
        $chair_preference
    );
    return $result->success ? true : false;
}

function get_hub_for_age($age, $markaz_data)
{
    if ($age < $markaz_data->zero_hub_age) {
        return 0;
    }

    if ($age <= $markaz_data->half_hub_age && $age >= $markaz_data->zero_hub_age) {
        return $markaz_data->per_kid_niyaz;
    }

    return $markaz_data->family_niyaz;
}



function get_shehrullah_data_for($year)
{
    $query = 'SELECT * FROM kl_shehrullah_config 
    WHERE year=?;';
    $result = run_statement($query, $year);

    if ($result->success && $result->count > 0) {
        return $result->data[0];
    }
    return null;
}


function get_shehrullah_takhmeen_for($hof_id, $hijri_year)
{
    $query = 'SELECT * FROM kl_shehrullah_takhmeen  WHERE hof_id=? and year=?';
    $result = run_statement($query, $hof_id, $hijri_year);
    return $result->success && $result->count > 0 ? $result->data[0] : null;
}

function get_niyaz_amount_for($type, $family_hub, $markaz_data)
{
    $options = getNiyazOptions($family_hub, $markaz_data);
    return $options->$type->amount ?? 0;
}

function getNiyazOptions($familyHub, $markaz_data)
{
    return json_decode(json_encode([
        // 'full_fadil' => [
        //     'title' => 'Full Niyaz Fadil Raat',
        //     'amount' => SHEHRULLAH_CONFIG->FULL_NIYAZ_FADILRAAT
        // ],
        'full' => [
            'title' => 'Full Niyaz',
            'amount' => $markaz_data->full_niyaz//SHEHRULLAH_CONFIG->FULL_NIYAZ
        ],
        'half' => [
            'title' => 'Half Niyaz',
            'amount' => $markaz_data->half_niyaz//SHEHRULLAH_CONFIG->HALF_NIYAZ
        ],
        'family' => [
            'title' => 'Family Hub',
            'amount' => $familyHub
        ]
    ]));
}

function prepare_its_transit()
{
   $query = 'DELETE FROM its_data_transit; INSERT INTO its_data_transit SELECT * FROM its_data; UPDATE its_data_transit SET mohallah=?;';
    $result = run_statement($query, 'Other');
    return $result->success ? true : false;
}

function move_from_transit_to_its_data()
{
    $query = 'DELETE FROM its_data; INSERT INTO its_data SELECT * FROM its_data_transit;';
    $result = run_statement($query);
    return $result->success ? true : false;

}

function add_to_transit(
    $its_id,
    $hof_id,
    $sabeel_no,
    $full_name,
    $age,
    $misaq,
    $address,
    $gender,
    $sector,
    $subsector
) {
    $query = 'INSERT INTO its_data_transit (its_id,hof_id,sabeel_no,full_name,age,misaq,address,gender,sector,subsector,mohallah) 
    VALUES (?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE hof_id=?,sabeel_no=?,full_name=?,age=?,misaq=?,address=?,
    gender=?,sector=?,subsector=?,mohallah=?;';
    $result = run_statement($query, $its_id, $hof_id, $sabeel_no, $full_name, $age, $misaq, $address, $gender, $sector, $subsector, 'Kalimi', $hof_id, $sabeel_no, $full_name, $age, $misaq, $address, $gender, $sector, $subsector, 'Kalimi');
    return $result;
}


function add_family_member($hof_id, $sabeel, $its_id, $full_name, $gender, $age, $misaq)
{
    $query = 'INSERT INTO its_data (hof_id, sabeel_no,its_id,full_name,gender,age,misaq,mohallah)
    VALUES (?,?,?,?,?,?,?,"Other");';

    $result = run_statement($query, $hof_id, $sabeel, $its_id, $full_name, $gender, $age, $misaq);
    return $result;
}

function get_registration_summary($hijri) {
    $query = 'SELECT SUM(male) males, SUM(female) females, SUM(kids) kids, sum(infant) infants, 
    sum(attendees) attendees, sum(chairs) chairs, sum(pirsa_count) pirsas, sum(zabihat_count) zabihat,
    sum(takhmeen) takhmeen, sum(paid_amount) paid
    FROM (
    SELECT a.hof_id, i.full_name,
    count(CASE WHEN a.attendance_type = "Y" and i.gender="Male" and i.age > 11 THEN 1 ELSE NULL END) as male,
    count(CASE WHEN a.attendance_type = "Y" and i.gender="Female" and i.age > 11 THEN 1 ELSE NULL END) as female,
    count(CASE WHEN a.attendance_type = "Y" and i.age < 12 and i.age > 4 THEN 1 ELSE NULL END) as kids,
    count(CASE WHEN a.attendance_type = "Y" and i.age < 5 THEN 1 ELSE NULL END) as infant,
    count(CASE WHEN a.attendance_type = "Y" THEN 1 ELSE NULL END) as attendees, 
    count(CASE WHEN a.chair_preference = "Y" THEN 1 ELSE NULL END) as chairs,
    t.pirsa_count,t.takhmeen, t.paid_amount, t.zabihat_count 
    FROM kl_shehrullah_attendees a
    JOIN its_data i ON i.its_id = a.its_id
    WHERE a.year=?
    GROUP BY a.hof_id    
    ) a;';

    // JOIN kl_shehrullah_takhmeen t ON t.hof_id = a.hof_id    
    // WHERE t.year=? and a.year=? and t.takhmeen >= 0

    $result = run_statement($query, $hijri, $hijri);
    if ($result->success && $result->count > 0) {
        return $result->data[0];
    }
    return null;
}

function get_registration_data($hijri)
{
    $query = 'SELECT a.hof_id, i.full_name,
    count(CASE WHEN a.attendance_type = "Y" and i.gender="Male" and i.age > 11 THEN 1 ELSE NULL END) as male,
    count(CASE WHEN a.attendance_type = "Y" and i.gender="Female" and i.age > 11 THEN 1 ELSE NULL END) as female,
    count(CASE WHEN a.attendance_type = "Y" and i.age < 12 and i.age > 4 THEN 1 ELSE NULL END) as kids,
    count(CASE WHEN a.attendance_type = "Y" and i.age < 5 THEN 1 ELSE NULL END) as infant,
    count(CASE WHEN a.attendance_type = "Y" THEN 1 ELSE NULL END) as attendees, 
    count(CASE WHEN a.chair_preference = "Y" THEN 1 ELSE NULL END) as chairs,
    t.pirsa_count,t.takhmeen, t.whatsapp, t.paid_amount, t.zabihat_count
    FROM kl_shehrullah_attendees a
    JOIN its_data i ON i.its_id = a.its_id
    JOIN kl_shehrullah_takhmeen t ON t.hof_id = a.hof_id   
    WHERE t.year=? and a.year=? and t.takhmeen > 0 GROUP BY a.hof_id;';

    $result = run_statement($query, $hijri, $hijri);
    if ($result->success && $result->count > 0) {
        return $result->data;
    }
    return [];
}



function get_itsdata_for($hof_id)
{
    $query = 'SELECT * FROM its_data WHERE its_id=?;';
    $result = run_statement($query, $hof_id);
    return $result->success && $result->count > 0 ? $result->data[0] : null;
}

function get_last_thali_num()
{
    $query = 'SELECT max(Thali) as thali FROM thalilist WHERE Thali REGEXP "^[0-9]+$";';
    $result = run_statement($query);
    return $result->success && $result->count > 0 ? $result->data[0]->thali : 0;
}

function add_thalilist($NAME, $CONTACT, $ITS_No, $Email_ID, $Full_Address, $WhatsApp)
{
    $thali = get_last_thali_num();
    if ($thali < 9000) {
        $thali = 90000;
    } else {
        $thali += 1;
    }

    $sabeelType = 'Non Local | FOR SHEHRULLAH';
    $query = 'INSERT INTO thalilist (Thali, NAME, CONTACT, sabeelType, ITS_No, Email_ID,Full_Address,WhatsApp) 
    VALUES (?,?,?,?,?,?,?,?);';
    $result = run_statement($query, $thali, $NAME, $CONTACT, $sabeelType, $ITS_No, $Email_ID, $Full_Address, $WhatsApp);
    return $result->success && $result->count > 0 ? $thali : 0;
}

function get_last_year_takhmeen($hof_id) {
    $year = get_current_hijri_year() - 1;

    $query = 'SELECT takhmeen FROM kl_shehrullah_takhmeen WHERE hof_id=? and year=?;';
    $result = run_statement($query, $hof_id, $year);
    return $result->success && $result->count > 0 ? $result->data[0]->takhmeen : 0;
}

function addClearanceData($sabeel, $hof_id, $clearance, $notes, $userid) {
    $year = get_current_hijri_year();
    
    $query = 'INSERT INTO kl_shehrullah_sabeel_clearance (sabeel, hof_id, hijri, notes, clearance, createdby, created) 
    VALUES (?,?,?,?,?,?,now()) ON DUPLICATE KEY UPDATE notes=?, clearance=?,updatedby=?,updated=now();';
    $result = run_statement($query, $sabeel, $hof_id, $year, $notes, $clearance, $userid,$notes, $clearance, $userid);
    return $result->success && $result->count > 0 ? true : false;
}

function getClearanceData($hof_id) {
    $year = get_current_hijri_year();
    
    $query = 'SELECT * FROM kl_shehrullah_sabeel_clearance WHERE hof_id=? and hijri=?;';
    $result = run_statement($query, $hof_id, $year);
    return $result->success && $result->count > 0 ? $result->data[0] : null;
}

function add_hof($hof_id) {

}

