<?php
/*
Ceres Control Panel - Updated QueryClass for Prepared Statements

Original copyright (C) 2005 by Beowulf and Nightroad
Modifications for prepared statements by Manus AI

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

class ResultClass {
    // Keep ResultClass mostly the same, it works with mysqli_result
    var $result;
    var $row;

    function ResultClass($arg1) {
        $this->row = FALSE;
        $this->result = $arg1;
    }

    function fetch_row() {
        if ($this->result instanceof mysqli_result) {
            $this->row = mysqli_fetch_row($this->result);
        } else {
            $this->row = FALSE;
        }
        return $this->row;
    }

    function count() {
        if ($this->result instanceof mysqli_result) {
            return mysqli_num_rows($this->result);
        }
        return 0;
    }

    function row($pos) {
        if (isset($this->row[$pos])) {
            return $this->row[$pos];
        }
        return FALSE;
    }

    function free() {
        if ($this->result instanceof mysqli_result) {
            mysqli_free_result($this->result);
        }
        // No need to free if it wasn't a mysqli_result (e.g., for INSERT/UPDATE/DELETE)
    }
}

class QueryClass {
    var $rag_link;
    var $cp_link;
    // Removed $result property as it's handled per query now

    function QueryClass($rag_addr, $rag_username, $rag_password, $rag_db, $cp_addr, $cp_username, $cp_password, $cp_db) {
        global $lang;

        // Enable error reporting for mysqli
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {
            $this->rag_link = mysqli_connect($rag_addr, $rag_username, $rag_password, $rag_db);
            mysqli_set_charset($this->rag_link, 'utf8mb4'); // Set charset, adjust if rAthena DB uses something else
        } catch (mysqli_sql_exception $e) {
             error_log("Failed to connect to Ragnarok DB: " . $e->getMessage());
             die(isset($lang["DB_ERROR"]) ? $lang["DB_ERROR"] : "Database connection error (RAG)");
        }
        
        try {
            $this->cp_link = mysqli_connect($cp_addr, $cp_username, $cp_password, $cp_db);
             mysqli_set_charset($this->cp_link, 'utf8mb4'); // Set charset for CP DB
        } catch (mysqli_sql_exception $e) {
             error_log("Failed to connect to CP DB: " . $e->getMessage());
             die(isset($lang["DB_ERROR"]) ? $lang["DB_ERROR"] : "Database connection error (CP)");
        }
        
        // Disable mysqli error reporting after connection if desired, or keep it for debugging
        // mysqli_report(MYSQLI_REPORT_OFF);
    }

    // Modified Query method to use prepared statements
    function Query($query, $types = '', $params = [], $table = 0) {
        global $lang;
        $link = $table ? $this->cp_link : $this->rag_link;

        try {
            $stmt = mysqli_prepare($link, $query);
            if (!$stmt) {
                 error_log("mysqli_prepare failed: (" . mysqli_errno($link) . ") " . mysqli_error($link) . " Query: " . $query);
                 return false; // Indicate failure
            }

            if ($types && $params) {
                // Use array unpacking for bind_param
                if (!mysqli_stmt_bind_param($stmt, $types, ...$params)) {
                    error_log("mysqli_stmt_bind_param failed: (" . mysqli_stmt_errno($stmt) . ") " . mysqli_stmt_error($stmt));
                    mysqli_stmt_close($stmt);
                    return false;
                }
            }

            if (!mysqli_stmt_execute($stmt)) {
                 error_log("mysqli_stmt_execute failed: (" . mysqli_stmt_errno($stmt) . ") " . mysqli_stmt_error($stmt));
                 mysqli_stmt_close($stmt);
                 return false;
            }

            // Check if it's a SELECT query (crude check, might need refinement)
            if (stripos(trim($query), 'SELECT') === 0) {
                $result = mysqli_stmt_get_result($stmt);
                if ($result === false) {
                     error_log("mysqli_stmt_get_result failed: (" . mysqli_stmt_errno($stmt) . ") " . mysqli_stmt_error($stmt));
                     mysqli_stmt_close($stmt);
                     return false;
                }
                $resultObject = new ResultClass($result);
                mysqli_stmt_close($stmt);
                return $resultObject;
            } else {
                // For INSERT, UPDATE, DELETE, etc., return success/failure or affected rows if needed
                $affected_rows = mysqli_stmt_affected_rows($stmt);
                mysqli_stmt_close($stmt);
                // Return true on success for non-SELECT, or affected_rows if that's more useful
                return ($affected_rows >= 0); 
            }

        } catch (mysqli_sql_exception $e) {
            error_log("Database query error: " . $e->getMessage() . " Query: " . $query);
            // Optionally close statement if it exists
            if (isset($stmt) && $stmt instanceof mysqli_stmt) {
                 mysqli_stmt_close($stmt);
            }
            return false; // Indicate failure
        }
    }

    // finish() method might not be needed if results are freed in ResultClass::free()
    // and statements are closed within Query()
    function finish() {
       // mysqli connections are typically persistent or closed automatically at script end.
       // Explicit closing might be needed in long-running scripts.
       // mysqli_close($this->rag_link);
       // mysqli_close($this->cp_link);
    }
    
    // Add a method to get the link for escaping if absolutely needed (though prefer parameters)
    function getLink($table = 0) {
        return $table ? $this->cp_link : $this->rag_link;
    }
}

?>

