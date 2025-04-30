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

// Updated ipban function to use prepared statements
function ipban() {
    // Validate REMOTE_ADDR
    if (!filter_var($_SERVER["REMOTE_ADDR"], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        error_log("Invalid IP address format for IP ban check: " . $_SERVER["REMOTE_ADDR"]);
        return 0; // Not banned (or handle error appropriately)
    }

    $p = explode(".", $_SERVER["REMOTE_ADDR"]);

    // Construct potential ban patterns
    $pattern1 = $p[0] . ".*.*.*";
    $pattern2 = $p[0] . "." . $p[1] . ".*.*";
    $pattern3 = $p[0] . "." . $p[1] . "." . $p[2] . ".*";
    $pattern4 = $_SERVER["REMOTE_ADDR"]; // The full IP

    // Updated query using placeholders
    $query = "SELECT COUNT(*) FROM `ipbanlist` WHERE `list` = ? OR `list` = ? OR `list` = ? OR `list` = ?";
    
    // Use the updated execute_query function with type string 'ssss' and parameters
    $result = execute_query($query, 'ipban.php', 0, 0, 'ssss', [$pattern1, $pattern2, $pattern3, $pattern4]);

    if ($result && $line = $result->fetch_row()) {
        return $line[0] > 0 ? 1 : 0; // Return 1 if count > 0 (banned), otherwise 0
    } 

    // Handle potential query error, default to not banned
    error_log("IP Ban check query failed for IP: " . $_SERVER["REMOTE_ADDR"]);
    return 0; 
}

?>

