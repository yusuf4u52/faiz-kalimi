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
    Email_ID,wingflat,society,Full_Address,WhatsApp, sector
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

/**
 * Calculate total paid amount from receipts for a given year and optionally hof_id
 * @param int $year Hijri year
 * @param string|null $hof_id Head of Family ID (optional - if null, returns total for all families)
 * @return float Total paid amount
 */
function get_paid_amount_from_receipts($year, $hof_id = null) {
    if ($hof_id !== null) {
        $query = 'SELECT COALESCE(SUM(amount), 0) as total_paid 
                  FROM kl_shehrullah_collection_record 
                  WHERE year=? AND hof_id=?';
        $result = run_statement($query, $year, $hof_id);
    } else {
        $query = 'SELECT COALESCE(SUM(amount), 0) as total_paid 
                  FROM kl_shehrullah_collection_record 
                  WHERE year=?';
        $result = run_statement($query, $year);
    }
    
    if ($result->success && $result->count > 0) {
        return floatval($result->data[0]->total_paid);
    }
    return 0.0;
}

function save_collection_record($year, $hof_id, $amount, $payment_mode, $transaction_ref, $remarks) {
    // Calculate the next receipt ID for this year (MAX(id) + 1)
    $query_max_id = 'SELECT COALESCE(MAX(id), 0) + 1 as next_id FROM kl_shehrullah_collection_record WHERE year = ?';
    $result_max = run_statement($query_max_id, $year);
    
    if (!$result_max->success || !isset($result_max->data[0]->next_id)) {
        return -1;
    }
    
    $next_id = $result_max->data[0]->next_id;
    
    // Insert the receipt with the calculated ID
    // Note: paid_amount is now calculated dynamically from receipts, so no UPDATE needed
    $query = 'INSERT INTO kl_shehrullah_collection_record (id, year, hof_id, amount, payment_mode, transaction_ref, remarks, created)
    VALUES (?,?,?,?,?,?,?,now());';

    $result = run_statement($query, $next_id, $year, $hof_id, $amount, $payment_mode, $transaction_ref, $remarks);

    if ($result->success && $result->count > 0) {
        return $next_id;
    }
    return -1;
}


function get_collection_record($id, $year) {
    $query = 'select cr.id,cr.hof_id, cr.amount,cr.payment_mode, cr.transaction_ref, cr.created, cr.remarks,t.takhmeen,
    m.full_name
    FROM kl_shehrullah_collection_record cr 
    JOIN kl_shehrullah_takhmeen t ON t.hof_id = cr.hof_id AND t.year = cr.year
    JOIN its_data m  ON t.hof_id = m.its_id
    where cr.id = ? and cr.year=?';

    $result = run_statement($query, $id, $year);

    if ($result->success && $result->count > 0) {
        $record = $result->data[0];
        // Calculate paid_amount from receipts
        $record->paid_amount = get_paid_amount_from_receipts($year, $record->hof_id);
        return $record;
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
    WHERE m.hof_id = ?';
	
	//Removed this condition to avoid Seems other mohalla error.
    //and (m.mohallah = "Kalimi" OR m.shehrullah="Y") 

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
    if ($result->success && $result->count > 0) {
        $takhmeen = $result->data[0];
        // Calculate paid_amount from receipts instead of using stored value
        $takhmeen->paid_amount = get_paid_amount_from_receipts($hijri_year, $hof_id);
        return $takhmeen;
    }
    return null;
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
    sum(takhmeen) takhmeen
    FROM (
    SELECT a.hof_id, i.full_name,
    count(CASE WHEN a.attendance_type = "Y" and i.gender="Male" and i.age > 11 THEN 1 ELSE NULL END) as male,
    count(CASE WHEN a.attendance_type = "Y" and i.gender="Female" and i.age > 11 THEN 1 ELSE NULL END) as female,
    count(CASE WHEN a.attendance_type = "Y" and i.age < 12 and i.age > 4 THEN 1 ELSE NULL END) as kids,
    count(CASE WHEN a.attendance_type = "Y" and i.age < 5 THEN 1 ELSE NULL END) as infant,
    count(CASE WHEN a.attendance_type = "Y" THEN 1 ELSE NULL END) as attendees, 
    count(CASE WHEN a.chair_preference = "Y" THEN 1 ELSE NULL END) as chairs,
    t.pirsa_count,t.takhmeen, t.zabihat_count
    FROM kl_shehrullah_attendees a
    JOIN its_data i ON i.its_id = a.its_id
    JOIN kl_shehrullah_takhmeen t ON t.hof_id = a.hof_id
    WHERE t.year=? and a.year=? and t.takhmeen >= 0
    GROUP BY a.hof_id    
    ) a;';

    $result = run_statement($query, $hijri, $hijri);
    if ($result->success && $result->count > 0) {
        $summary = $result->data[0];
        // Calculate total paid from receipts (for all families in this year)
        $summary->paid = get_paid_amount_from_receipts($hijri);
        return $summary;
    }
    return null;
}

function get_registration_data($hijri)
{
    $query = 'SELECT a.hof_id, hof.full_name,
    count(CASE WHEN a.attendance_type = "Y" and i.gender="Male" and i.age > 11 THEN 1 ELSE NULL END) as male,
    count(CASE WHEN a.attendance_type = "Y" and i.gender="Female" and i.age > 11 THEN 1 ELSE NULL END) as female,
    count(CASE WHEN a.attendance_type = "Y" and i.age < 12 and i.age > 4 THEN 1 ELSE NULL END) as kids,
    count(CASE WHEN a.attendance_type = "Y" and i.age < 5 THEN 1 ELSE NULL END) as infant,
    count(CASE WHEN a.attendance_type = "Y" THEN 1 ELSE NULL END) as attendees, 
    count(CASE WHEN a.chair_preference = "Y" THEN 1 ELSE NULL END) as chairs
    ,t.pirsa_count,t.takhmeen, t.whatsapp, t.zabihat_count
    FROM kl_shehrullah_attendees a
    JOIN its_data i ON i.its_id = a.its_id
    JOIN kl_shehrullah_takhmeen t ON t.hof_id = a.hof_id
    JOIN its_data hof ON hof.its_id = a.hof_id
    WHERE t.year=? and a.year=? and t.takhmeen >= 0 GROUP BY a.hof_id;';    

    $result = run_statement($query, $hijri, $hijri);
    if ($result->success && $result->count > 0) {
        // Calculate paid_amount for each record from receipts
        foreach ($result->data as &$record) {
            $record->paid_amount = get_paid_amount_from_receipts($hijri, $record->hof_id);
        }
        unset($record); // Break the reference
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

//--------------------------------------------
// SEAT SELECTION FUNCTIONS
//--------------------------------------------

/**
 * Check if seat selection is globally open (controlled by SUPER_ADMIN)
 */
function is_seat_selection_open() {
    $hijri_year = get_current_hijri_year();
    $query = 'SELECT seat_selection_open FROM kl_shehrullah_config WHERE year = ?';
    $result = run_statement($query, $hijri_year);
    if ($result->success && $result->count > 0) {
        return $result->data[0]->seat_selection_open == 'Y';
    }
    return false;
}

/**
 * Toggle seat selection open/close (SUPER_ADMIN only)
 */
function toggle_seat_selection($open) {
    $hijri_year = get_current_hijri_year();
    $value = $open ? 'Y' : 'N';
    $query = 'UPDATE kl_shehrullah_config SET seat_selection_open = ? WHERE year = ?';
    $result = run_statement($query, $value, $hijri_year);
    return $result->success;
}

/**
 * Check if a family can select seats (selection open + full payment or exception)
 */
function can_select_seats($hof_id) {
    // TEMPORARILY DISABLED: Bypass takhmeen and payment checks
    return true;
    
    // First check if seat selection is globally open
    // if (!is_seat_selection_open()) {
    //     return false;
    // }
    // 
    // $hijri_year = get_current_hijri_year();
    // $takhmeen = get_shehrullah_takhmeen_for($hof_id, $hijri_year);
    // 
    // if (is_null($takhmeen) || $takhmeen->takhmeen <= 0) {
    //     return false;
    // }
    // 
    // // Check if full payment done
    // if ($takhmeen->paid_amount >= $takhmeen->takhmeen) {
    //     return true;
    // }
    // 
    // // Check if SUPER_ADMIN has granted an exception
    // return has_seat_exception($hof_id, $hijri_year);
}

/**
 * Check if HOF has a payment exception for seat selection
 */
function has_seat_exception($hof_id, $hijri_year) {
    $query = 'SELECT id FROM kl_shehrullah_seat_exceptions 
              WHERE hof_id = ? AND hijri_year = ? AND is_active = "Y"';
    $result = run_statement($query, $hof_id, $hijri_year);
    return $result->success && $result->count > 0;
}

/**
 * Grant payment exception for seat selection (SUPER_ADMIN only)
 */
function grant_seat_exception($hof_id, $reason, $granted_by, $hoob_clearance_date = null) {
    $hijri_year = get_current_hijri_year();
    $query = 'INSERT INTO kl_shehrullah_seat_exceptions 
              (hof_id, hijri_year, reason, hoob_clearance_date, granted_by, granted_at, is_active)
              VALUES (?, ?, ?, ?, ?, NOW(), "Y")
              ON DUPLICATE KEY UPDATE 
              reason = ?, hoob_clearance_date = ?, granted_by = ?, granted_at = NOW(), 
              is_active = "Y", revoked_at = NULL';
    $result = run_statement($query, $hof_id, $hijri_year, $reason, $hoob_clearance_date, $granted_by, $reason, $hoob_clearance_date, $granted_by);
    return $result->success;
}

/**
 * Revoke payment exception
 */
function revoke_seat_exception($hof_id) {
    // Check if family has any allocated seats
    $allocations = get_seat_allocations_for_family($hof_id);
    if (!empty($allocations)) {
        return false; // Cannot revoke if seats are allocated
    }
    
    $hijri_year = get_current_hijri_year();
    $query = 'UPDATE kl_shehrullah_seat_exceptions 
              SET is_active = "N", revoked_at = NOW() 
              WHERE hof_id = ? AND hijri_year = ?';
    $result = run_statement($query, $hof_id, $hijri_year);
    return $result->success;
}

/**
 * Get all active seat exceptions
 */
function get_all_seat_exceptions() {
    $hijri_year = get_current_hijri_year();
    $query = 'SELECT e.*, m.full_name, t.takhmeen, gb.name as granted_by_name
              FROM kl_shehrullah_seat_exceptions e
              LEFT JOIN its_data m ON m.its_id = e.hof_id
              LEFT JOIN kl_shehrullah_takhmeen t ON t.hof_id = e.hof_id AND t.year = e.hijri_year
              LEFT JOIN kl_shehrullah_roles gb ON gb.itsid = e.granted_by
              WHERE e.hijri_year = ? AND e.is_active = "Y"
              ORDER BY e.hoob_clearance_date ASC, e.granted_at DESC';
    $result = run_statement($query, $hijri_year);
    if ($result->success && $result->count > 0) {
        // Calculate paid_amount for each record from receipts
        foreach ($result->data as &$record) {
            $record->paid_amount = get_paid_amount_from_receipts($hijri_year, $record->hof_id);
        }
        unset($record); // Break the reference
        return $result->data;
    }
    return [];
}

/**
 * Get all seating areas for current year
 * @param bool $active_only If true, only returns active areas (default for user-facing pages)
 */
function get_seating_areas($active_only = true) {
    $hijri_year = get_current_hijri_year();
    if ($active_only) {
        $query = 'SELECT * FROM kl_shehrullah_seating_areas 
                  WHERE hijri_year = ? AND is_active = "Y" ORDER BY id';
    } else {
        $query = 'SELECT * FROM kl_shehrullah_seating_areas 
                  WHERE hijri_year = ? ORDER BY id';
    }
    $result = run_statement($query, $hijri_year);
    return $result->success && $result->count > 0 ? $result->data : [];
}

/**
 * Get a specific seating area
 * @param bool $active_only If true, only returns if area is active (default for user-facing pages)
 */
function get_seating_area($area_code, $active_only = true) {
    $hijri_year = get_current_hijri_year();
    if ($active_only) {
        $query = 'SELECT * FROM kl_shehrullah_seating_areas 
                  WHERE area_code = ? AND hijri_year = ? AND is_active = "Y"';
    } else {
        $query = 'SELECT * FROM kl_shehrullah_seating_areas 
                  WHERE area_code = ? AND hijri_year = ?';
    }
    $result = run_statement($query, $area_code, $hijri_year);
    return $result->success && $result->count > 0 ? $result->data[0] : null;
}

/**
 * Get attendees eligible for seat selection (Misaq Done, attending)
 */
function get_attendees_for_seat_selection($hof_id) {
    $hijri_year = get_current_hijri_year();
    $query = 'SELECT sa.its_id, sa.chair_preference, m.full_name, m.age, m.gender, m.misaq,
              alloc.area_code as allocated_area, alloc.seat_number, alloc.allocated_by,
              areas.area_name as allocated_area_name, ab.full_name as allocated_by_name
              FROM kl_shehrullah_attendees sa
              JOIN its_data m ON m.its_id = sa.its_id
              LEFT JOIN kl_shehrullah_seat_allocation alloc ON alloc.its_id = sa.its_id AND alloc.hijri_year = ? AND alloc.status = "reserved"
              LEFT JOIN kl_shehrullah_seating_areas areas ON areas.area_code = alloc.area_code AND areas.hijri_year = ?
              LEFT JOIN its_data ab ON ab.its_id = alloc.allocated_by
              WHERE sa.hof_id = ? AND sa.year = ? 
              AND sa.attendance_type = "Y" AND m.misaq = "Done"
              ORDER BY m.age DESC';
    $result = run_statement($query, $hijri_year, $hijri_year, $hof_id, $hijri_year);
    return $result->success && $result->count > 0 ? $result->data : [];
}

/**
 * Get all attendees for display (including those with Misaq not Done)
 */
function get_all_attendees_for_display($hof_id) {
    $hijri_year = get_current_hijri_year();
    $query = 'SELECT sa.its_id, sa.chair_preference, m.full_name, m.age, m.gender, m.misaq,
              alloc.area_code as allocated_area, alloc.seat_number, alloc.allocated_by,
              areas.area_name as allocated_area_name, ab.full_name as allocated_by_name
              FROM kl_shehrullah_attendees sa
              JOIN its_data m ON m.its_id = sa.its_id
              LEFT JOIN kl_shehrullah_seat_allocation alloc ON alloc.its_id = sa.its_id AND alloc.hijri_year = ? AND alloc.status = "reserved"
              LEFT JOIN kl_shehrullah_seating_areas areas ON areas.area_code = alloc.area_code AND areas.hijri_year = ?
              LEFT JOIN its_data ab ON ab.its_id = alloc.allocated_by
              WHERE sa.hof_id = ? AND sa.year = ? 
              AND sa.attendance_type = "Y"
              ORDER BY m.age DESC';
    $result = run_statement($query, $hijri_year, $hijri_year, $hof_id, $hijri_year);
    return $result->success && $result->count > 0 ? $result->data : [];
}

/**
 * Get eligible seating areas for an attendee based on rules
 */
function get_eligible_areas_for_attendee($its_id, $hof_id) {
    $hijri_year = get_current_hijri_year();
    
    // Get attendee info
    $query = 'SELECT m.*, sa.chair_preference 
              FROM its_data m 
              JOIN kl_shehrullah_attendees sa ON sa.its_id = m.its_id AND sa.year = ?
              WHERE m.its_id = ?';
    $result = run_statement($query, $hijri_year, $its_id);
    if (!$result->success || $result->count == 0) {
        return [];
    }
    $attendee = $result->data[0];
    
    // Only Misaq Done members can select seats
    if ($attendee->misaq != 'Done') {
        return [];
    }
    
    $areas = get_seating_areas();
    $eligible = [];
    
    foreach ($areas as $area) {
        // Gender check
        if ($area->gender != 'All' && $area->gender != $attendee->gender) {
            continue;
        }
        
        // Age check (for first floor ladies)
        if ($area->min_age > 0 && $attendee->age < $area->min_age) {
            continue;
        }
        
        // Max seats per family check
        if ($area->max_seats_per_family > 0) {
            $count = count_family_seats_in_area($hof_id, $area->area_code);
            
            if ($count >= $area->max_seats_per_family) {
                continue;
            }
        }
        
        // Chair preference check - if attendee needs chair, only show chair-allowed areas
        if ($attendee->chair_preference == 'Y' && $area->chairs_allowed != 'Y') {
            continue;
        }
        
        $eligible[] = $area;
    }
    
    return $eligible;
}

/**
 * Count how many family members have seats in a specific area
 */
function count_family_seats_in_area($hof_id, $area_code) {
    $hijri_year = get_current_hijri_year();
    $query = 'SELECT COUNT(*) as cnt FROM kl_shehrullah_seat_allocation 
              WHERE hof_id = ? AND area_code = ? AND hijri_year = ? AND status = "reserved"';
    $result = run_statement($query, $hof_id, $area_code, $hijri_year);
    return $result->success && $result->count > 0 ? intval($result->data[0]->cnt) : 0;
}


/**
 * Get seat allocation for a specific member
 */
function get_seat_allocation_for_member($its_id) {
    $hijri_year = get_current_hijri_year();
    $query = 'SELECT alloc.*, areas.area_name 
              FROM kl_shehrullah_seat_allocation alloc
              JOIN kl_shehrullah_seating_areas areas ON areas.area_code = alloc.area_code AND areas.hijri_year = alloc.hijri_year
              WHERE alloc.its_id = ? AND alloc.hijri_year = ? AND alloc.status = "reserved"';
    $result = run_statement($query, $its_id, $hijri_year);
    return $result->success && $result->count > 0 ? $result->data[0] : null;
}

/**
 * Get all seat allocations for a family
 */
function get_seat_allocations_for_family($hof_id) {
    $hijri_year = get_current_hijri_year();
    $query = 'SELECT alloc.*, areas.area_name, m.full_name, ab.name as allocated_by_name
              FROM kl_shehrullah_seat_allocation alloc
              JOIN kl_shehrullah_seating_areas areas ON areas.area_code = alloc.area_code AND areas.hijri_year = alloc.hijri_year
              LEFT JOIN its_data m ON m.its_id = alloc.its_id
              LEFT JOIN kl_shehrullah_roles ab ON ab.itsid = alloc.allocated_by
              WHERE alloc.hof_id = ? AND alloc.hijri_year = ? AND alloc.status = "reserved"
              ORDER BY alloc.allocated_at';
    $result = run_statement($query, $hof_id, $hijri_year);
    return $result->success && $result->count > 0 ? $result->data : [];
}

/**
 * Atomic seat allocation using single UPDATE query
 * Prevents race conditions using atomic UPDATE operations
 * 
 * @param string $its_id Member ITS ID
 * @param string $hof_id Head of Family ID
 * @param string $area_code Seating area code
 * @return int|false Seat number on success, false on failure
 */
function allocate_seat_atomic($its_id, $hof_id, $area_code) {
    $hijri_year = get_current_hijri_year();
    
    // Step 1: Pre-validation (fast path)
    $eligible_areas = get_eligible_areas_for_attendee($its_id, $hof_id);
    $is_pre_eligible = false;
    foreach ($eligible_areas as $area) {
        if ($area->area_code === $area_code) {
            $is_pre_eligible = true;
            break;
        }
    }
    if (!$is_pre_eligible) {
        return false;
    }
    
    // Step 2: Get area configuration
    $area_query = 'SELECT max_seats_per_family FROM kl_shehrullah_seating_areas 
                   WHERE area_code = ? AND hijri_year = ? AND is_active = "Y"';
    $area_result = run_statement($area_query, $area_code, $hijri_year);
    if (!$area_result->success || $area_result->count == 0) {
        return false;
    }
    $max_seats_per_family = intval($area_result->data[0]->max_seats_per_family);
    
    // Step 3: Atomic UPDATE with family limit check
    $conn = get_database_connection(1);
    if (!$conn) return false;
    
    try {
        if ($max_seats_per_family > 0) {
            // Limited seats per family - include family limit check
            $update_query = 'UPDATE kl_shehrullah_seat_allocation 
                            SET status = "reserved", its_id = ?, hof_id = ?, allocated_at = NOW() 
                            WHERE id = (
                                SELECT id FROM (
                                    SELECT s.id FROM kl_shehrullah_seat_allocation s
                                    WHERE s.area_code = ? AND s.hijri_year = ? AND s.status = "available"
                                    AND (
                                        SELECT COUNT(*) FROM kl_shehrullah_seat_allocation 
                                        WHERE hof_id = ? AND area_code = ? AND hijri_year = ? AND status = "reserved"
                                    ) < ?
                                    ORDER BY s.seat_number LIMIT 1
                                ) AS subquery
                            )';
            $stmt = $conn->prepare($update_query);
            $stmt->execute([
                $its_id, $hof_id,
                $area_code, $hijri_year,
                $hof_id, $area_code, $hijri_year, $max_seats_per_family
            ]);
        } else {
            // Unlimited seats per family - simpler query
            $update_query = 'UPDATE kl_shehrullah_seat_allocation 
                            SET status = "reserved", its_id = ?, hof_id = ?, allocated_at = NOW() 
                            WHERE id = (
                                SELECT id FROM (
                                    SELECT s.id FROM kl_shehrullah_seat_allocation s
                                    WHERE s.area_code = ? AND s.hijri_year = ? AND s.status = "available"
                                    ORDER BY s.seat_number LIMIT 1
                                ) AS subquery
                            )';
            $stmt = $conn->prepare($update_query);
            $stmt->execute([$its_id, $hof_id, $area_code, $hijri_year]);
        }
        
        // Step 4: Check if UPDATE succeeded
        if ($stmt->rowCount() == 0) {
            return false; // No seats available or family limit reached
        }
        
        // Get seat_number from updated row
        $seat_query = 'SELECT seat_number FROM kl_shehrullah_seat_allocation 
                      WHERE its_id = ? AND hof_id = ? AND area_code = ? AND hijri_year = ? AND status = "reserved" 
                      ORDER BY allocated_at DESC LIMIT 1';
        $seat_result = run_statement($seat_query, $its_id, $hof_id, $area_code, $hijri_year);
        
        if ($seat_result->success && $seat_result->count > 0) {
            return intval($seat_result->data[0]->seat_number);
        }
        
        return false;
    } catch (Exception $e) {
        return false;
    }
}


/**
 * SUPER_ADMIN pre-allocate seat (bypasses all rules)
 */
function admin_pre_allocate_seat($its_id, $hof_id, $area_code, $seat_number, $allocated_by) {
    $hijri_year = get_current_hijri_year();
    
    // Step 0: Free up any existing seat allocation for this member
    $existing_seat_query = 'UPDATE kl_shehrullah_seat_allocation 
                           SET status = "available", its_id = NULL, hof_id = NULL, allocated_by = NULL, allocated_at = NULL
                           WHERE its_id = ? AND hijri_year = ? AND status = "reserved"';
    run_statement($existing_seat_query, $its_id, $hijri_year);
    
    // Validation 1: Check gender compatibility
    $gender_query = 'SELECT m.gender, a.gender as area_gender, a.area_name
                     FROM its_data m, kl_shehrullah_seating_areas a
                     WHERE m.its_id = ? AND a.area_code = ? AND a.hijri_year = ?';
    $gender_result = run_statement($gender_query, $its_id, $area_code, $hijri_year);
    if (!$gender_result->success || $gender_result->count == 0) {
        return ['success' => false, 'error' => 'INVALID_AREA'];
    }
    
    $data = $gender_result->data[0];
    $member_gender = $data->gender;
    $area_gender = $data->area_gender;
    
    // Check if gender matches (area_gender 'All' allows both genders)
    if ($area_gender != 'All' && $area_gender != $member_gender) {
        return ['success' => false, 'error' => 'GENDER_MISMATCH', 
                'area_gender' => $area_gender, 'member_gender' => $member_gender];
    }
    
    if ($seat_number) {
        // Specific seat number provided - update that seat
        // Validation: Check if seat is already reserved by someone else
        $check_query = 'SELECT its_id FROM kl_shehrullah_seat_allocation 
                        WHERE area_code = ? AND seat_number = ? AND hijri_year = ? AND status = "reserved" AND its_id != ?';
        $check_result = run_statement($check_query, $area_code, $seat_number, $hijri_year, $its_id);
        if ($check_result->success && $check_result->count > 0) {
            return ['success' => false, 'error' => 'SEAT_TAKEN'];
        }
        
        // Ensure seat exists, then update it
        $check_seat_query = 'SELECT id FROM kl_shehrullah_seat_allocation 
                            WHERE area_code = ? AND seat_number = ? AND hijri_year = ?';
        $check_seat_result = run_statement($check_seat_query, $area_code, $seat_number, $hijri_year);
        
        if ($check_seat_result->success && $check_seat_result->count > 0) {
            // Seat exists, update it
            $query = 'UPDATE kl_shehrullah_seat_allocation 
                      SET status = "reserved", its_id = ?, hof_id = ?, allocated_by = ?, allocated_at = NOW()
                      WHERE area_code = ? AND seat_number = ? AND hijri_year = ?';
            $result = run_statement($query, $its_id, $hof_id, $allocated_by, $area_code, $seat_number, $hijri_year);
        } else {
            // Seat doesn't exist, insert it
            $query = 'INSERT INTO kl_shehrullah_seat_allocation 
                      (its_id, hof_id, area_code, seat_number, allocated_by, hijri_year, status, allocated_at)
                      VALUES (?, ?, ?, ?, ?, ?, "reserved", NOW())';
            $result = run_statement($query, $its_id, $hof_id, $area_code, $seat_number, $allocated_by, $hijri_year);
        }
    } else {
        // No specific seat number - use atomic allocation (same as user flow)
        $seat_number = allocate_seat_atomic($its_id, $hof_id, $area_code);
        if ($seat_number === false) {
            return ['success' => false, 'error' => 'NO_SEATS_AVAILABLE'];
        }
        
        // Update allocated_by field
        $update_query = 'UPDATE kl_shehrullah_seat_allocation 
                        SET allocated_by = ?
                        WHERE its_id = ? AND hof_id = ? AND area_code = ? AND hijri_year = ? AND seat_number = ?';
        $result = run_statement($update_query, $allocated_by, $its_id, $hof_id, $area_code, $hijri_year, $seat_number);
    }
    
    return $result->success ? ['success' => true] : ['success' => false, 'error' => 'DB_ERROR'];
}

/**
 * Check if member's seat was pre-allocated by SUPER_ADMIN
 */
function is_admin_allocated($its_id) {
    $hijri_year = get_current_hijri_year();
    $query = 'SELECT allocated_by FROM kl_shehrullah_seat_allocation 
              WHERE its_id = ? AND hijri_year = ? AND status = "reserved" AND allocated_by IS NOT NULL';
    $result = run_statement($query, $its_id, $hijri_year);
    return $result->success && $result->count > 0;
}

/**
 * Get all seat allocations (for admin)
 */
function get_all_seat_allocations() {
    $hijri_year = get_current_hijri_year();
    $query = 'SELECT alloc.*, areas.area_name, m.full_name, m.gender, m.age,
              hof.full_name as hof_name, ab.name as allocated_by_name
              FROM kl_shehrullah_seat_allocation alloc
              JOIN kl_shehrullah_seating_areas areas ON areas.area_code = alloc.area_code AND areas.hijri_year = alloc.hijri_year
              LEFT JOIN its_data m ON m.its_id = alloc.its_id
              LEFT JOIN its_data hof ON hof.its_id = alloc.hof_id
              LEFT JOIN kl_shehrullah_roles ab ON ab.itsid = alloc.allocated_by
              WHERE alloc.hijri_year = ? AND alloc.status = "reserved"
              ORDER BY areas.area_name, alloc.seat_number, alloc.allocated_at';
    $result = run_statement($query, $hijri_year);
    return $result->success && $result->count > 0 ? $result->data : [];
}

/**
 * Delete seat allocation (SUPER_ADMIN)
 * Sets seat back to available status instead of deleting
 */
function delete_seat_allocation($its_id) {
    $hijri_year = get_current_hijri_year();
    $query = 'UPDATE kl_shehrullah_seat_allocation 
              SET status = "available", its_id = NULL, hof_id = NULL, allocated_by = NULL, allocated_at = NULL
              WHERE its_id = ? AND hijri_year = ?';
    $result = run_statement($query, $its_id, $hijri_year);
    return $result->success;
}

/**
 * Block a seat (SUPER_ADMIN)
 */
function block_seat($area_code, $seat_number, $reason, $blocked_by) {
    $hijri_year = get_current_hijri_year();
    
    // First ensure the seat exists in the table
    $check_query = 'SELECT id FROM kl_shehrullah_seat_allocation 
                    WHERE area_code = ? AND seat_number = ? AND hijri_year = ?';
    $check_result = run_statement($check_query, $area_code, $seat_number, $hijri_year);
    
    if (!$check_result->success || $check_result->count == 0) {
        // Seat doesn't exist, insert it as blocked
        $insert_query = 'INSERT INTO kl_shehrullah_seat_allocation 
                        (area_code, seat_number, hijri_year, status, blocked_reason, blocked_by, blocked_at, its_id, hof_id)
                        VALUES (?, ?, ?, "blocked", ?, ?, NOW(), NULL, NULL)';
        $result = run_statement($insert_query, $area_code, $seat_number, $hijri_year, $reason, $blocked_by);
    } else {
        // Seat exists, update it to blocked
        $query = 'UPDATE kl_shehrullah_seat_allocation 
                  SET status = "blocked", blocked_reason = ?, blocked_by = ?, blocked_at = NOW(), its_id = NULL, hof_id = NULL
                  WHERE area_code = ? AND seat_number = ? AND hijri_year = ?';
        $result = run_statement($query, $reason, $blocked_by, $area_code, $seat_number, $hijri_year);
    }
    
    return $result->success;
}

/**
 * Unblock a seat
 */
function unblock_seat($area_code, $seat_number) {
    $hijri_year = get_current_hijri_year();
    $query = 'UPDATE kl_shehrullah_seat_allocation 
              SET status = "available", blocked_reason = NULL, blocked_by = NULL, blocked_at = NULL
              WHERE area_code = ? AND seat_number = ? AND hijri_year = ?';
    $result = run_statement($query, $area_code, $seat_number, $hijri_year);
    return $result->success;
}

/**
 * Get blocked seats for an area
 */
function get_blocked_seats($area_code) {
    $hijri_year = get_current_hijri_year();
    $query = 'SELECT area_code, seat_number, hijri_year, blocked_reason, blocked_by, blocked_at 
              FROM kl_shehrullah_seat_allocation 
              WHERE area_code = ? AND hijri_year = ? AND status = "blocked" 
              ORDER BY seat_number';
    $result = run_statement($query, $area_code, $hijri_year);
    return $result->success && $result->count > 0 ? $result->data : [];
}

/**
 * Check if a specific seat is blocked
 */
function is_seat_blocked($area_code, $seat_number) {
    $hijri_year = get_current_hijri_year();
    $query = 'SELECT id FROM kl_shehrullah_seat_allocation 
              WHERE area_code = ? AND seat_number = ? AND hijri_year = ? AND status = "blocked"';
    $result = run_statement($query, $area_code, $seat_number, $hijri_year);
    return $result->success && $result->count > 0;
}

/**
 * Get available blocked seats (blocked but not yet allocated)
 */
function get_available_blocked_seats($area_code) {
    $hijri_year = get_current_hijri_year();
    $query = 'SELECT area_code, seat_number, hijri_year, blocked_reason, blocked_by, blocked_at 
              FROM kl_shehrullah_seat_allocation 
              WHERE area_code = ? AND hijri_year = ? AND status = "blocked" 
              ORDER BY seat_number';
    $result = run_statement($query, $area_code, $hijri_year);
    return $result->success && $result->count > 0 ? $result->data : [];
}

/**
 * Validate if any reserved seats exist outside the seat range
 * 
 * @param string $area_code Area code
 * @param int $hijri_year Hijri year
 * @param int $seat_start Seat range start
 * @param int $seat_end Seat range end
 * @return array ['valid' => bool, 'conflicting_seats' => array, 'message' => string]
 */
function validate_reserved_seats_in_range($area_code, $hijri_year, $seat_start, $seat_end) {
    $query = 'SELECT seat_number FROM kl_shehrullah_seat_allocation 
              WHERE area_code = ? AND hijri_year = ? AND status = "reserved" 
              AND (seat_number < ? OR seat_number > ?)
              ORDER BY seat_number';
    $result = run_statement($query, $area_code, $hijri_year, $seat_start, $seat_end);
    
    if ($result->success && $result->count > 0) {
        $conflicting_seats = array_map(function($row) {
            return intval($row->seat_number);
        }, $result->data);
        
        $seat_list = implode(', ', $conflicting_seats);
        return [
            'valid' => false,
            'conflicting_seats' => $conflicting_seats,
            'message' => "Cannot sync seats. Reserved seats [{$seat_list}] are outside the configured range ({$seat_start}-{$seat_end})."
        ];
    }
    
    return ['valid' => true, 'conflicting_seats' => [], 'message' => ''];
}

/**
 * Sync seats in kl_shehrullah_seat_allocation to match seat range in kl_shehrullah_seating_areas
 * 
 * - Validates that no reserved seats exist outside the range (errors if found)
 * - Deletes all seats (blocked + available) outside the range
 * - For seats inside range: preserves reserved seats, makes all others available
 * - Blocked seats inside range are converted to available (will be re-applied by sync_blocked_seats_for_area)
 * 
 * @param string $area_code Area code
 * @param int|null $hijri_year Hijri year (if null, uses current year)
 * @return array ['success' => bool, 'message' => string]
 */
function sync_seats_for_area($area_code, $hijri_year = null) {
    if (empty($area_code)) {
        return ['success' => false, 'message' => 'Area code is required'];
    }
    
    $hijri_year = $hijri_year ?? get_current_hijri_year();
    
    // Get area configuration from kl_shehrullah_seating_areas
    $area = get_seating_area($area_code, false);
    if (!$area || $area->seat_start === null || $area->seat_end === null) {
        return ['success' => false, 'message' => 'Area not found or seat range not configured'];
    }
    
    $seat_start = intval($area->seat_start);
    $seat_end = intval($area->seat_end);
    
    $conn = get_database_connection(1);
    if (!$conn) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    try {
        $conn->beginTransaction();
        
        // Step 1: Validate that no reserved seats exist outside the range
        $validation = validate_reserved_seats_in_range($area_code, $hijri_year, $seat_start, $seat_end);
        if (!$validation['valid']) {
            $conn->rollBack();
            return ['success' => false, 'message' => $validation['message']];
        }
        
        // Step 2: Delete all seats (blocked + available) outside the range
        $delete_stmt = $conn->prepare(
            'DELETE FROM kl_shehrullah_seat_allocation 
             WHERE area_code = ? AND hijri_year = ? 
             AND (seat_number < ? OR seat_number > ?)'
        );
        $delete_stmt->execute([$area_code, $hijri_year, $seat_start, $seat_end]);
        
        // Step 3: For seats inside range: preserve reserved, make everything else available
        // Insert missing seats as available, or update existing non-reserved seats to available
        // Note: Blocked seats are handled separately by sync_blocked_seats_for_area(), so we always clear blocked fields here
        // Note: In ON DUPLICATE KEY UPDATE, 'status' refers to the existing (old) value
        $sync_stmt = $conn->prepare(
            'INSERT INTO kl_shehrullah_seat_allocation 
             (area_code, seat_number, hijri_year, status, its_id, hof_id, blocked_reason, blocked_by, blocked_at) 
             VALUES (?, ?, ?, "available", NULL, NULL, NULL, NULL, NULL)
             ON DUPLICATE KEY UPDATE 
             status = CASE WHEN status = "reserved" THEN "reserved" ELSE "available" END,
             its_id = CASE WHEN status = "reserved" THEN its_id ELSE NULL END,
             hof_id = CASE WHEN status = "reserved" THEN hof_id ELSE NULL END,
             blocked_reason = NULL,
             blocked_by = NULL,
             blocked_at = NULL'
        );
        
        for ($seat_num = $seat_start; $seat_num <= $seat_end; $seat_num++) {
            $sync_stmt->execute([$area_code, $seat_num, $hijri_year]);
        }
        
        $conn->commit();
        return ['success' => true, 'message' => 'Seats synced successfully'];
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        return ['success' => false, 'message' => 'Failed to sync seats: ' . $e->getMessage()];
    }
}

/**
 * Sync blocked seats for an area (efficiently handles add/remove in one transaction)
 * 
 * @param string $area_code Area code
 * @param array $blocked_seats Array of ['seat_number' => int, 'reason' => string]
 * @param string $blocked_by User ID who is blocking
 * @param int|null $hijri_year Hijri year (if null, uses current year)
 * @return array ['success' => bool, 'message' => string]
 */
function sync_blocked_seats_for_area($area_code, $blocked_seats, $blocked_by, $hijri_year = null) {
    if (empty($area_code)) {
        return ['success' => false, 'message' => 'Area code is required'];
    }
    
    $hijri_year = $hijri_year ?? get_current_hijri_year();
    
    // Note: sync_seats_for_area() runs first and converts all blocked seats to available,
    // so we only need to block the desired seats (no need to check current blocked or unblock)
    
    if (empty($blocked_seats)) {
        // No seats to block - nothing to do (all seats are already available from sync_seats_for_area)
        return ['success' => true, 'message' => 'Blocked seats synced successfully'];
    }
    
    $conn = get_database_connection(1);
    if (!$conn) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    try {
        $conn->beginTransaction();
        
        // Block desired seats (only if not reserved, preserve reserved seats)
        $check_stmt = $conn->prepare(
            'SELECT status FROM kl_shehrullah_seat_allocation 
             WHERE area_code = ? AND seat_number = ? AND hijri_year = ?'
        );
        $block_stmt = $conn->prepare(
            'INSERT INTO kl_shehrullah_seat_allocation 
             (area_code, seat_number, hijri_year, status, blocked_reason, blocked_by, blocked_at, its_id, hof_id)
             VALUES (?, ?, ?, "blocked", ?, ?, NOW(), NULL, NULL)
             ON DUPLICATE KEY UPDATE 
             status = "blocked", blocked_reason = ?, blocked_by = ?, blocked_at = NOW(), its_id = NULL, hof_id = NULL'
        );
        
        foreach ($blocked_seats as $seat) {
            $seat_num = intval($seat['seat_number'] ?? $seat);
            $reason = $seat['reason'] ?? '';
            
            // Check if seat exists and is reserved
            $check_stmt->execute([$area_code, $seat_num, $hijri_year]);
            $existing = $check_stmt->fetch(PDO::FETCH_OBJ);
            
            // Skip if seat is reserved
            if ($existing && $existing->status === 'reserved') {
                continue;
            }
            
            // Block the seat (only affects available seats, never reserved)
            $block_stmt->execute([$area_code, $seat_num, $hijri_year, $reason, $blocked_by, $reason, $blocked_by]);
        }
        
        $conn->commit();
        return ['success' => true, 'message' => 'Blocked seats synced successfully'];
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        return ['success' => false, 'message' => 'Failed to sync blocked seats: ' . $e->getMessage()];
    }
}

/**
 * Update seating area configuration
 */
function update_seating_area($area_code, $area_name, $seat_start, $seat_end, $is_active, $max_seats_per_family = null) {
    if (empty($area_code)) {
        return false;
    }
    
    // Update area configuration in kl_shehrullah_seating_areas
    $result = run_statement(
        'UPDATE kl_shehrullah_seating_areas 
         SET area_name = ?, seat_start = ?, seat_end = ?, is_active = ?, max_seats_per_family = ?
         WHERE area_code = ? AND hijri_year = ?',
        $area_name, $seat_start, $seat_end, $is_active, $max_seats_per_family, 
        $area_code, get_current_hijri_year()
    );
    
    // Sync seats if update successful and seat range is configured
    // sync_seats_for_area() handles all validation and syncing
    if ($result->success && $seat_start !== null && $seat_end !== null) {
        sync_seats_for_area($area_code);
    }
    
    return $result->success;
}

// TODO: WhatsApp Integration Placeholder
// function send_seat_allocation_whatsapp($hof_id, $whatsapp_number) {
//     $hijri_year = get_current_hijri_year();
//     $allocations = get_seat_allocations_for_family($hof_id);
//     
//     $message = "Shehrullah $hijri_year - Seat Allocation Confirmation\n\n";
//     
//     foreach($allocations as $alloc) {
//         $message .= "{$alloc->full_name}: {$alloc->area_name}";
//         if($alloc->seat_number) $message .= " (Seat #{$alloc->seat_number})";
//         $message .= "\n";
//     }
//     
//     $message .= "\n📌 Seat selection is first come first serve basis.";
//     $message .= "\n📌 Please arrive early to secure your allocated seat.";
//     
//     // Integration with WhatsApp Business API
//     // Update whatsapp_sent and whatsapp_sent_at in kl_shehrullah_seat_allocation
// }

