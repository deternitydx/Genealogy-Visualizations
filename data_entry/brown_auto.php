<?php
include("../database.php");

/**
  * Automaticaly pull in the brown information into the UVA database
  */


// Helper function: query database, return array of the results
function query_db($q) {
    global $db;
    if (stristr($q, "insert") || stristr($q, "update")) {
        echo $q . "\n";
    }
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

function parse_date($d) {
    global $offs;

    // get the date first
    $junk = array("[", "]", "-");
    $date = str_replace($junk, "", $d);
    $date = str_replace(array_keys($offs), "", $date);
    //list ($date, $off) = explode("-", $date);
    if (stristr($date, "Dec") !== false)
        $date = $date . " 1845";
    else
        $date = $date . " 1846";

    $parts = date_parse($date);
    if ($parts["month"] < 9)
        $parts["month"] = '0' . $parts["month"];
    if ($parts["day"] < 9)
        $parts["day"] = '0' . $parts["day"];

    // get the officiator
    $off = null;
    $date = str_replace($junk, " ", $d);
    $date = str_replace("FebNR", "Feb NR", $date);
    $date = str_replace("FebZC", "Feb ZC", $date);
    $dp = explode(" ", trim($date));
    $poss = $dp[count($dp) -1];
    if (in_array($poss, array_keys($offs)))
        $off = $offs[$poss];

    //echo $parts["year"] ."-". $parts["month"] . "-" . $parts["day"] ." == ". $off . " (".$offs[$off].")  === " . $d . "\n";
    return array($parts["year"] ."-". $parts["month"] . "-" . $parts["day"], $off);
}

//**************************************************************

$db = pg_connect($db_conn_string);

$brown = query_db("Select * from \"Brown\" where \"Progress\" <> 'done';");

//print_r($brown);

// Officiators in Brown
$offs = array(
    "AML" => 25079,
    "BY" => 615,
    "CCR" => 447,
    "DS" => 36761,
    "ETB" => 1351,
    "GAS" => 484,
    "GM" => 15277,
    "HCK" => 5720,
    "IM" => 15728,
    "JS" => 495,
    "JT" => 32267,
    "OH" => 31692,
    "OP" => 425,
    "OS" => 7727,
    "PPP" => 428,
    "WdS" => 1496,
    "WF" => 19111,
    "WH" => 282,
    "WmS" => 8744,
    "Wms" => 8744,
    "WWP" => 34674,
    "ZC" => 12094,
    "NR" => null,
    "NF" => null
);

foreach ($brown as $b) {

    // Insert the Second Annointing
    if ($b["SA"] != "" and $b["SA"] != null) {
        list($d,$o) = parse_date($b["SA"]);

        $res = query_db("insert into \"NonMaritalTempleRites\" (\"Type\", \"PersonID\", \"Date\", \"PlaceID\", \"PrivateNotes\")
                   values ('secondAnnointing', {$b["PersonID"]}, '$d', 18525, 'Imported from Brown') returning *;");

        if ($o != null) {
            $r2 = query_db("insert into \"TempleRiteOfficiators\" (\"NonMaritalTempleRitesID\", \"PersonID\", \"PrivateNotes\") values ({$res["ID"]}, $o, 'Imported from Brown') returning *;");
        }


    }

    // Insert the Adoptions
    if ($b["ASC"] != "" and $b["ASC"] != null) {
        list($d, $o) = parse_date($b["ASC"]);

        if ($o == null)
            $o = "NULL";

        $res = query_db("insert into \"NonMaritalSealings\" (\"Type\", \"AdopteeID\", \"Date\", \"PlaceID\", \"OfficiatorID\", \"PrivateNotes\") values ('adoption', {$b["PersonID"]}, '$d', 18525, $o, 'Imported from Brown');");

    }

    // Insert the Endowments
    if ($b["E"] != "" and $b["E"] != null) {
        list ($d, $o) = parse_date($b["E"]);

        $res = query_db("insert into \"NonMaritalTempleRites\" (\"Type\", \"PersonID\", \"Date\", \"PlaceID\", \"PrivateNotes\") values ('endowment', {$b["PersonID"]}, '$d', 18525, 'Imported from Brown');");

        if ($o != null) {
            $r2 = query_db("insert into \"TempleRiteOfficiators\" (\"NonMaritalTempleRitesID\", \"PersonID\", \"PrivateNotes\") values ({$res["ID"]}, $o, 'Imported from Brown') returning *;");
        }


    }

}


?>
