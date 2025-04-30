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

// Ensure jobs array is loaded from session
$jobs = isset($_SESSION[$CONFIG_name.
'jobs'
]) ? $_SESSION[$CONFIG_name.
'jobs'
] : [];

// Check if WoE is active
if (is_woe()) {
    redir(
"motd.php"
, 
"main_div"
, $lang[
'WOE_TIME'
]);
}

// Execute the query using the updated function (no parameters needed for WHOISONLINE)
$result = execute_query(WHOISONLINE, 
"whoisonline.php"
, 0, 1);

opentable($lang[
'WHOISONLINE_WHOISONLINE'
]);
echo 
"
<table width=\"500\">
<tr>
	<td align=\"left\" class=\"head\">".htmlformat($lang[
'NAME'
])."</td>
	<td align=\"left\" class=\"head\">".htmlformat($lang[
'CLASS'
])."</td>
	<td align=\"center\" class=\"head\">".htmlformat($lang[
'BLVLJLVL'
])."</td>
	"
;

// Check if admin should see coordinates
$show_coords = (isset($_SESSION[$CONFIG_name.
'level'
]) && $_SESSION[$CONFIG_name.
'level'
] >= $CONFIG[
'cp_admin'
]);

if ($show_coords) {
    echo 
"<td align=\"center\" class=\"head\">".htmlformat($lang[
'WHOISONLINE_COORDS'
])."</td>"
;
}

echo 
"
	<td align=\"left\" class=\"head\">".htmlformat($lang[
'MAP'
])."</td>
</tr>
"
;

if ($result && $result->count() > 0) {
    while ($line = $result->fetch_row()) {
        // $line indices based on WHOISONLINE query:
        // 0: char.name, 1: char.class, 2: char.base_level, 3: char.job_level,
        // 4: char.last_x, 5: char.last_y, 6: char.last_map, 7: char.account_id,
        // 8: char.char_id, 9: login.group_id

        $char_name = htmlformat($line[0]);
        $char_class_id = (int)$line[1];
        $base_level = (int)$line[2];
        $job_level = (int)$line[3];
        $coord_x = (int)$line[4];
        $coord_y = (int)$line[5];
        $map_name = htmlformat($line[6]);
        $group_id = (int)$line[9];

        // GM Hiding Logic
        if ($group_id >= $CONFIG_gm_hide) {
            // Hide if current user is not logged in or has lower level than the GM
            if (!isset($_SESSION[$CONFIG_name.
'level'
]) || $_SESSION[$CONFIG_name.
'level'
] < $group_id) {
                continue; // Skip this character
            }
        }

        // Get Job Name
        $job_name = isset($jobs[$char_class_id]) ? htmlformat($jobs[$char_class_id]) : htmlformat($lang[
'UNKNOWN'
]);

        echo 
"
                <tr>
                    <td align=\"left\">".$char_name."</td>
                    <td align=\"left\">".$job_name."</td>
                    <td align=\"center\">".$base_level."/" .$job_level."</td>
                    "
;

        if ($show_coords) {
            echo 
"<td align=\"center\">".$coord_x."," .$coord_y."</td>"
;
        }

        echo 
"
                    <td align=\"left\">".$map_name."</td>
                </tr>
            "
;
    }
} else {
    // Display a message if no players are online or if there was a query error
    $colspan = $show_coords ? 5 : 4;
    echo 
"<tr><td colspan='"
.$colspan.
"' align='center'>".htmlformat($lang['NOBODY_ONLINE'] ?? 'No players online')."</td></tr>"
;
}

echo 
"</table>"
;
closetable();
fim();
?>

