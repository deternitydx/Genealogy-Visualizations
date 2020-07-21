<?php
include("../database.php");
header('Access-Control-Allow-Origin: *');

/****
 * We must generate a JSON file following the structure below
 *
    {
        "marriageUnits":[
                {"id": 1234, "name":"MU1"},
                {"id": 1231, "name":"MU2"},
                {"id": 1232, "name":"MU3"},
                {"id": 1233, "name":"MU4"},
                {"id": 1235, "name":"MU5"},
                {"id": 1236, "name":"MU6"},
                {"id": 1237, "name":"MU7"},
                {"id": 1238, "name":"MU8"},
                {"id": 1239, "name":"MU9"},
                {"id": 1260, "name":"MU10"}

        ],
        "people":[
                {"source":1234,"target":1232, "gender":"M", "name": "Smith, John"},
                {"source":1231,"target":1232, "gender":"F", "name": "Jones, Mary"},
                {"source":1232,"target":1236, "gender":"M", "name": "Smith, Tom"},
                {"source":1233,"target":1236, "gender":"F", "name": "Bowls, Debra"},
                {"source":1235,"target":1236, "gender":"F", "name": "Carter, Rebekah"},
                {"source":1236,"target":1237, "gender":"F", "name": "Smith, Rachel"},
                {"source":1236,"target":1238, "gender":"M", "name": "Smith, Matthew"},
                {"source":1236,"target":1239, "gender":"F", "name": "Smith, Martha"},
                {"source":1236,"target":1260, "gender":"F", "name": "Smith, Christina"}
        ]
    }
 */


// Steps to take
// ==================
// 1. Query the database for the women that are in each husband's marriages (we can simplify the muliple queries below into one)
//    - These will be the in edges for each marriage unit
// 2. Query the database for the children that are in each husband's marriages
//    - These will be the out edges for each marriage unit
// 3. List each husband as the ID for the marriage unit and name as the MU name
// 4. For each in-edge of each MU, check to see if they are from another marriage we have found. If so, use that id as their
//       source.  If not, then create a dummy marriage with their last name as their source and add the marriage to the list.
//       NOTE: if two people come from the same marriage (BiologicalChildOfMarriage DB entry will be helpful here, actually)
// 5. Ignore fixing up out edges.  In that case, we won't display the edge, as people don't have to get married, and we likely
//       don't have that data.  The in edges will cover all the cases of finding the relations in our data.
// 6. We need to fix the out edges in the cases where they may go to the same out marriage.  That is, the children of two different
//       people may end up married in the end.  We need a way to approach this.


// LEVELS Steps to take
//
// After adding everyone, but before adding the dummy nodes, put everyone without a source into the left edge and everyone 
// without a target to the right edge.  For each left-edge, look up parent's marriage and participants and add.  For each
// right-edge, look up children's marriage and participants to add.  Attach appropriately.  
//  -- repeat for each level (add to edge, process edges)

header('Content-type: application/json');

$ids = array( 615);
if (isset($_GET["id"]))
    $ids = explode(",",$_GET["id"]);
$levels = 0;
if (isset($_GET["levels"]))
    $levels = $_GET["levels"];
$showall = false;
if (isset($_GET["showall"]))
    $showall = true;
$orientation = "male";
if (isset($_GET["view"]) && $_GET["view"] == "female")
    $orientation = "female";
$goforwards = true;
$gobackwards = true;
if (isset($_GET["forwards"])) $gobackwards = false;
if (isset($_GET["backwards"])) $goforwards = false;

$marriageUnits = array();
$people = array();
$currentLevel = 0;

$db = pg_connect($db_conn_string);

// Insert this person with either source or target (direction) pointing to this id
function insertPerson($person, $direction, $id) {
    global $people, $marriageUnits, $ids, $currentLevel, $db;
    
    // If they are not already there, add them
    if (!isset($people[$person["ID"]]))
        $people[$person["ID"]] =  array(
            "id"=>$person["ID"], 
            "source"=>array(), 
            "target"=>array(), 
            "gender"=>$person["Gender"], 
            "name"=>$person["Last"] . ", " . $person["First"] . " " . $person["Middle"], 
            "childOf"=>$person["BiologicalChildOfMarriage"], 
            "birthdate"=>$person["BirthDate"], 
            "deathdate" => $person["DeathDate"]
        );

        
    // If they don't have a parent marriage, then set this field to -1
    if (!isset($person["BiologicalChildOfMarriage"]) || $person["BiologicalChildOfMarriage"] == "") {
            $people[$person["ID"]]["childOf"] = -1;
    }

    //TODO Add a query here to get the person's marriages and add them with from:marriagedate -> to:deathdate|divorcedate|canceldate under
    //              $people[$person["ID"]["marriages"][topersonid] = [start=> date, end=>date]
    //     see *** below
    
    // Get the other marriages for this person
    $other_type = "Wife";
    if (is_masculine()) $other_type = "Husband";
    $this_type = "Wife";
    if (is_feminine()) $this_type = "Husband";
    $result = pg_query($db, "select distinct p.\"ID\", m.\"MarriageDate\", m.\"DivorceDate\", m.\"CancelledDate\" from (select pm.* from \"PersonMarriage\" pm where pm.\"PersonID\"=".$person["ID"]." and pm.\"Role\" = '$this_type') mid, \"PersonMarriage\" pmw, \"Person\" p, \"Marriage\" m where mid.\"MarriageID\"=pmw.\"MarriageID\" and pmw.\"Role\" = '$other_type' and pmw.\"PersonID\" = p.\"ID\" and m.\"ID\" = pmw.\"MarriageID\" order by m.\"MarriageDate\" ASC;");
    $arr = pg_fetch_all($result);
    $people[$person["ID"]]["marriages"] = array();
    if ($arr) {
        foreach($arr as $mar) {
            if (!isset($people[$person["ID"]]["marriages"][$mar["ID"]])) {
                $people[$person["ID"]]["marriages"][$mar["ID"]] = array(
                    "marriagedate" => $mar["MarriageDate"],
                    "divorcedate" => $mar["DivorceDate"],
                    "canceldate" => $mar["CancelledDate"]
                );
            }
        }
    }

    // Set the level on the marriage if we need one
    $level = $currentLevel + 1;
    if (in_array($person["ID"], $ids))
        $level = 0;

    // If this person is the gender of the marriage units, create a marriage unit for them
    if ((is_masculine() && $person["Gender"] == "Male") || (is_feminine() && $person["Gender"] == "Female")) {
        if (!array_key_exists($person["ID"], $marriageUnits))
            $marriageUnits[$person["ID"]] =  array(
                "id"=>$person["ID"], 
                "name"=>$person["Last"] . ", " . $person["First"] . " " . $person["Middle"], 
                "level"=>$level,
                "start"=>$person["BirthDate"], 
                "end"=>$person["DeathDate"]);

        if (!in_array($person["ID"], $people[$person["ID"]]["target"]))
            array_push($people[$person["ID"]]["target"], $person["ID"]);
    }
    
    // Set the direction we had asked for to the proper id
    if ($direction !== null && $id !== null)
        if (!in_array($id, $people[$person["ID"]][$direction]))
            array_push($people[$person["ID"]][$direction], $id);
}

function is_masculine() {
    global $orientation;
    return ($orientation == 'male');
}

function is_feminine() {
    return !is_masculine();
}

function get_role() {
    if (is_masculine()) return "Husband";
    else return "Wife";
}

function get_other_role() {
    if (is_masculine()) return "Wife";
    else return "Husband";
}

function processID($id) {
    global $db, $marriageUnits, $people, $currentLevel, $ids;
    // Get the person's information    
    $result = pg_query($db, "SELECT p.\"ID\", n.\"First\", n.\"Middle\", n.\"Last\", p.\"Gender\", p.\"BirthDate\", p.\"DeathDate\", p.\"BiologicalChildOfMarriage\" FROM public.\"Person\" p, public.\"Name\" n  WHERE p.\"ID\"=$id
         AND p.\"ID\" = n.\"PersonID\" AND n.\"Type\"='authoritative'");
    if (!$result) {
        echo "An error occurred.\n";
        exit;
    }
    $arr = pg_fetch_all($result);
    $person = $arr[0];

    // Set the level on the marriage if we need one
    $level = $currentLevel;
    if (in_array($id, $ids))
        $level = 0;
    
    // If this person is the gender of the marriage units, create a marriage unit for them
    if ((is_masculine() && $person["Gender"] == "Male") || (is_feminine() && $person["Gender"] == "Female")) {
        if (!isset($marriageUnits[$id]))
            $marriageUnits[$id] = array("id"=>$id, "name"=>$person["Last"] . ", " . $person["First"] . " " . $person["Middle"], "level"=>$level,
                                        "start"=>$person["BirthDate"], "end"=>$person["DeathDate"]);
        insertPerson($person, "target", $id);
    } else {
        // Not sure if we want this, but we'll see for now
        insertPerson($person, null, null);
    }

    // *** This would be a good query to use
    // Get the other marriage members for this person
    $other_type = "Wife";
    if (is_feminine()) $other_type = "Husband";
    $this_type = "Wife";
    if (is_masculine()) $this_type = "Husband";
    $result = pg_query($db, "select distinct p.\"ID\", n.\"First\", n.\"Middle\", n.\"Last\", m.\"MarriageDate\", p.\"Gender\", p.\"BirthDate\", p.\"DeathDate\", p.\"BiologicalChildOfMarriage\" from (select pm.* from \"PersonMarriage\" pm where pm.\"PersonID\"=$id and pm.\"Role\" = '$this_type') mid, \"PersonMarriage\" pmw, \"Person\" p, \"Marriage\" m, \"Name\" n where mid.\"MarriageID\"=pmw.\"MarriageID\" and pmw.\"Role\" = '$other_type' and n.\"PersonID\" = pmw.\"PersonID\" and pmw.\"PersonID\" = p.\"ID\" and n.\"Type\" = 'authoritative' and m.\"ID\" = pmw.\"MarriageID\" order by m.\"MarriageDate\" ASC;");
    if (!$result) {
        echo "An error occurred.\n";
        exit;
    }
    $arr = pg_fetch_all($result);
    if($arr) {
        foreach($arr as $wife) {
            insertPerson($wife, "target", $id);    
        }
    }

    // Get the children for this person's marriage 
    $result = pg_query($db, "select distinct p.\"ID\", n.\"First\", n.\"Middle\", n.\"Last\", p.\"Gender\", p.\"BiologicalChildOfMarriage\", p.\"BirthDate\", p.\"DeathDate\" from (select pm.\"MarriageID\" from \"PersonMarriage\" pm where pm.\"PersonID\"=$id and pm.\"Role\" = '$this_type') mid, \"Person\" p, \"Name\" n where mid.\"MarriageID\"=p.\"BiologicalChildOfMarriage\" and n.\"PersonID\" = p.\"ID\" and n.\"Type\" = 'authoritative' order by p.\"BirthDate\" ASC;");
    if (!$result) {
        echo "An error occurred.\n";
        exit;
    }
    $arr = pg_fetch_all($result);
    
    // Do things for each child
    foreach($arr as $child) {
            insertPerson($child, "source", $id);
    }

}

// **********************************************************************************************************


// For each person id, get their spouses and children
foreach($ids as $id) {
    processID($id);
}


// For each level, we'll check edges and look for more people
for ($curlevel = 0; $curlevel < $levels; $curlevel++) {
    $currentLevel++;
    $leftedge = array();
    $rightedge = array();
    // If there is any person on the edge, we will keep their ids for more use:
    foreach($people as $i => $person) {
        if ($gobackwards && empty($person["source"]))
            array_push($leftedge, $i);
        if ($goforwards && empty($person["target"]))
            array_push($rightedge, $i);
    }

    foreach ($leftedge as $i) {
        // Look up parental marriage's husband id and get parents
        if ($people[$i]["childOf"] != -1)  { // we know they have a parent
            // Start by getting all parents in that marriage
            $mid = $people[$i]["childOf"];
            // Get the parents of the same gender as the view we're currently filling
            $result = pg_query($db, "select pm.\"PersonID\" as \"ID\" from \"PersonMarriage\" pm where pm.\"MarriageID\"=$mid and pm.\"Role\" = '". get_role() ."';");
            if (!$result) {
                 echo "An error occurred.\n";
                 exit;
            }
            $arr = pg_fetch_all($result);
            foreach($arr as $parent) {
                // this doesn't work for this particular application
                // insertPerson($parent, "target", $id);    

                // I think this is what we really want
                processID($parent["ID"]);
            }
            
        }
        // Look up this person, too, just in case they have other spouses or children
        processID($people[$i]["id"]);
    }

    foreach ($rightedge as $i) {
        // Look up child marriage's parent role (husband or wife)
        $result = pg_query($db, "select distinct pm.\"PersonID\" as \"ID\" from \"PersonMarriage\" pm, \"PersonMarriage\" jn where pm.\"MarriageID\"=jn.\"MarriageID\" and jn.\"PersonID\"=".$people[$i]["id"]." and pm.\"Role\" = '". get_role() ."';");
        if (!$result) {
             echo "An error occurred looking up children marriage.\n";
             exit;
        }
        $arr = pg_fetch_all($result);
        foreach($arr as $parent) {
            processID($parent["ID"]);
        }
        processID($people[$i]["id"]);
    }
}


/****
 * Clean up and add dummy nodes to those we don't know about
 */
$currentLevel++;
$dummyID = 1000000;
$known = array();
foreach($people as $i => $person) {
        if (empty($person["source"])) {
            if ($person["childOf"] != -1 && array_key_exists($person["childOf"], $known) && !in_array($known[$person["childOf"]], $people[$i]["source"]))
                    array_push($people[$i]["source"], $known[$person["childOf"]]);
            else {
                $marriageUnits[$dummyID] = array("id"=>$dummyID, "name"=>"", "level"=>$currentLevel, "start"=>$person["birthdate"], "end"=>$person["end"]);
                if (!in_array($dummyID, $people[$i]["source"]))
                    array_push($people[$i]["source"], $dummyID);
                $known[$person["childOf"]] = $dummyID;
                $dummyID++;
            }
        }

        if (empty($person["target"])) {
            $needDummy = true;
            // TODO Fix IF statement
            // check to see if this person is the opposite gender, and if so, then let's query to see if they're married one of the main people we have
            if ((is_masculine() && $person["gender"] == "Female") || (is_feminine() && $person["gender"] == "Male")) {
                $result = pg_query($db, "SELECT pm.\"PersonID\" as \"SigOtherID\" FROM (SELECT m.\"MarriageID\" FROM public.\"PersonMarriage\" as m  WHERE \"PersonID\"={$person['id']} AND \"Role\" = '".get_other_role()."') m, \"PersonMarriage\" pm WHERE pm.\"MarriageID\" = m.\"MarriageID\" and pm.\"Role\" = '".get_role()."';");
                if (!$result) {
                    echo "An error occurred.\n";
                    exit;
                }
                $arr = pg_fetch_all($result);
                $sigOtherID = null;
                foreach ($arr as $target) {
						$sigOther = $target["SigOtherID"];
                        if (array_key_exists($sigOtherID, $marriageUnits)) {
                                if (!in_array($sigOtherID, $people[$i]["target"]))
                                    array_push($people[$i]["target"],$sigOtherID);
                                $needDummy = false;
                        }
                }
                // If there is a husband ID, let's use his id as the target to catch some other women
                if ($needDummy && $sigOtherID != null) {
                	$marriageUnits[$sigOtherID] = array("id"=>$sigOtherID, "name"=>"", "level"=>$currentLevel, "start"=>$person["birthdate"], "end"=>$person["end"]);
                    if (!in_array($sigOtherID, $people[$i]["target"]))
                	    array_push($people[$i]["target"], $sigOtherID);
                	$needDummy = false;
            	}
            }
            
            
            if ($needDummy) {
                $marriageUnits[$dummyID] = array("id"=>$dummyID, "name"=>"", "level"=>$currentLevel, "start"=>$person["birthdate"], "end"=>$person["end"]);
                if (!in_array($dummyID, $people[$i]["target"]))
                    array_push($people[$i]["target"], $dummyID);
                $dummyID++;
            }
        }

        if (empty($people[$i]["target"]) || empty($people[$i]["source"])) {
            // Still have an issue, so print this for now
            //print_r($marriageUnits);
            echo "Problem with this person's target or source:";
            print_r($person);
            //die ("Problem with person not having a source or target");
        }
}

echo "{ \"marriageUnits\":[";

$i = 0;
foreach ($marriageUnits as $unit) {
        echo "{ \"id\":" . $unit["id"] . ", \"name\":\"" . $unit["name"] . "\", \"start\":\"" . $unit["start"] . "\", \"end\":\"" . $unit["end"] . "\", \"level\": " . $unit["level"] . "}";
        if ($i++ < count($marriageUnits) -1) echo ",";
}

echo "], \"people\": [";

$fixed = array();
foreach ($people as $k => $person) {
    if (!empty($person["id"]) && $person["id"] != "")
        $fixed[$k] = $person;
}
$i = 0;
foreach ($fixed as $person) {
            echo "\n{ \n\"id\":" . $person["id"] . ", \n\"name\":\"" . $person["name"] . "\", \n\"source\": [" . implode(",",$person["source"]). "], \n\"target\": [" .implode(",",$person["target"]) ."], \n\"gender\":\"".$person["gender"] ."\", \n\"birthdate\":\"".$person["birthdate"]."\", \n\"deathdate\":\"".$person["deathdate"]."\", \n\"childOf\":\"".$person["childOf"]."\",\n\"marriages\":".json_encode($person["marriages"], JSON_PRETTY_PRINT)."}";
            if ($i++ < count($fixed) -1) echo ",\n";
}

echo "]}";


?>

