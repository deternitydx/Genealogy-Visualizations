<?php
include("../database.php");
$db = null;
$testing = false;


function logger($str, $comment) {
    global $output;
    $c = "";
    if ($comment) $c = "-- ";
    fwrite($output, $c . str_replace("\n"," ", $str) . "\n");
}

function setup_db() {
    global $db, $db_conn_string;
    $db = pg_connect($db_conn_string);
}

function query_db($q, $is_select) {
    global $db, $testing;

    if ($db == null) {
        logger("Database not initialized", true);
        return false;
    }

    if ($testing !== true) {
        $result = pg_query($db, $q);
        if (!$result) {
            logger("Error: " . pg_last_error($db), true);
            return false;
        }

        // If selecting only, and nothing is returned, then this is a false
        if ($is_select && pg_num_rows($result) == 0) {
            return false;
        }

        // Get the last insert value (if needed)
        $res = pg_query($db, "SELECT lastval()");
        $temprow = pg_fetch_Array($res);

        // Return the new id
        return $temprow[0];
    } else {
        logger("Testing Only, not submitted", true);
        return 1;
    }
}

function close_db() {
    global $db;
    pg_close($db);
}

function get_insert_statement($tableName, $arr) {
    $insert = "INSERT INTO public.\"$tableName\" ";
    
    if (!empty($arr)) {
        $cols = "";
        $vals = "";
        foreach ($arr as $k => $v) {
            $cols .= pg_escape_identifier($k) . ",";
            if ($v == "") $v = "NULL";
            if (is_numeric($v) || $v == "NULL")
                $vals .= "$v,";
            else
                $vals .= pg_escape_literal($v) . ",";
        }
        $cols = substr($cols, 0, -1);
        $vals = substr($vals, 0, -1);

        $insert .= "($cols) VALUES ($vals)";
    }
    $insert .= ";";

    return $insert;
}

function get_update_statement($tableName, $arr, $match) {
    $insert = "UPDATE public.\"$tableName\" ";
    $cols = "";
    $vals = "";
    foreach ($arr as $k => $v) {
        $cols .= pg_escape_identifier($k) .",";
        if ($v == "") $v = "NULL";
        if (is_numeric($v) || $v == "NULL")
            $vals .= "$v,";
        else
            $vals .= pg_escape_literal($v) . ",";
    }
    $cols = substr($cols, 0, -1);
    $vals = substr($vals, 0, -1);

    $insert .= " SET ($cols) = ($vals)";

    $insert .= " WHERE $match RETURNING *;";

    return $insert;
}

function get_search_statement($tableName, $match) {
    $insert = "SELECT * FROM public.\"$tableName\" ";
    $insert .= " WHERE $match;";

    return $insert;
}

function get_delete_statement($tableName, $match) {
    $insert = "DELETE FROM public.\"$tableName\" ";
    $insert .= " WHERE $match RETURNING *;";

    return $insert;
}

function insert($tableName, $arr) {
    global $output;

    $insert = get_insert_statement($tableName, $arr);
    // Logging output just in case
    logger($insert, false);
    return query_db($insert, false);
}

function update($tableName, $arr, $match) {
    global $output;

    $update = get_update_statement($tableName, $arr, $match);
    
    // Logging output just in case
    logger($update, false);
    return query_db($update, false) === false ? false : true;
}

function search($tableName, $match) {
    global $output;

    $update = get_search_statement($tableName, $match);
    
    // Logging output just in case
    logger($update, false);
    return query_db($update, true) === false ? false : true;
}

function dbdelete($tableName, $match) {
    global $output;

    $update = get_delete_statement($tableName, $match);
    
    // Logging output just in case
    logger($update, false);
    return query_db($update, false) === false ? false : true;
}

function combine_date($year, $month, $day) {
    $date = "";
    if ($year != "YYYY" && $year != "") {
        $date .= $year;
        if ($month != "MM" && $month != "") {
            if (intval($month) < 10)
                $date .= "-0" . intval($month);
            else
                $date .= "-" . intval($month);
            if ($day != "DD" && $day != "") {
                if (intval($day) < 10)
                    $date .= "-0" . intval($day);
                else
                    $date .= "-" . intval($day);
            }
        }
    }
    
    return $date;
}

function updateInsertPM($vals) {
    if (search("PersonMarriage", "\"MarriageID\" = " . $vals["MarriageID"] . 
        " AND \"Role\" = '" . $vals["Role"] . "'")) {
        if ($vals["PersonID"] != null && $vals["PersonID"] != "")
            // Found this participant and they need to be updated to a different person
            update("PersonMarriage", $vals, "\"MarriageID\" = " . $vals["MarriageID"] . 
            " AND \"Role\" = '" . $vals["Role"] . "'");
        else
            // Found this participant and they need to be removed from the marriage (personid is empty or null)
            dbdelete("PersonMarriage", "\"MarriageID\" = " . $vals["MarriageID"] . 
            " AND \"Role\" = '" . $vals["Role"] . "'");
    } else {
        // Not found, but there is a person, so insert them
        if ($vals["PersonID"] != null && $vals["PersonID"] != "")
            insert("PersonMarriage", $vals);
    }
}
?>
