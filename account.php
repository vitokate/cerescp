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

session_start();
include_once __DIR__ . 
'/config.php'
; // loads config variables
include_once __DIR__ . 
'/query.php'
; // imports queries
include_once __DIR__ . 
'/functions.php'
;

// Check if account creation is disabled or user is banned
if ($CONFIG_disable_account || check_ban()) { // check_ban() already uses prepared statements
    redir(
"motd.php"
, 
"main_div"
, 
"Account creation disabled or user banned."
); // Provide clearer message
}

// Check max accounts limit
if ($CONFIG_max_accounts > 0) {
    // MAX_ACCOUNTS query uses COUNT(1), no parameters needed
    $result = execute_query(MAX_ACCOUNTS, 
'account.php'
, 0, 1);
    if ($result && $line = $result->fetch_row()) {
        $current_accounts = (int)$line[0];
        if ($current_accounts >= $CONFIG_max_accounts) {
            redir(
"motd.php"
, 
"main_div"
, $lang[
'ACCOUNT_MAX_REACHED'
]);
        }
    } else {
        // Handle query error if necessary
        error_log("Failed to check max accounts limit.");
        // Maybe alert the user or redirect with an error
    }
}

// Process form submission
if (isset($POST_opt)) {
    if ($POST_opt == 1 && isset($POST_frm_name) && !strcmp($POST_frm_name, 
"account"
)) {
        
        // --- Input Validation --- 
        $username = trim($POST_username ?? 
''
);
        $password = trim($POST_password ?? 
''
);
        $confirm = trim($POST_confirm ?? 
''
);
        $email = trim($POST_email ?? 
''
);
        $birthdate_str = trim($POST_birthdate ?? 
''
);
        $sex = ($POST_sex ?? 
'0'
) === 
'1'
 ? 
'F'
 : 
'M'
; // Default to Male
        $security_code = strtoupper($POST_code ?? 
''
);
        $ip_address = $_SERVER[
'REMOTE_ADDR'
];

        // 1. CAPTCHA Check (uses MD5, consider replacing with modern CAPTCHA)
        $session = $_SESSION[$CONFIG_name.
'sessioncode'
] ?? [];
        if ($CONFIG_auth_image && function_exists(
"gd_info"
)
            && (!isset($session[
'account'
]) || $security_code !== substr(strtoupper(md5(
"Mytext"
.$session[
'account'
])), 0, 6)))
        {
            alert($lang[
'INCORRECT_CODE'
]);
        }

        // 2. Basic Character Check (replace weak inject() if possible)
        // Using a stricter validation approach
        if (!preg_match(
'/^[a-zA-Z0-9_]+$/'
, $username)) { // Allow letters, numbers, underscore
             alert($lang[
'INCORRECT_CHARACTER'
] . 
" (Username)"
);
        }
        // Password validation is complex due to MD5/plain text options, length checks below
        if (inject($password)) { // Keep inject for password temporarily if it allows needed chars
             alert($lang[
'INCORRECT_CHARACTER'
] . 
" (Password)"
);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            alert($lang[
'EMAIL_NEEDED'
]);
        }
        // Validate birthdate format (YYYYMMDD)
        if (!preg_match(
'/^(\d{4})(\d{2})(\d{2})$/'
, $birthdate_str, $matches) || !checkdate($matches[2], $matches[3], $matches[1])) {
             alert($lang[
'INVALID_BIRTHDAY'
]);
        }
        $birthdate_formatted = $matches[1] . 
'-'
 . $matches[2] . 
'-'
 . $matches[3]; // Format for DB (YYYY-MM-DD)

        // 3. Length Checks
        if (strlen($username) < 4 || strlen($username) > 23) {
            alert($lang[
'USERNAME_LENGTH'
]);
        }
        // Password length check (consider rAthena limits)
        $pass_min_len = $CONFIG_safe_pass ? 6 : 4;
        $pass_max_len = 23; // Original limit, check if rAthena allows more for MD5/plain
        if (strlen($password) < $pass_min_len || strlen($password) > $pass_max_len) {
             alert($CONFIG_safe_pass ? $lang[
'PASSWORD_LENGTH'
] : $lang[
'PASSWORD_LENGTH_OLD'
]);
        }
        if (strlen($email) > 40) { // Check based on DB schema
             alert(
"Email address is too long."
);
        }

        // 4. Password Confirmation and Policy Checks
        if ($password !== $confirm) {
            alert($lang[
'PASSWORD_NOT_MATCH'
]);
        }
        if (!strcmp($password, $username)) { // Password same as username
            alert($lang[
'PASSWORD_REJECTED'
]);
        }
        if ($CONFIG_safe_pass && thepass($password)) { // Dictionary/complexity check
            alert($lang[
'PASSWORD_REJECTED'
]);
        }

        // 5. Check if Username Exists (Use Prepared Statement)
        $query_check_user = 
"SELECT `userid` FROM `login` WHERE userid = ?"
;
        $result_check_user = execute_query($query_check_user, 
'account.php'
, 0, 1, 
's'
, [$username]);
        if ($result_check_user && $result_check_user->count() > 0) {
            alert($lang[
'USERNAME_IN_USE'
]);
        }

        // --- Create Account --- 
        
        // Hash password if MD5 is enabled in config
        $final_password = $password;
        if ($CONFIG_md5_pass) {
            $final_password = md5($password);
        }

        // Insert Account (Use Prepared Statement)
        $query_insert = 
"INSERT INTO `login` (`userid`, `user_pass`, `sex`, `email`, `birthdate`, `last_ip`) VALUES (?, ?, ?, ?, ?, ?)"
;
        $result_insert = execute_query($query_insert, 
'account.php'
, 0, 1, 
'ssssss'
, 
                                    [$username, $final_password, $sex, $email, $birthdate_formatted, $ip_address]);

        if ($result_insert) {
            // Verify insertion by checking account ID (Optional but good practice)
            // CHECK_ACCOUNTID query might be redundant if INSERT succeeded
            // $query_check_id = "SELECT `account_id` FROM `login` WHERE `userid` = ? AND `user_pass` = ?";
            // $result_check_id = execute_query($query_check_id, 'account.php', 0, 1, 'ss', [$username, $final_password]);
            // if ($result_check_id && $result_check_id->count() > 0) {
                 erro_de_login(1); // Clear session/cookies
                 redir(
"motd.php"
, 
"main_div"
, $lang[
'ACCOUNT_CREATED'
]);
            // } else {
            //     // This case indicates an issue even after successful insert?
            //     error_log("Account possibly created but verification failed for user: " . $username);
            //     erro_de_login(1);
            //     redir("motd.php", "main_div", $lang['ACCOUNT_PROBLEM']);
            // }
        } else {
            error_log(
"Account creation failed for user: "
 . $username . 
" Error: "
 . ($mysql->getLink(0) ? mysqli_error($mysql->getLink(0)) : 
'Unknown DB error'
));
            erro_de_login(1);
            redir(
"motd.php"
, 
"main_div"
, $lang[
'ACCOUNT_PROBLEM'
]);
        }
    }
}

// Generate new session code for CAPTCHA
if (isset($_SESSION[$CONFIG_name.
'sessioncode'
])) {
    $session = $_SESSION[$CONFIG_name.
'sessioncode'
];
} else {
     $session = [];
}
$session[
'account'
] = rand(12345, 99999);
$_SESSION[$CONFIG_name.
'sessioncode'
] = $session;
$var = rand(10, 9999999); // Variable for cache busting CAPTCHA image

// --- Display Form --- 
opentable($lang[
'NEW_ACCOUNT'
]);
echo 
"
	<form id=\"account\" onSubmit=\"return POST_ajax('account.php','main_div','account');\"><table>
	<tr><td align=\"right\">".htmlformat($lang[
'USERNAME'
]).":</td><td align=\"left\">
	<input type=\"text\" name=\"username\" maxlength=\"23\" size=\"23\" required onKeyPress=\"return force(this.name,this.form.id,event);\">
	</td></tr>
	<tr><td align=\"right\">".htmlformat($lang[
'PASSWORD'
]).":</td><td align=\"left\">
	<input type=password name=\"password\" maxlength=\"23\" size=\"23\" required onKeyPress=\"return force(this.name,this.form.id,event);\">
	</td></tr>
	<tr><td align=\"right\">".htmlformat($lang[
'CONFIRM'
]).":</td><td align=\"left\">
	<input type=password name=\"confirm\" maxlength=\"23\" size=\"23\" required onKeyPress=\"return force(this.name,this.form.id,event);\">
	</td></tr>
	<tr><td align=\"right\">".htmlformat($lang[
'SEX'
]).":</td><td align=\"left\">
	<select name=\"sex\" onKeyPress=\"return force(this.name,this.form.id,event);\">
	<option value=\"0\">".htmlformat($lang[
'SEX_MALE'
])."</option>
    <option value=\"1\">".htmlformat($lang[
'SEX_FEMALE'
])."</option>
    </select></td></tr>
	<tr><td align=\"right\">".htmlformat($lang[
'MAIL'
]).":</td><td align=\"left\">
	<input type=\"email\" name=\"email\" maxlength=\"40\" size=\"40\" required onKeyPress=\"return force(this.name,this.form.id,event);\">
	</td></tr>
    <tr><td align=\"right\">".htmlformat($lang[
'BIRTHDAY'
])." (YYYYMMDD):</td><td align=\"left\">
	<input type=\"text\" name=\"birthdate\" maxlength=\"8\" size=\"8\" required pattern=\"\\d{8}\" title=\"Enter date as YYYYMMDD\" onKeyPress=\"return force(this.name,this.form.id,event);\">
	<input type=\"hidden\" name=\"opt\" value=\"1\">
    </td></tr>
	<input type=\"hidden\" name=\"ipaddress\" value=\"".htmlformat($_SERVER[
'REMOTE_ADDR'
])."\">
    "
;

if ($CONFIG_auth_image && function_exists(
"gd_info"
)) { 
    echo 
"<tr><td></td><td align=left><img src=\"img.php?img=account&var=$var\" alt=\"".htmlformat($lang[
'SECURITY_CODE'
])."\">
		</td></tr><tr><td align=right>".htmlformat($lang[
'CODE'
]).":</td>
		<td align=\"left\">
		<input type=\"text\" name=\"code\" maxlength=\"6\" size=\"6\" required autocomplete=\"off\" onKeyPress=\"return force(this.name,this.form.id,event);\">
		&nbsp;</td></tr>"
;
}

echo 
"
	<tr><td>&nbsp;</td><td><input type=\"submit\" name=\"create\" value=\"".htmlformat($lang[
'CREATE'
])."\"></td></tr>
	</table></form>
	"
;
closetable();
fim(); // Ensure fim() is called to exit script properly
?>

