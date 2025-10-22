<?php
/********************************* 
 * 
 */

/**
 * Configuration
 */
DEFINED('VIEW_DIR') or DEFINE('VIEW_DIR', 'views');
DEFINED('VIEW_TEMPLATE') or DEFINE('VIEW_TEMPLATE', 'template.php');

/* == APP SECURITY STUFF */
DEFINED('IS_APP_SECURE') or DEFINE('IS_APP_SECURE', false); // This should be set true if application has secure pages, else false
DEFINED('IS_RELATIVE_AUTH_REDIRECT') or DEFINE('IS_RELATIVE_AUTH_REDIRECT', false);
DEFINED('AUTH_REDIRECT') or DEFINE('AUTH_REDIRECT', 'https://www.its52.com/Login.aspx?OneLogin='); //If application is secure and not autheticated.. define the redirect page 
DEFINED('OPEN_PAGE_LIST') or DEFINE('OPEN_PAGE_LIST', ['data_entry']); //List of pages that bypasses authentication.
DEFINED('SECURE_LANDING_PAGE') or DEFINE('SECURE_LANDING_PAGE', 'data_entry');

/* == SESSION STUFF */
DEFINED('DB_BASED_SESSION') or DEFINE('DB_BASED_SESSION', false);
DEFINED('SESSION_TABLE_NAME') or DEFINE('SESSION_TABLE_NAME', 'kq_php_sessions');
DEFINED('THE_SESSION_ID') or DEFINE('THE_SESSION_ID', 'Khatm_alQuran_App_786110');
DEFINED('TRANSIT_DATA') or DEFINE('TRANSIT_DATA', 'transit_data');

DEFINED('TIME_ZONE') or DEFINE('TIME_ZONE', 'Asia/Kolkata');


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Start it.
 */
init_session();
get_set_and_go();

/**
 * Bootstrap Logic
 * 
 */
function get_set_and_go()
{
    if (function_exists('application_custom_configuration')) {
        application_custom_configuration();
    }

    date_default_timezone_set(TIME_ZONE);

    process_the_request(CURRENT_DIR);

    $page = getPageName();
    if (IS_APP_SECURE) {
        if (!in_array($page, OPEN_PAGE_LIST) && !is_authenticated()) {
            do_redirect(AUTH_REDIRECT, IS_RELATIVE_AUTH_REDIRECT);
        }
    }

    $dir = VIEW_DIR;
    $filePath = "./$dir/$page.php";
    if (file_exists($filePath)) {
        try {
            include_once $filePath;
            if (!DEFINED('NO_TEMPLATE')) {
                $template = DEFINED('SPECIAL_TEMPLATE') ? SPECIAL_TEMPLATE : VIEW_TEMPLATE;
                include_once $template;
            }
        } catch (\Throwable $th) {
            echo 'Cought the error';
            var_dump($th);
        }
    } else {
        $show = false;
        $base_uri = getAppData('BASE_URI');
        echo 'Ops! Page Not Found.<br/><a href="' . $base_uri . '">Go Back</a>';
    }
}

function getPageName()
{
    $page = getAppData('arg0');
    if (!isset($page) || strlen($page) == 0) {
        $page = SECURE_LANDING_PAGE;
    }

    $shouldEndWith = '.php';
    $endsWith = substr($page, -strlen($shouldEndWith)) == $shouldEndWith;
    if ($endsWith) {
        $page = substr($page, 0, strlen($page) - 4);
    }

    if (strpos($page, '.') !== false) {
        $page = str_replace('.', '/', $page);
    }

    // if (str_contains($page, '.')) {
    //     $page = str_replace('.', '/', $page);
    // }

    //return strtolower($page);
    return $page;
}


/**
 * Database Function
 */

function get_database_connection($count = 1)
{
    $conn = null;
    if ($count < 10) {

        try {
            $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // echo "Connected successfully";
        } catch (PDOException $e) {
            // echo "Connection failed: " . $e->getMessage();
            usleep(pow(2, $count) * 10000);
            return get_database_connection($count + 1);
        }
    }
    return $conn;
}

function bind_query_values($statement, $value, $counter = 1)
{
    if (is_array($value)) {
        foreach ($value as $val) {
            bind_query_values($statement, $val, $counter++);
        }
    } else {
        $statement->bindValue($counter, $value, PDO::PARAM_STR);
    }
}

function create_object()
{
    return json_decode('{}');
}

function run_statement($query, ...$args)
{
    $resultData = create_object();
    $resultData->success = false;
    $resultData->message = 'Invalid request';
    $resultData->count = 0;
    $resultData->data = array();
    $conn = get_database_connection(1);

    if (!isset($conn)) {
        $resultData->message = 'No connection found.';
        return $resultData;
    }

    $stmt = null;
    // Execute and Collect the result
    try {
        // $conn = Flight::db(false);
        // $conn = Flight::getDBConn();
        // Generate the statement for the given query.
        $stmt = $conn->prepare($query);

        // $counter = 1;
        // Flight::bindVal($stmt, $counter, $args);
        bind_query_values($stmt, $args);

        $stmt->execute();
        $numRows = $stmt->rowCount();
        $colCount = $stmt->columnCount();

        $resultData->insertedID = $conn->lastInsertId() ?? -1;
        $resultData->success = true;
        $resultData->message = 'Success';
        $resultData->count = $numRows;
        $resultData->data = array();

        // This is select query..
        if ($colCount > 0 && $numRows > 0) {
            $allRowData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $resultData->data = json_decode(json_encode($allRowData));
        }
    } catch (PDOException $e) {
        $resultData->message = $e->getMessage();
        $resultData->success = false;
        $resultData->count = 0;
        // $resultData->data = array(
        //     'data' => $e->getMessage()
        // );
        if ($e->errorInfo[1] == 1062) {
            //The INSERT query failed due to a key constraint violation.
            $resultData->message = 'Same value is used before.';
        }
    } catch (Exception $e2) {
        $resultData->message = $e2->getMessage();
        $resultData->success = false;
        $resultData->count = 0;
        // $resultData->data = array(
        //     'data' => []
        // );
    } finally {
        $stmt = null;
        $conn = null;
    }

    return $resultData;
}

function execute_query($sql, $update = false, $multi = false)
{
    global $link;
    $resp = json_decode('{}');
    $resp->data = [];
    $resp->success = false;
    $resp->count = 0;

    try {
        //code...

        $result = $multi ? mysqli_multi_query($link, $sql) : mysqli_query($link, $sql);

        if( is_bool($result) ) {
            $resp->count = mysqli_affected_rows($link);
        } else {
            if (($count = $result->num_rows) > 0) {
                $resp->data = $result->fetch_all(MYSQLI_ASSOC);
                $resp->count = $count;
            }
            // Free result set
            $result->free_result();
        }
        
        $resp->success = true;

    } catch (exception $th) {
        $resp->error = mysqli_error($link);
    }

    return $resp;
}

function fetch_data($sql)
{
    return execute_query($sql, false, false);
}

function change_data($sql)
{
    return execute_query($sql, true, false);
}

function change_multi_data($sql)
{
    return execute_query($sql, true, true);
}

function is_record_found($result)
{
    return isset($result) && $result->success && $result->count > 0 ? true : false;
}


/**
 * 
 * Request handle
 */


function process_the_request($current_directory)
{

    setAppData('current_dir', $current_directory);

    //Form the website URL. Ex: https://khatmalquran.com
    $siteURL = getSiteURL();
    setAppData('site_url', $siteURL);

    //Parse the request URI ( separate out the URI and Query params )
    $urls = parse_url($_SERVER['REQUEST_URI']);

    $path = $urls['path'] ?? '';
    $query = $urls['query'] ?? null;

    //Calculate the position for substring
    $pos = stripos($path, $current_directory) + strlen($current_directory);

    $base_location = substr($path, 0, $pos);
    setAppData('base_location', $base_location);


    $base_uri = $siteURL . $base_location;
    setAppData('BASE_URI', $base_uri);

    $uri_segments = substr($path, $pos);

    $args = getURISegments($uri_segments);

    $index = 0;
    $relative_depth = '';
    foreach ($args as $arg) {
        $name = "arg$index";
        setAppData($name, $arg);
        if ($index > 0) {
            $relative_depth .= '../';
        }
        $index++;
    }

    setAppData('relative_depth', $relative_depth);

    if (isset($query)) {
        parse_str($query, $queryArray);
        foreach ($queryArray as $key => $val) {
            $name = "q_$key";
            setAppData($name, $val);
            $index++;
        }
    }
}

function auto_post_redirect($page, $data)
{
    DEFINED('NO_TEMPLATE') or DEFINE('NO_TEMPLATE', true);

    echo "<form id='myForm' action='$page' method='POST'>";
    foreach ($data as $key => $value) {
        echo '<input type="hidden" name="' . htmlentities($key) . '" value="' . htmlentities($value) . '">';
    }
    //If page does not edirects automatically. click Sumit. <input type="submit">
    echo '</form><script type="text/javascript">document.getElementById("myForm").submit();</script>';
    exit();
}


function getSiteURL()
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domainName = $_SERVER['HTTP_HOST'];
    return $protocol . $domainName;
}

function getURISegments($uri_segments): array
{
    //Separate out the URI elements.
    $args = explode('/', $uri_segments);
    //array_filter : removes the blank array elements
    return array_filter($args, 'strlen');
}

function is_post()
{
    $method = strtolower($_SERVER['REQUEST_METHOD']);
    return 'post' === $method ? true : false;
}

function do_redirect($page, $relativePath = true)
{
    if ($relativePath) {
        $baseUri = getAppData('BASE_URI') ?? '';
        $page = $baseUri . $page;
    }
    header("Location: $page");
    exit();
}

function do_redirect_with_message($page, $message, $relativePath = true)
{
    setSessionData("transit_data", $message);
    do_redirect($page, $relativePath);
}

function if_not_post_redirect($page, $relativePath = true)
{
    if (!is_post()) {
        do_redirect($page, $relativePath);
    }
}

function do_for_post($callback)
{
    if (is_post()) {
        $callback();
    }
}

/**
 * POJO
 */
/**
 * This file is used to maintain a function to store the data for a given session.
 * Has set and get method to push and pull the data.
 */

$application_data = [];

function setAppData($key, $value)
{
    global $application_data;

    $application_data[$key] = $value;
}

function getAppData($key)
{
    global $application_data;

    return isset($application_data[$key]) ? $application_data[$key] : null;
}

/**
 * Session Handler
 */
function setSessionData($key, $value)
{
    $_SESSION[$key] = serialize($value);
}

function getSessionData($name)
{
    if (isset($_SESSION[$name]))
        return unserialize($_SESSION[$name]);
}

function removeSessionData($key)
{
    if (isset($_SESSION[$key]))
        unset($_SESSION[$key]);
}

function destroySession()
{
    session_destroy();
}

function is_authenticated()
{
    $udata = getSessionData(THE_SESSION_ID);

    if (isset($udata)) {
        return true;
    }

    return false;
}


function init_session()
{
    //DB_BASED_SESSION
    $res = true;
    if (session_status() == PHP_SESSION_NONE) {

        // session_cache_limiter('');
        // session_name(THE_SESSION_ID);

        if (DB_BASED_SESSION) {
            $res = session_set_save_handler("_open", "_close", "_read", "_write", "_destroy", "_gc");
        }
        // Start the session
        session_start();
    }

    return $res;
}

function _open()
{
    return true;
}

function _close()
{
    return true;
}

function _read($id)
{
    $resultSet = run_statement('SELECT data FROM ' . SESSION_TABLE_NAME . ' WHERE id = ?', $id);
    if ($resultSet->count > 0) {
        $row = $resultSet->data[0];
        return $row->data;
    }
    return '';
}

function _write($id, $data)
{
    $access = time();
    $result = run_statement('REPLACE INTO ' . SESSION_TABLE_NAME . ' VALUES (?,?,?,now());', $id, $access, $data);
    if ($result->success) {
        return true;
    }
    return false;
}

function _destroy($id)
{
    $result = run_statement('DELETE FROM ' . SESSION_TABLE_NAME . ' WHERE id = ?;', $id);
    if ($result->success) {
        return true;
    }
    return false;
}

function _gc($max)
{
    // Calculate what is to be deemed old
    $old = time() - $max;
    $result = run_statement('DELETE FROM ' . SESSION_TABLE_NAME . ' WHERE access < ?;', $old);
    if ($result->success) {
        return true;
    }
    return false;
}