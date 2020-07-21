<?php
/**
 * Replay Update statements into the database
 *
 * This file is useful to replay a list of update statements, ie from the log, into the database.
 * If something happens, pull out only the UPDATE .. RETURNING *; statements out and replay them
 * here by passing that file as an argument to this script.  The script will then try to replay
 * each and every UPDATE.  If the UPDATE fails, then the script will convert that UPDATE into an
 * INSERT statement and try to insert that data instead.  If the INSERT statement fails, it will
 * just print (stdout) the error message and give up on replaying that line.
 *
 * The list of statements executed by this script are printed to stdout so that they may be
 * read and grep-ed to see what the script had done.
 */

if ($argc < 2) {
    echo ("Please supply an argument: php replay_history.php FILENAME.SQL\n");
    exit();
}
$statements = file($argv[1], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

include("../database.php");
$db = pg_connect($db_conn_string);

foreach ($statements as $statement) {

    $q = $statement;
    echo $q . "\n";
    $result = pg_query($db, $q);
    if (!$result) {
        echo("--  Error: " . pg_last_error($db) . "\n");
        continue;
    }

    // If the update didn't work, then we need to change it into an insert
    if (pg_num_rows($result) == 0) {
        echo "--  Didn't affect any rows\n";

        // Create the insert statement for this row
        // 1. grab the pieces of the update statement with regex
        preg_match("/UPDATE ([a-zA-Z\"\.]*)  SET (\([a-zA-Z\",]*\)) = (\([0-9a-zA-Z',]*\))/", $q, $pieces);
        if (count($pieces) == 4) {
            // 2. if there are enough pieces grabbed, put them into an insert
            $ins = "INSERT INTO " . $pieces[1] . " " . $pieces[2] . " VALUES " . $pieces[3] . ";";

            // Print the insert statement for sanity
            echo $ins . "\n";

            // Do the insert
            $result = pg_query($db, $ins);
            if (!$result) {
                echo("--  Error on insert: " . pg_last_error($db) . "\n");
                continue;
            }

        }

        continue;
    }

    echo "--  Success\n";
}

?>
