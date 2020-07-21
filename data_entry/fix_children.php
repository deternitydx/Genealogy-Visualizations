<?php
if ($argc < 2) {
    echo ("Please supply an argument: php replay_history.php FILENAME.txt\n");
    exit();
}
$statements = file($argv[1], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

include("../database.php");
$db = pg_connect($db_conn_string);

foreach ($statements as $statement) {

    list($p, $m) = explode(",", $statement);

    $result = pg_query($db, "select count(*) from \"Marriage\" where \"ID\" = $m");
    if (!$result) {
        echo("--  Error: " . pg_last_error($db) . "\n");
        continue;
    }
    $rm = pg_fetch_row($result);
    $result = pg_query($db, "select count(*) from \"Person\" where \"ID\" = $p");
    if (!$result) {
        echo("--  Error: " . pg_last_error($db) . "\n");
        continue;
    }
    $rp = pg_fetch_row($result);
    if ($rm[0] > 0 && $rp[0] > 0) {
        $q = "Update \"Person\" set \"BiologicalChildOfMarriage\"=$m where \"ID\" = $p;";
        echo $q."\n";
        $result = pg_query($db, $q); 
    }
    else {
        echo "-- Skipped id $p with parents $m\n";
    }
}

?>
