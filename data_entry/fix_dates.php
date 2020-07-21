<?php
if ($argc < 3) {
    echo ("Please supply an argument: php fix_dates.php TABLE COLUMN\n");
    exit();
}

$table = $argv[1];
$column = $argv[2];

include("../database.php");
$db = pg_connect($db_conn_string);

$result = pg_query($db, "select \"ID\", \"$column\" from \"$table\"");
if (!$result) {
    echo("--  Error: " . pg_last_error($db) . "\n");
    exit;
}

$results = pg_fetch_all($result);

foreach ($results as $res) {

    $date = explode("-", $res[$column]);

    // check the 3-part dates
    if (count($date) == 3 && $date[0] <= 31) {
        if ($date[2] == "" || $date[1] == "" || $date[2] <= 31) {
            echo "!! Skippped ID={$res["ID"]} with $column={$res[$column]}\n";
            continue;
        }
        // need to fix up this date, its month-day-year or day-month-year???
        if ($date[1] == "00") {
            echo "!! Skippped ID={$res["ID"]} with $column={$res[$column]}\n";
            continue;
        }
        else
            $repl = $date[2] . "-" . $date[1] . "-" . $date[0];
        $q = "Update \"$table\" set \"$column\"='$repl' where \"ID\" = {$res["ID"]};";
        echo $q."\n";
        $result = pg_query($db, $q); 
        if (!$result) {
            echo("--  Error: " . pg_last_error($db) . "\n");
            exit;
        }
    }
    else {
        echo "-- Skippped ID={$res["ID"]} with $column={$res[$column]}\n";
    }
}

?>
