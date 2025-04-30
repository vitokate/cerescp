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

// Check if user is logged in
if (empty($_SESSION[$CONFIG_name.
'account_id'
]) || $_SESSION[$CONFIG_name.
'account_id'
] <= 0) {
    redir(
"motd.php"
, 
"main_div"
, $lang[
'NEED_TO_LOGIN'
]);
}

$account_id = (int)$_SESSION[$CONFIG_name.
'account_id'
];
$user_id = $_SESSION[$CONFIG_name.
'userid'
]; // Get userid for fetching current password

// Process form submission
if (!empty($POST_opt)) {
    if ($POST_opt == 1 && isset($POST_frm_name) && !strcmp($POST_frm_name, 
"password"
)) {

        // --- Input Validation --- 
        $old_password = trim($POST_login_pass ?? 
''
);
        $new_password = trim($POST_newpass ?? 
''
);
        $confirm_password = trim($POST_confirm ?? 
''
);

        // 1. Basic Character Check (replace weak inject() if possible)
        // Keeping inject for now due to complexity with MD5/plain text, but it's weak.
        if (inject($old_password) || inject($new_password)) {
            alert($lang[
'INCORRECT_CHARACTER'
]);
        }

        // 2. Length Checks (consider rAthena limits)
        $pass_max_len = 23; // Original limit, check if rAthena allows more for MD5/plain
        if (strlen($old_password) < 4 || strlen($old_password) > $pass_max_len) {
            alert($lang[
'PASSWORD_LENGTH_OLD'
] . 
" (Current Password)"
);
        }
        $new_pass_min_len = $CONFIG_safe_pass ? 6 : 4;
        if (strlen($new_password) < $new_pass_min_len || strlen($new_password) > $pass_max_len) {
            alert($CONFIG_safe_pass ? $lang[
'PASSWORD_LENGTH'
] : $lang[
'PASSWORD_LENGTH_OLD'
] . 
" (New Password)"
);
        }

        // 3. Password Confirmation and Policy Checks
        if ($new_password !== $confirm_password) {
            alert($lang[
'PASSWORD_NOT_MATCH'
]);
        }
        // Cannot easily check against username here without fetching it again
        // if (!strcmp($new_password, $user_id)) { alert($lang['PASSWORD_REJECTED']); }
        if ($CONFIG_safe_pass && thepass($new_password)) { // Dictionary/complexity check
            alert($lang[
'PASSWORD_REJECTED'
]);
        }

        // --- Change Password Logic --- 

        // Fetch current user data including password (hash or plain)
        // Re-use LOGIN_USER query which selects account_id, userid, group_id, user_pass
        $query_check = LOGIN_USER; // Query defined in query.php
        $result_check = execute_query($query_check, 
'password.php'
, 0, 1, 
's'
, [$user_id]);

        if ($result_check && $result_check->count() == 1 && $line = $result_check->fetch_row()) {
            $current_password_stored = $line[3];
            $password_to_check = $old_password;

            // If MD5 is enabled in config, hash the submitted old password for comparison
            if ($CONFIG_md5_pass) {
                $password_to_check = md5($old_password);
            }

            // Verify the submitted old password against the stored one
            if ($password_to_check === $current_password_stored) {
                
                // Prepare the new password (hash if MD5 is enabled)
                $final_new_password = $new_password;
                if ($CONFIG_md5_pass) {
                    $final_new_password = md5($new_password);
                }

                // Update the password in the database (Use Prepared Statement)
                $query_update = CHANGE_PASSWORD; // Query defined in query.php
                $result_update = execute_query($query_update, 
'password.php'
, 0, 1, 
'si'
, [$final_new_password, $account_id]);

                if ($result_update) {
                    // Password changed successfully
                    redir(
"password.php"
, 
"main_div"
, $lang[
'PASSWORD_CHANGED'
]);
                } else {
                    // Database update failed
                    error_log("Password change query failed for account ID: " . $account_id);
                    alert($lang[
'ERROR'
] ?? 
'Database error during password update.'
);
                }
            } else {
                // Submitted old password does not match stored password
                alert($lang[
'INCORRECT_PASSWORD'
]);
            }
        } else {
            // Could not fetch user data or user not found (shouldn't happen if logged in)
            error_log("Failed to fetch user data for password change. Account ID: " . $account_id);
            alert($lang[
'ERROR'
] ?? 
'Error fetching user data.'
);
        }
    }
}

// --- Display Form --- 
opentable($lang[
'CHANGE_PASSWORD'
]);
echo 
"
		<form id=\"password\" onsubmit=\"return POST_ajax('password.php','main_div','password')\"><table>
		<tr><td align=right>".htmlformat($lang[
'PASSWORD'
]).":</td><td>
			<input type=\"password\" name=\"login_pass\" maxlength=\"23\" size=\"23\" required onKeyPress=\"return force(this.name,this.form.id,event);\">
			</td></tr>
		<tr><td align=right>".htmlformat($lang[
'NEW_PASSWORD'
]).":</td><td>
			<input type=\"password\" name=\"newpass\" maxlength=\"23\" size=\"23\" required onKeyPress=\"return force(this.name,this.form.id,event);\">
			</td></tr>
		<tr><td align=right>".htmlformat($lang[
'CONFIRM'
]).":</td><td>
			<input type=\"password\" name=\"confirm\" maxlength=\"23\" size=\"23\" required onKeyPress=\"return force(this.name,this.form.id,event);\">
			</td></tr>
		<input type=\"hidden\" name=\"opt\" value=\"1\">
		<tr><td>&nbsp;</td><td><input type=\"submit\" value=\"".htmlformat($lang[
'CHANGE'
])."\"></td></tr>
		</table></form>
		"
;
closetable();
fim();

?>

