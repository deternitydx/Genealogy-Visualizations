<?php
include("../database.php");

/**
  * Other considerations when merging that are not covered here:
  *  1. Merging the data from the Person table (conflicting birth/death dates, places)
  *     that might have been problematic.  I have only seen ~1 year difference in the
  *     dates for a person, and the places have usually been the same (intuitively),
  *     but different ids, because one place has a little more information (such as "USA")
  *  2. Collecting the BYU IDs that have been merged together, if there were any.  This
  *     scheme assumes that the merge id is the lowest possible ID, which would be the ID we
  *     created from BYU's entity. So far, all the merges have been from UVA creations through
  *     the Brown process, but if we actually merge two people from BYU, we should maintain
  *     compatibility with them.  I'd suggest creating a BYUID table, with two columns:
  *        UVA_ID | BYU_ID
  */


// Helper function: query database, return array of the results
function query_db($q) {
    global $db;
    $result = pg_query($db, $q);
    if (!$result) {
        echo "Error: " . pg_last_error($db) . "\n";
        return false;
    }
    $ret = pg_fetch_all($result);
    if (count($ret) == 1)
        $ret = $ret[0];
    return $ret;
}

//**************************************************************

/**
 * Requires either GET parameters:
 *   merge: id that is the final ending ID
 *   dups: duplicate ids for the person, comma separated
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
        die("WEB: No data to merge");
    }

} else {
    // Get command line variables
    if ($argc >= 3) {
        $merge = $argv[1];
        for ($i = 2; $i < $argc; $i++)
            array_push($dups, $argv[$i]);
    } else {
        die("CLI: No data to merge");
    }
}

// Keep the original people, just in case
$originals = array();

$originals[$merge] = query_db("select * from \"Person\" where \"ID\"=$merge;");
foreach ($dups as $dup) {
    $originals[$dup] = query_db("select * from \"Person\" where \"ID\"=$dup;");
}

// Do the merging
foreach ($dups as $dup) {

    // Repoint all marriages to the main person
    query_db("update \"PersonMarriage\" set \"PersonID\"=$merge where \"PersonID\"=$dup;");

    // Repoint all sealings to the main person
    query_db("update \"NonMaritalSealings\" set \"AdopteeID\"=$merge where \"AdopteeID\"=$dup;");

    // Repoint all rites to the main person
    query_db("update \"NonMaritalTempleRites\" set \"PersonID\"=$merge where \"PersonID\"=$dup;");
    query_db("update \"NonMaritalTempleRites\" set \"AnnointedToID\"=$merge where \"AnnointedToID\"=$dup;");
    
    // Repoint all brown entries to the main person
    query_db("update \"Brown\" set \"PersonID\"=$merge where \"PersonID\"=$dup;");

    // Repoint all name entries to the main person
    query_db("update \"Name\" set \"Type\"='alternate' where \"PersonID\"=$dup;");
    query_db("update \"Name\" set \"PersonID\"=$merge where \"PersonID\"=$dup;");

    // Repoint all memberships to the main person
    query_db("update \"ChurchOrgMembership\" set \"PersonID\"=$merge where \"PersonID\"=$dup;");

    // Repoint all offices and office officiators to the main person
    query_db("update \"PersonOffice\" set \"PersonID\"=$merge where \"PersonID\"=$dup;");
    query_db("update \"PersonOffice\" set \"OfficiatorID1\"=$merge where \"OfficiatorID1\"=$dup;");
    query_db("update \"PersonOffice\" set \"OfficiatorID2\"=$merge where \"OfficiatorID2\"=$dup;");
    query_db("update \"PersonOffice\" set \"OfficiatorID3\"=$merge where \"OfficiatorID3\"=$dup;");

    // Repoint all temple rite officiators to the main person
    query_db("update \"TempleRiteOfficiators\" set \"PersonID\"=$merge where \"PersonID\"=$dup;");

    // Repoint all company roster entries to the main person (not used as of 9/2015)
    query_db("update \"CompanyRoster\" set \"PersonID\"=$merge where \"PersonID\"=$dup;");

    // Repoint all image entries to the main person (not used as of 9/2015)
    query_db("update \"Images\" set \"PersonID\"=$merge where \"PersonID\"=$dup;");

    // Repoint all land plot entries to the main person (not used as of 9/2015)
    query_db("update \"LandPlots\" set \"PersonID\"=$merge where \"PersonID\"=$dup;");

    // Repoint all occupations to the main person (not used as of 9/2015)
    query_db("update \"PersonOccupation\" set \"PersonID\"=$merge where \"PersonID\"=$dup;");
}


// Do some cleanups

// Names
$nameid = query_db("select \"ID\" from \"Name\" where \"PersonID\"=$merge and \"Type\" = 'authoritative' limit 1;");
$nameid = $nameid["ID"];

// Drop down to one authoritative name:
query_db("update \"Name\" set \"Type\"='alternate' where \"PersonID\"=$merge and \"Type\" = 'authoritative' and \"ID\" <> $nameid;");

// Combine the notes
$prnotes = "";
$punotes = "";
foreach ($originals as $original) {
    $prnotes .= $original["PrivateNotes"];
    $punotes .= $original["PublicNotes"];
}
query_db("update \"Person\" set \"PrivateNotes\"='".pg_escape_string($prnotes)."' where \"ID\"=$merge;");
query_db("update \"Person\" set \"PublicNotes\"='".pg_escape_string($punotes)."' where \"ID\"=$merge;");

// Insert the merged records into the merged table
foreach ($originals as $key => $original) {
    if ($key !== $merge ) {
        query_db("insert into \"Merge\" (\"PersonID\", \"MergedID\", \"BYUID\", \"LostData\") values ($merge, $key, {$original["BYUID"]}, '".pg_escape_string(json_encode($original))."');");
    }
}

// Delete the old person entries that should not be around anymore
foreach ($dups as $dup) {
    query_db("delete from \"Person\" where \"ID\"=$dup;");
}

echo "Successfully merged into $merge\n";
?>
