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

function get_thaalilist_data($sabeel_hof)
{
    $query = 'SELECT Thali, NAME, CONTACT, sabeelType, ITS_No, Email_ID,Full_Address,WhatsApp 
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


function get_attendees_data_for($hof_id, $hijri_year, $attends = false)
{
    $query = 'SELECT 
    case when sa.masalla is null then ? else sa.masalla end as masalla, 
    case when sa.attendance_type is null then ? else sa.attendance_type end as attendance_type,
    case when sa.chair_preference is null then ? else sa.chair_preference end as chair_preference,
    m.its_id,m.full_name,m.age,m.gender
    FROM its_data m
    LEFT JOIN  kl_shehrullah_attendees sa ON m.its_id = sa.its_id and sa.year = ?
    WHERE m.hof_id = ? and m.mohallah = "Kalimi" ';

    $params = [];
    if ($attends) {
        $query .= ' and sa.attendance_type=?;';
        $result = run_statement($query, '', 'Yes', 'No', $hijri_year, $hof_id, 'Y');
    } else {
        $result = run_statement($query, '', 'Yes', 'No', $hijri_year, $hof_id);
    }

    if ($result->count > 0) {
        return $result->data;
    }
    return null;
}



function add_shehrullah_takhmeen(
    $year,
    $hof_id,
    $family_hub,
    $pirsa_count,
    $chair_count,
    $parking_count,
    $venue,
    $whatsapp
) {
    //$year = HIJRI_YEAR;
    $query = 'INSERT INTO kl_shehrullah_takhmeen
    (hof_id, year, family_hub,pirsa_count,chair_count,parking_count,venue,whatsapp) 
    values(?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE 
    family_hub=?,pirsa_count=?,chair_count=?,
    parking_count=?,venue=?,whatsapp=?,updated=now();';

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
        $family_hub,
        $pirsa_count,
        $chair_count,
        $parking_count,
        $venue,
        $whatsapp
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

