<?php
include("../database.php");

/**
  * Automaticaly pull the office information from other tables into the PersonOffice table
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
//    if (count($ret) == 1)
//        $ret = $ret[0];
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

$people = query_db("Select \"ID\" from \"Person\";");
//$people = array (array("ID"=> 615));
$ii = 0;

// Mappings
$offs = array(
    "FirstPresidency" => 1,
    "Apostle" => 2,
    "Seventy" => 3,
    "HighPriest" => 4,
    "Elder" => 5,
    "Teacher" => 6,
    "Priest" => 7,
    "Deacon" => 8,
    "Bishop" => 9,
    "Patriarch" => 10,
    "ReliefSociety" => 11,
    "TempleWorker" => 12,
    "Midwife" => 13,
    "FemaleReliefSocietyNauvoo" => 14
);

foreach ($people as $p) {

    $id = $p["ID"];

    // offices will be a list of OFFICE => dates
    // then, we will sort the dates for each office
    // then, we will insert with at least dates
    $offices = array();

    $res = query_db("Select \"Date\", \"OfficeWhenPerformed\" as \"Office\"
                        from \"NonMaritalTempleRites\"
                        where \"PersonID\" = $id and \"OfficeWhenPerformed\" is not null;");
    // if success, then add them
    if ($res) {
        foreach ($res as $o) {
            // add office
            if (!isset($offices[$o["Office"]]))
                $offices[$o["Office"]] = array();
            if ($o["Date"] != null && $o["Date"] != "")
                array_push($offices[$o["Office"]], $o["Date"]); 
        }
    }

    $res = query_db("Select \"Date\", \"OfficeWhenPerformed\" as \"Office\"
                        from \"NonMaritalSealings\"
                        where \"AdopteeID\" = $id and \"OfficeWhenPerformed\" is not null;");
    // if success, then add them
    if ($res) {
        foreach ($res as $o) {
            // add office
            if (!isset($offices[$o["Office"]]))
                $offices[$o["Office"]] = array();
            if ($o["Date"] != null && $o["Date"] != "")
                array_push($offices[$o["Office"]], $o["Date"]); 
        }
    }
        
    $res = query_db("Select m.\"MarriageDate\" as \"Date\", pm.\"OfficeWhenPerformed\" as \"Office\"
                        from \"PersonMarriage\" pm, \"Marriage\" m
                        where pm.\"PersonID\" = $id and pm.\"MarriageID\" = m.\"ID\"
                            and pm.\"OfficeWhenPerformed\" is not null;");
    // if success, then add them
    if ($res) {
        foreach ($res as $o) {
            // add office
            if (!isset($offices[$o["Office"]]))
                $offices[$o["Office"]] = array();
            if ($o["Date"] != null && $o["Date"] != "")
                array_push($offices[$o["Office"]], $o["Date"]); 
        }
    }

    // Put the offices into the database
    foreach($offices as $name => $dates) {
        sort($dates);
        //echo $dates[0] . " to " . $dates[count($dates)-1] . "\n";
        //print_r($dates);
        query_db("insert into \"PersonOffice\" (\"PersonID\", \"OfficeID\", \"From\", \"FromStatus\",
            \"To\", \"ToStatus\", \"PrivateNotes\") values ($id, {$offs[$name]}, '{$dates[0]}', 
            'atLeastBy', '". $dates[count($dates)-1] ."', 'atLeastUntil', 'Auto generated from previous Office When Performed fields.');");
    }
}



?>
