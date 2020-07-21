<?php
include("../database.php");

/**
  */
$testing = false;

// Helper function: query database, return array of the results
function query_db($q) {
    global $db, $testing;
    echo $q . "\n";
    if (!$testing || strpos($q, "select") !== false) {
        $result = pg_query($db, $q);
        if (!$result) {
            echo "Error: " . pg_last_error($db) . "\n";
            return false;
        }
        $ret = pg_fetch_all($result);
        if (count($ret) == 1)
            $ret = $ret[0];
    } else {
        // Update, insert, or delete: just print (for testing)
        echo "  Not queried (testing)\n";
        $ret = array();
    }
    return $ret;
}

//**************************************************************

/**
 * Requires either GET parameters:
 *   merge: id that is the final ending ID
 *   dups: duplicate ids for the marriage, comma separated
 * or the list of all on the command line, such as:
 *   php merge.php merge_id dup1 dup2 dup3 ...
 */

$db = pg_connect($db_conn_string);

$merge = null;
$dups = array();

// Get the proper variables (CLI or WEB)
if (PHP_SAPI != "cli") {
    // Get GET variables
    if (isset($_GET["merge"]) && isset($_GET["dups"])) {
        $merge = $_GET["merge"];
        $dups = explode(",", $_GET["dups"]);
    } else {
        die("WEB: No data to merge\n");
    }

} else {
    // Get command line variables
    if ($argc >= 3) {
        $merge = $argv[1];
        for ($i = 2; $i < $argc; $i++)
            array_push($dups, $argv[$i]);
    } else {
        die("CLI: No data to merge\nUsage: php merge_marriage.php main_id dup1_id dup2_id ...\n");
    }
}

// Keep the original people, just in case
$originals = array();


$originals[$merge] = query_db("select * from \"Marriage\" where \"ID\"=$merge;");
foreach ($dups as $dup) {
    $originals[$dup] = query_db("select * from \"Marriage\" where \"ID\"=$dup;");
    $originals[$dup]["Participants"] = query_db("select * from \"PersonMarriage\" where \"MarriageID\"=$dup;");
    $originals[$dup]["Children"] = query_db("select \"ID\", \"BYUID\",\"BiologicalChildOfMarriage\" from \"Person\" where \"BiologicalChildOfMarriage\"=$dup;");
}

// Do the merging
foreach ($dups as $dup) {

    // Repoint all children to the merged marriage
    query_db("update \"Person\" set \"BiologicalChildOfMarriage\" = $merge where \"BiologicalChildOfMarriage\" = $dup;");
    
    // Repoint all Non-Marital Sealings to the merged marriage
    query_db("update \"NonMaritalSealings\" set \"MarriageID\" = $merge where \"MarriageID\" = $dup;");
    query_db("update \"NonMaritalSealings\" set \"MarriageProxyID\" = $merge where \"MarriageProxyID\" = $dup;");
}


// Do some cleanups

// Combine the notes
$prnotes = "";
$punotes = "";
foreach ($originals as $original) {
    $prnotes .= $original["PrivateNotes"];
    $punotes .= $original["PublicNotes"];
}
query_db("update \"Marriage\" set \"PrivateNotes\"='".pg_escape_string($prnotes)."' where \"ID\"=$merge;");
query_db("update \"Marriage\" set \"PublicNotes\"='".pg_escape_string($punotes)."' where \"ID\"=$merge;");

// Insert the merged records into the merged table
foreach ($originals as $key => $original) {
    if ($key !== $merge ) {
        query_db("insert into \"MergeMarriage\" (\"MarriageID\", \"MergedID\", \"BYUID\", \"LostData\") values ($merge, $key, {$original["BYUID"]}, '".pg_escape_string(json_encode($original))."');");
    }
}

// Delete the old person entries that should not be around anymore
foreach ($dups as $dup) {
    query_db("delete from \"Marriage\" where \"ID\"=$dup;");
}

echo "Successfully merged into $merge\n";
?>
