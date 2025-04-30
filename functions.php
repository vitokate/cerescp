<?php
/*
Ceres Control Panel

This is a control pannel program for Athena and Freya
Copyright (C) 2005 by Beowulf and Nightroad
Modifications by Manus AI

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

To contact any of the authors about special permissions send
an e-mail to cerescp@gmail.com
*/

include_once __DIR__ . '/language/language.php';
include_once __DIR__ . '/classes.php';

// Use more robust session handling if possible (e.g., regenerate ID on login)
if (isset($_SESSION[$CONFIG_name.'SERVER'])) {
    if (strcmp($_SESSION[$CONFIG_name.'SERVER'], $CONFIG_name)) {
        session_destroy();
        die();
    }
} else {
    $_SESSION[$CONFIG_name.'SERVER'] = $CONFIG_name;
}

// Error reporting - adjust for production/development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Instantiate the updated QueryClass
$mysql = new QueryClass($CONFIG_rag_serv, $CONFIG_rag_user, $CONFIG_rag_pass, $CONFIG_rag_db, $CONFIG_cp_serv, $CONFIG_cp_user, $CONFIG_cp_pass, $CONFIG_cp_db);

// IP Ban Check - Consider refining this logic
if (!isset($_SESSION[$CONFIG_name.'ipban']) || (isset($_SESSION[$CONFIG_name.'iptime']) && (time() - $_SESSION[$CONFIG_name.'iptime']) > 300)) {
    include_once __DIR__ . '/ipban.php';
    $_SESSION[$CONFIG_name.'ipban'] = ipban();
    $_SESSION[$CONFIG_name.'iptime'] = time();
}

if ($_SESSION[$CONFIG_name.'ipban'])
    die("Denied");

// Preload data - Consider if this is always necessary or can be loaded on demand
if (!isset($_SESSION[$CONFIG_name.'jobs']))
    $_SESSION[$CONFIG_name.'jobs'] = readjobs();

if (!isset($_SESSION[$CONFIG_name.'castles']))
    $_SESSION[$CONFIG_name.'castles'] = readcastles();

// Ensure admin level is valid
if ($CONFIG['cp_admin'] < 10)
    $CONFIG['cp_admin'] = 100;

// --- File Reading Functions --- (Consider error handling and security)

function readcastles() {
    global $lang;
    $resp = [];
    $filePath = __DIR__ . '/db/castles.txt';
    if (!is_readable($filePath)) {
        error_log("Error reading castles file: " . $filePath);
        die(htmlformat(isset($lang['TXT_ERROR']) ? $lang['TXT_ERROR'] : 'File Read Error'));
    }
    $handle = fopen($filePath, "rt");
    if (!$handle) {
         error_log("Error opening castles file: " . $filePath);
         die(htmlformat(isset($lang['TXT_ERROR']) ? $lang['TXT_ERROR'] : 'File Read Error'));
    }
    while (($line = fgets($handle, 1024)) !== false) {
        $line = trim($line);
        if (empty($line) || strpos($line, '//') === 0) {
            continue;
        }
        // Use preg_split for more robust parsing
        $parts = preg_split('/\s+/', $line, 2);
        if (count($parts) === 2 && is_numeric($parts[0])) {
            $id = (int)$parts[0];
            $name = str_replace('_', ' ', $parts[1]);
            $resp[$id] = $name;
        }
    }
    fclose($handle);
    return $resp;
}

function readitems() {
    $resp = [0 => 'unknown']; // Default for ID 0
    $filePath = __DIR__ . '/db/item_db.txt';
     if (!is_readable($filePath)) {
        error_log("Error reading items file: " . $filePath);
        return $resp; // Return default on error
    }
    $handle = fopen($filePath, "rt");
     if (!$handle) {
         error_log("Error opening items file: " . $filePath);
         return $resp; // Return default on error
    }
    while (($line = fgets($handle, 1024)) !== false) {
         $line = trim($line);
        if (empty($line) || strpos($line, '//') === 0) {
            continue;
        }
        $item = explode(',', $line, 4);
        if (isset($item[0]) && isset($item[2]) && is_numeric($item[0])) {
            $resp[(int)$item[0]] = $item[2]; // Use item ID as key
        }
    }
    $resp[0] = " "; // Original behavior for ID 0
    fclose($handle);
    return $resp;
}

function readjobs() {
    global $lang;
    $resp = [0 => 'unknown'];
    $filePath = __DIR__ . '/db/jobs.txt';
     if (!is_readable($filePath)) {
        error_log("Error reading jobs file: " . $filePath);
        die(htmlformat(isset($lang['TXT_ERROR']) ? $lang['TXT_ERROR'] : 'File Read Error'));
    }
    $handle = fopen($filePath, "rt");
     if (!$handle) {
         error_log("Error opening jobs file: " . $filePath);
         die(htmlformat(isset($lang['TXT_ERROR']) ? $lang['TXT_ERROR'] : 'File Read Error'));
    }
    while (($line = fgets($handle, 1024)) !== false) {
        $line = trim($line);
         if (empty($line) || strpos($line, '//') === 0) {
            continue;
        }
        // Use preg_split for more robust parsing
        $parts = preg_split('/\s+/', $line, 2);
         if (count($parts) === 2 && is_numeric($parts[1])) {
            $id = (int)$parts[1];
            $name = str_replace('_', ' ', $parts[0]);
            $resp[$id] = $name;
        }
    }
    fclose($handle);
    return $resp;
}

// --- Formatting and Validation Functions --- 

// Encodes string to HTML entities. Consider using htmlspecialchars directly.
function htmlformat($string) {
    // Using htmlspecialchars is safer and standard
    return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    /* Original implementation:
    $resp = "";
    for ($i = 0; isset($string[$i]) && ord($string[$i]) > 0; $i++)
        $resp .= "&#".ord($string[$i]).";";
    return $resp;
    */
}

// Formats number with commas. Consider using number_format.
function moneyformat($string) {
    $string = trim($string);
    if (!is_numeric($string)) {
        return $string; // Return original if not numeric
    }
    return number_format((float)$string);
    /* Original implementation:
    $return = "";
    $len = strlen($string) - 1;

    for ($i = 0; $i < strlen($string); $i++) {
        if ($i > 0 && $i % 3 == 0)
            $return = ",".$return;
        $return = $string[$len - $i].$return;
    }
    return $return;
    */
}

// Basic input validation. THIS IS NOT SUFFICIENT FOR SQL INJECTION PREVENTION.
// Use prepared statements instead.
function inject($string) {
    // This function provides minimal protection and should NOT be relied upon for security.
    // Prepared statements are the correct way to prevent SQL injection.
    // Keeping it for now as it might be used in non-SQL contexts, but it's weak.
    $permitido = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890.@$&-_/"; // Removed potentially problematic chars §*°ºª
    for ($i=0; $i<strlen($string); $i++) {
        if (strpos($permitido, substr($string, $i, 1)) === FALSE) return TRUE;
    }
    return FALSE;
}

// Checks if a string contains non-numeric characters.
function notnumber($string) {
    return !ctype_digit((string)$string);
    /* Original implementation:
    $permitido = "1234567890"; //numeros
    for ($i=0; $i<strlen($string); $i++) {
        if (strpos($permitido, substr($string, $i, 1)) === FALSE) return TRUE;
    }
    return FALSE;
    */
}

// Checks password against a dictionary and basic complexity rules.
// This is weak security. Consider stronger password policies.
function thepass($string) {
    global $lang;
    $string = trim($string);

    // Basic complexity check (at least 2 numbers, 2 non-numbers)
    $numero = 0;
    $letra = 0;
    for ($i = 0; isset($string[$i]); $i++) {
        if (ctype_digit($string[$i])) {
            $numero++;
        } elseif (ctype_alpha($string[$i])) { // Count letters specifically
             $letra++;
        }
    }
    if ($numero < 2 || $letra < 2) {
        return TRUE; // Fails complexity check
    }

    // Dictionary check
    $filePath = __DIR__ . '/db/passdict.txt';
    if (!is_readable($filePath)) {
         error_log("Error reading password dictionary: " . $filePath);
         // Decide whether to fail open (allow password) or closed (reject password)
         return FALSE; // Fail open for now, as original code did
    }
    $handle = fopen($filePath, "rt");
     if (!$handle) {
         error_log("Error opening password dictionary: " . $filePath);
         return FALSE; // Fail open
    }
    while (($line = fgets($handle, 1024)) !== false) {
        $line = trim($line);
        if (empty($line) || strpos($line, '//') === 0) {
            continue;
        }
        if (strcasecmp($string, $line) === 0) { // Case-insensitive comparison
            fclose($handle);
            return TRUE; // Password found in dictionary
        }
    }
    fclose($handle);

    return FALSE; // Password not in dictionary and passed complexity
}

// Validates date components. Consider using checkdate().
function truedate($day, $month, $year) {
    if (!ctype_digit((string)$day) || !ctype_digit((string)$month) || !ctype_digit((string)$year)) {
        return 0;
    }
    $day = (int)$day;
    $month = (int)$month;
    $year = (int)$year;

    if (checkdate($month, $day, $year)) {
        // Return timestamp if needed, or just true
        // return mktime(0, 0, 0, $month, $day, $year);
        return true;
    } else {
        return 0; // Or false
    }
    /* Original implementation:
    $diames = array (
        1  => 31,
        2  => 28,
        3  => 31,
        4  => 30,
        5  => 31,
        6  => 30,
        7  => 31,
        8  => 31,
        9  => 30,
        10 => 31,
        11 => 30,
        12 => 31,
    );
    if (($year % 4) === 0)
        $diames[2] = 29;

    if ($day > $diames[$month])
        return 0;

    return mktime(0, 0, 0, $month, $day, $year);
    */
}

// --- Status and Check Functions --- (Require updating to prepared statements)

// Checks if any character of the logged-in account is online.
// Needs update to prepared statements.
function is_online() {
    global $CONFIG_name, $lang, $mysql;

    if (empty($_SESSION[$CONFIG_name.'account_id']))
        redir("motd.php", "main_div", htmlformat($lang['NEED_TO_LOGIN_F']));

    $log_account = (int)$_SESSION[$CONFIG_name.'account_id'];

    // Updated to use prepared statements
    $query = "SELECT COUNT(1) FROM `char` WHERE online = '1' AND account_id = ?";
    $result = execute_query($query, 'is_online', 0, 1, 'i', [$log_account]);

    if ($result && $line = $result->fetch_row()) {
        return $line[0] > 0; // Return true if count > 0
    }
    return false; // Default to false on error or no result
}

// Counts total online players.
// Needs update to prepared statements.
function online_count() {
    global $mysql;
    // Updated to use prepared statements (though no parameters needed here)
    $query = "SELECT COUNT(1) FROM `char` WHERE online = '1'";
    $result = execute_query($query, 'online_count', 0, 0); // No report needed?

    if ($result && $line = $result->fetch_row()) {
        return (int)$line[0];
    }
    return 0; // Default to 0
}

// Checks if the current user's IP is banned.
// Needs update to prepared statements.
function check_ban() {
    global $mysql;
    $ip = $_SERVER['REMOTE_ADDR'];

    // Updated to use prepared statements
    $query = "SELECT UNIX_TIMESTAMP(`lastlogin`), `unban_time`, `state` FROM `login` WHERE `last_ip` = ?";
    $result = execute_query($query, 'check_ban', 0, 0, 's', [$ip]); // No report needed?

    if ($result && $result->count() > 0) {
        while ($line = $result->fetch_row()) {
            // Original logic: ban if state is 5 OR unban_time is set, AND last login was within 2 days
            if (($line[2] == 5 || $line[1] > 0) && (time() - $line[0]) < (86400 * 2)) {
                return 1; // Banned
            }
        }
    }
    return 0; // Not banned
}

// Original forger function - purpose unclear, possibly related to old data structures.
function forger($hint, $lint) {
    $result = 0;
    $result = $lint * 65536;
    if ($hint > 0)
        return ($result + $lint); // This seems wrong, maybe $hint?
    // return ($result + $hint);
    return ($result + 65536 + $hint); // Also seems potentially wrong
    // Needs clarification on its intended purpose.
}

// --- Database Interaction Functions --- 

// Updated execute_query to handle prepared statements via QueryClass
function execute_query($query, $source = 'none.php', $database = 0, $save_report = 1, $types = '', $params = []) {
    global $mysql;

    // Basic query cleanup (consider removing if not needed)
    $query = str_replace(["\r\n", "\n", "\r"], " ", $query);
    $query = trim($query);

    if ($save_report) {
        // Pass parameters to add_query_entry for accurate logging
        add_query_entry($source, $query, $types, $params);
    }

    // Call the updated Query method in QueryClass
    $result = $mysql->Query($query, $types, $params, $database);

    // Return the result object (ResultClass instance) or boolean status
    return $result;
}

// Updated add_query_entry to use prepared statements
function add_query_entry($source, $log_query, $types = '', $params = []) {
    global $CONFIG_name, $mysql;
    
    $log_account = isset($_SESSION[$CONFIG_name.'account_id']) ? (int)$_SESSION[$CONFIG_name.'account_id'] : 0;
    $log_ip = $_SERVER['REMOTE_ADDR'];

    // Serialize parameters for logging purposes
    $param_log = '';
    if ($params) {
        $param_log = " Types: [" . $types . "] Params: [" . implode(", ", array_map('strval', $params)) . "]";
    }
    $full_log = $log_query . $param_log;
    
    // Limit log length if necessary
    if (strlen($full_log) > 65535) { // Assuming TEXT field limit
        $full_log = substr($full_log, 0, 65532) . '...';
    }

    // Use prepared statement for inserting the log entry
    $insert_query = "INSERT INTO `cp_querylog` (`Date`, `User`, `IP`, `page`, `Query`) VALUES(NOW(), ?, ?, ?, ?)";
    
    // Execute the query without saving a report of itself (prevent recursion)
    execute_query($insert_query, 'none.php', 1, 0, 'isss', [$log_account, $log_ip, $source, $full_log]);
}

// --- Server Status Function --- (Requires update to prepared statements)

// Checks status of login, char, map servers.
// Needs update to prepared statements for DB interaction.
function server_status() {
    global $CONFIG_accip, $CONFIG_accport, $CONFIG_charip, $CONFIG_charport, $CONFIG_mapip, $CONFIG_mapport, $mysql;

    $retorno = 0;
    $last_checked_time = 0;
    $status_from_db = 0;
    $seconds_since_check = 301; // Default to force check

    // Check DB for cached status
    $query_check = "SELECT `last_checked`, `status`, TIMESTAMPDIFF(SECOND, `last_checked`, NOW()) FROM `cp_server_status` LIMIT 1";
    $result_check = execute_query($query_check, "server_status_check", 1, 0);

    if ($result_check && $line = $result_check->fetch_row()) {
        $last_checked_time = strtotime($line[0]);
        $status_from_db = (int)$line[1];
        $seconds_since_check = (int)$line[2];
    } else {
        // No status found, insert initial record
        $query_insert = "INSERT INTO `cp_server_status` (last_checked, status) VALUES(NOW(), 0)";
        execute_query($query_insert, "server_status_insert", 1, 0);
        // Proceed with checking servers
    }

    // Check servers if cache is old (e.g., > 300 seconds) or status seems incomplete
    if ($seconds_since_check > 300 || $status_from_db < 7) { // Original logic used < 7
        $acc_online = false;
        $char_online = false;
        $map_online = false;

        // Suppress errors for fsockopen
        $context = stream_context_create(['socket' => ['connect_timeout' => 1]]); // 1 second timeout
        
        $acc_socket = @stream_socket_client("tcp://" . $CONFIG_accip . ":" . $CONFIG_accport, $errno, $errstr, 1, STREAM_CLIENT_CONNECT, $context);
        if ($acc_socket) {
            $acc_online = true;
            fclose($acc_socket);
        }

        $char_socket = @stream_socket_client("tcp://" . $CONFIG_charip . ":" . $CONFIG_charport, $errno, $errstr, 1, STREAM_CLIENT_CONNECT, $context);
        if ($char_socket) {
            $char_online = true;
            fclose($char_socket);
        }

        $map_socket = @stream_socket_client("tcp://" . $CONFIG_mapip . ":" . $CONFIG_mapport, $errno, $errstr, 1, STREAM_CLIENT_CONNECT, $context);
        if ($map_socket) {
            $map_online = true;
            fclose($map_socket);
        }

        if ($acc_online) $retorno += 1;
        if ($char_online) $retorno += 2;
        if ($map_online) $retorno += 4;

        // Update status in DB using prepared statement
        $query_update = "UPDATE `cp_server_status` SET last_checked = NOW(), status = ?";
        execute_query($query_update, "server_status_update", 1, 0, 'i', [$retorno]);
    } else {
        // Use cached status
        $retorno = $status_from_db;
    }

    return $retorno;
}

// --- UI and Helper Functions --- 

// Redirects via JavaScript AJAX call.
function redir($page, $div, $msg) {
    opentable("Status");
    // Ensure message is properly encoded for JavaScript context if needed
    $safe_msg = htmlformat($msg); // Use htmlformat or htmlspecialchars
    $safe_page = htmlformat($page);
    $safe_div = htmlformat($div);
    echo "<tr><td><span style=\"cursor:pointer\" onMouseOver=\"this.style.color='#FF3300'\" onMouseOut=\"this.style.color='#000000'\" "

         . "onClick=\"return LINK_ajax('" . $safe_page . "','" . $safe_div . "')\"><b>" . $safe_msg . "</b></span></td></tr>";
    closetable();
    fim();
}

// Sends an alert message via JavaScript.
function alert($alertmsg) {
    // Ensure message is properly encoded for JavaScript
    $safe_alertmsg = json_encode($alertmsg); // json_encode is generally safer for JS
    echo "ALERT|" . $safe_alertmsg . "|ENDALERT";
    fim();
}

// Terminates script execution.
function fim() {
    global $mysql;
    // $mysql->finish(); // finish() might not be needed with updated QueryClass
    exit(0);
}

// Opens a styled table for UI output.
function opentable($titulo) {
    $safe_titulo = htmlformat($titulo);
    echo "
<center><table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">
	<tbody>
		<tr>
			<th height=\"28\" class=\"title\">" . $safe_titulo . "</th>
		</tr>
		<tr>
			<td>
	"
;
}

// Closes the styled table.
function closetable() {
    echo "
			</td>
		</tr>
	</tbody>
</table></center>
	"
;
}

// Reads mail template file.
function read_maildef($file) {
    global $lang;
    $filePath = __DIR__ . '/language/mail/' . basename($file) . '.txt'; // Basic path safety
    if (!is_readable($filePath)) {
        error_log("Error reading mail definition file: " . $filePath);
        die(htmlformat(isset($lang['TXT_ERROR']) ? $lang['TXT_ERROR'] : 'File Read Error'));
    }
    $handle = fopen($filePath, "rt");
     if (!$handle) {
         error_log("Error opening mail definition file: " . $filePath);
         die(htmlformat(isset($lang['TXT_ERROR']) ? $lang['TXT_ERROR'] : 'File Read Error'));
    }
    $maildef = "";
    while (($line = fgets($handle, 1024)) !== false) {
        // Skip comment lines
        if (strpos(trim($line), '//') === 0) {
            continue;
        }
        $maildef .= $line;
    }
    fclose($handle);
    return $maildef;
}

// Handles login errors, destroys session, clears cookies, and triggers JS reload.
function erro_de_login($i = 0) {
    // Clear session and cookies
    session_destroy();
    setcookie("login_pass", "", time() - 3600);
    setcookie("userid", "", time() - 3600);
    session_start(); // Start a new session
    
    // Trigger JS updates
    echo "<script type=\"text/javascript\">";
    echo "LINK_ajax('login.php', 'login_div');";
    if (!$i) {
        echo "LINK_ajax('motd.php','main_div');";
    }
    echo "</script>";
    // Do not call fim() here if alert() is called afterwards in the calling code
}

// --- WOE Time Functions --- (Mostly date/time logic, seem okay)

function is_woe() {
    global $CONFIG_woe_time;
    // Ensure timezone is set correctly
    // date_default_timezone_set('Your/Timezone'); // Set in config or here
    $wdaynow = date('w'); // 0 (for Sunday) through 6 (for Saturday)
    $wtimenow = date('Hi'); // HHMM format
    $week_day = [
        0 => 'sun',
        1 => 'mon',
        2 => 'tue',
        3 => 'wed',
        4 => 'thu',
        5 => 'fri',
        6 => 'sat'

    ];

    $woe_times_str = trim($CONFIG_woe_time);
    if (empty($woe_times_str)) {
        return FALSE;
    }

    $woe_periods = explode(';', $woe_times_str);
    foreach ($woe_periods as $period) {
        $period = trim($period);
        if (empty($period)) {
            continue;
        }
        // Parse format like: tue(2100, 2300)
        if (preg_match('/^(\w+)\((\d{4}),\s*(\d{4})\)$/', $period, $matches)) {
            $day_abbr = strtolower($matches[1]);
            $start_time = $matches[2];
            $end_time = $matches[3];

            if ($day_abbr === $week_day[$wdaynow]) {
                if ($wtimenow >= $start_time && $wtimenow < $end_time) {
                    return TRUE;
                }
            }
        }
    }

    return FALSE;
}

function ret_woe_times() {
    global $CONFIG_woe_time, $lang;

    $week_day_map = [
        
'sun'
 => isset($lang['SUNDAY']) ? $lang['SUNDAY'] : 'Sunday',
        
'mon'
 => isset($lang['MONDAY']) ? $lang['MONDAY'] : 'Monday',
        
'tue'
 => isset($lang['TUESDAY']) ? $lang['TUESDAY'] : 'Tuesday',
        
'wed'
 => isset($lang['WEDNSDAY']) ? $lang['WEDNSDAY'] : 'Wednesday', // Typo in original lang key?
        
'thu'
 => isset($lang['THURSDAY']) ? $lang['THURSDAY'] : 'Thursday',
        
'fri'
 => isset($lang['FRIDAY']) ? $lang['FRIDAY'] : 'Friday',
        
'sat'
 => isset($lang['SATURDAY']) ? $lang['SATURDAY'] : 'Saturday'

    ];

    $woe_times_str = trim($CONFIG_woe_time);
    if (empty($woe_times_str)) {
        echo "<tr><td colspan='3'>No WoE times configured.</td></tr>";
        return;
    }

    $woe_periods = explode(';', $woe_times_str);
    foreach ($woe_periods as $period) {
         $period = trim($period);
        if (empty($period)) {
            continue;
        }
        if (preg_match('/^(\w+)\((\d{4}),\s*(\d{4})\)$/', $period, $matches)) {
            $day_abbr = strtolower($matches[1]);
            $start_time = substr_replace($matches[2], ':', 2, 0); // Format HH:MM
            $end_time = substr_replace($matches[3], ':', 2, 0);   // Format HH:MM

            $day_name = isset($week_day_map[$day_abbr]) ? $week_day_map[$day_abbr] : ucfirst($day_abbr);
            
            echo "<tr><td align=\"right\">" . $day_name . "</td><td>&nbsp;</td><td align=\"left\">" . $start_time . " - " . $end_time . "</td></tr>";
        }
    }
}

?>

