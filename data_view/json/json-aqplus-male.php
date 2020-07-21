<?php
// This file produces the GEXF format for the graph of all marriages and
// people.  This format should in the future produce easy ways of defining
// dynamic graphs, as its current format supports dynamic definitions.  For the
// full syntax, see http://gexf.net/format/.

/***
 * Example Format
 *
 * <?xml version="1.0" encoding="UTF-8"?>
 * <gexf xmlns="http://www.gexf.net/1.2draft" version="1.2">
 *     <meta lastmodifieddate="2009-03-20">
 *         <creator>Gexf.net</creator>
 *         <description>A hello world! file</description>
 *     </meta>
 *     <graph mode="static" defaultedgetype="directed">
 *          <nodes>
 *               <node id="0" label="Hello" />
 *               <node id="1" label="Word" />
 *          </nodes>
 *          <edges>
 *               <edge id="0" source="0" target="1" />
 *          </edges>
 *     </graph>
 * </gexf>
 ***/


// Get the content from the database
//
// Need an array of marriages (nodes), submarriages (in case man/woman married multiple times), and people (links)
// For each male person with a child of marriageid:
//   Get their marriages (as husband)
//   Add person as a link from birth marriage to married-to marriage (use submarriages)
// Combine submarriages with same husband/wife pair or same husband (latter is better)
// Actually, could probably just use husband ID as marriage ID in the array and gexf, but would need to look up husband ID for child of marriage ID for each person (easy join)
//
//  select p.*, pm.PersonID from Person p, Marriage m, PersonMarriage pm where p.Gender='Male' and p.ChildOfMarriageID=m.ID and m.ID=pm.MarriageID and pm.Role = 'Husband';
//
// For each result
//  add marriage if it doesn't exist (p.ID, person's name as marriage label)
//  add link from pm.PersonID to p.ID (child->marriageof)
// Select on wives (similar select statement)
//  for each wife, add them to the marriages they've married into

$males = array();
$newmales = array();
$nodes = array();
$edges = array();
$dummyCounter = 100000000;
$iterations = 0;
$maxIter = 0;

$office = null;
$officeID = null;
if (isset($_GET["office"]))
    $office = $_GET["office"];
else if (isset($argv[1]))
    $office = $argv[1];

if (isset($_GET["level"]))
    $maxIter = $_GET["level"];
else if (isset($argv[2]))
    $maxIter = $argv[2];

$date = null;
if (isset($_GET["date"]))
    $date = $_GET["date"];
else if (isset($argv[3]))
    $date = $argv[3];


include("../../database.php");
$db = pg_connect($db_conn_string);

$creator = "Command Line";
if (isset($_SERVER['REQUEST_URI']))
    $creator = $_SERVER['REQUEST_URI'];

// Get the Office ID
if ($office != null) {
    $result = pg_query($db, "select \"ID\" from \"Office\" where \"Name\" = '$office' limit 1;");
    if (!$result) {
        exit;
    }
    $tmp =  pg_fetch_array($result);
    $officeID = $tmp["ID"];
}


// Query for all the main gender in the AQ
$result = pg_query($db, "SELECT DISTINCT p.\"ID\",n.\"First\",n.\"Middle\",n.\"Last\",p.\"BirthDate\",p.\"DeathDate\",
    p.\"Gender\", p.\"BirthPlaceID\", pm.\"PersonID\" as \"ChildOf\", m.\"Type\"
    FROM public.\"Person\" p INNER JOIN public.\"Name\" n ON (p.\"ID\"=n.\"PersonID\" AND n.\"Type\"='authoritative') 
    LEFT OUTER JOIN public.\"PersonMarriage\" pm ON (pm.\"MarriageID\" = p.\"BiologicalChildOfMarriage\" AND pm.\"Role\" = 'Husband')
    INNER JOIN public.\"ChurchOrgMembership\" c ON (c.\"PersonID\" = p.\"ID\") INNER JOIN public.\"Marriage\" m ON m.\"ID\" = pm.\"MarriageID\"
    WHERE p.\"Gender\" = 'Male' AND c.\"ChurchOrgID\" = 1
    ORDER BY p.\"ID\" asc");
if (!$result) {
    exit;
}

process_results($result);

$datestr = "";
if ($date != null)
    $datestr = "AND m.\"MarriageDate\" <= '$date'";
// Query for all the secondary gender in the AQ
$result = pg_query($db, "SELECT DISTINCT p.\"ID\",n.\"First\",n.\"Middle\",n.\"Last\",p.\"BirthDate\",p.\"DeathDate\",
    p.\"Gender\", p.\"BirthPlaceID\", pm.\"PersonID\" as \"ChildOf\", m.\"SpouseID\", m.\"Type\"
    , m.\"MarriageDate\", m.\"DivorceDate\", m.\"CancelledDate\"
    FROM public.\"Person\" p INNER JOIN public.\"Name\" n ON (p.\"ID\"=n.\"PersonID\" AND n.\"Type\"='authoritative') 
    LEFT OUTER JOIN public.\"PersonMarriage\" pm ON (pm.\"MarriageID\" = p.\"BiologicalChildOfMarriage\" AND pm.\"Role\" = 'Husband')
    INNER JOIN public.\"ChurchOrgMembership\" c ON (c.\"PersonID\" = p.\"ID\")
    LEFT OUTER JOIN
        (SELECT DISTINCT m1.\"PersonID\" as \"PersonID\", m2.\"PersonID\" as \"SpouseID\", m.\"Type\" as \"Type\"
            , m.\"MarriageDate\", m.\"DivorceDate\", m.\"CancelledDate\"
            FROM public.\"PersonMarriage\" m1, public.\"PersonMarriage\" m2, public.\"Marriage\" m
            WHERE m1.\"MarriageID\" = m2.\"MarriageID\" AND m1.\"Role\" = 'Wife' AND m2.\"Role\" = 'Husband' 
                    AND m1.\"MarriageID\" = m.\"ID\" $datestr
            GROUP BY m1.\"PersonID\", m2.\"PersonID\", m.\"Type\", m.\"MarriageDate\", m.\"DivorceDate\", m.\"CancelledDate\") m
        ON (m.\"PersonID\" = p.\"ID\")
    WHERE p.\"Gender\" = 'Female' AND c.\"ChurchOrgID\" = 1
    ORDER BY p.\"ID\" asc");
if (!$result) {
    exit;
}

// For each (secondary level) person
process_results($result);

// NOW THE OFFICES
if ($officeID != null) {

    // Query for all the main gender in the Office
    $result = pg_query($db, "SELECT DISTINCT p.\"ID\",n.\"First\",n.\"Middle\",n.\"Last\",p.\"BirthDate\",p.\"DeathDate\",
        p.\"Gender\", p.\"BirthPlaceID\", pm.\"PersonID\" as \"ChildOf\", m.\"Type\"
        FROM public.\"Person\" p INNER JOIN public.\"Name\" n ON (p.\"ID\"=n.\"PersonID\" AND n.\"Type\"='authoritative') 
        LEFT OUTER JOIN public.\"PersonMarriage\" pm ON (pm.\"MarriageID\" = p.\"BiologicalChildOfMarriage\" AND pm.\"Role\" = 'Husband')
        INNER JOIN public.\"PersonOffice\" c ON (c.\"PersonID\" = p.\"ID\") INNER JOIN public.\"Marriage\" m ON m.\"ID\" = pm.\"MarriageID\"
        WHERE p.\"Gender\" = 'Male' AND c.\"OfficeID\" = $officeID
        ORDER BY p.\"ID\" asc");
    if (!$result) {
        exit;
    }

    process_results($result);

    $datestr = "";
    if ($date != null)
        $datestr = "AND m.\"MarriageDate\" <= '$date'";
    // Query for all the secondary gender in the Office
    $result = pg_query($db, "SELECT DISTINCT p.\"ID\",n.\"First\",n.\"Middle\",n.\"Last\",p.\"BirthDate\",p.\"DeathDate\",
        p.\"Gender\", p.\"BirthPlaceID\", pm.\"PersonID\" as \"ChildOf\", m.\"SpouseID\", m.\"Type\"
        , m.\"MarriageDate\", m.\"DivorceDate\", m.\"CancelledDate\"
        FROM public.\"Person\" p INNER JOIN public.\"Name\" n ON (p.\"ID\"=n.\"PersonID\" AND n.\"Type\"='authoritative') 
        LEFT OUTER JOIN public.\"PersonMarriage\" pm ON (pm.\"MarriageID\" = p.\"BiologicalChildOfMarriage\" AND pm.\"Role\" = 'Husband')
        INNER JOIN public.\"PersonOffice\" c ON (c.\"PersonID\" = p.\"ID\")
        LEFT OUTER JOIN
            (SELECT DISTINCT m1.\"PersonID\" as \"PersonID\", m2.\"PersonID\" as \"SpouseID\", m.\"Type\" as \"Type\"
                , m.\"MarriageDate\", m.\"DivorceDate\", m.\"CancelledDate\"
                FROM public.\"PersonMarriage\" m1, public.\"PersonMarriage\" m2, public.\"Marriage\" m
                WHERE m1.\"MarriageID\" = m2.\"MarriageID\" AND m1.\"Role\" = 'Wife' AND m2.\"Role\" = 'Husband' 
                        AND m1.\"MarriageID\" = m.\"ID\" $datestr
                GROUP BY m1.\"PersonID\", m2.\"PersonID\", m.\"Type\", m.\"MarriageDate\", m.\"DivorceDate\", m.\"CancelledDate\") m
            ON (m.\"PersonID\" = p.\"ID\")
        WHERE p.\"Gender\" = 'Female' AND c.\"OfficeID\" = $officeID
        ORDER BY p.\"ID\" asc");
    if (!$result) {
        exit;
    }

    // For each (secondary level) person
    process_results($result);
}


// Now we have a set of people (edges) and we can see if there are missing connections
// Really want to look up anyone who has one of these people as a father or significant other

// CREATE SQL ARRAY of all primary gender
$males = $newmales;
while (!empty($newmales) && $iterations++ < $maxIter) {
    $prevmales = "(" . implode(",", array_keys($newmales)) . ")";
    $newmales = array();

    // Get all males who are their children (born before date)
    $datestr = "";
    if ($date != null)
        $datestr = "AND p.\"BirthDate\" <= '$date'";
    $result = pg_query($db, "SELECT p.\"ID\",n.\"First\",n.\"Middle\",n.\"Last\",p.\"BirthDate\",p.\"DeathDate\",
        p.\"Gender\", p.\"BirthPlaceID\", pm.\"PersonID\" as \"ChildOf\", m.\"Type\"
        FROM public.\"Person\" p, public.\"Name\" n, public.\"PersonMarriage\" pm, public.\"Marriage\" m
        WHERE p.\"ID\"=n.\"PersonID\" AND n.\"Type\"='authoritative' $datestr
            AND pm.\"MarriageID\" = p.\"BiologicalChildOfMarriage\" AND pm.\"Role\" = 'Husband'
            AND pm.\"PersonID\" in $prevmales AND p.\"Gender\" = 'Male' AND m.\"ID\" = pm.\"MarriageID\"
        ORDER BY p.\"ID\" asc");
    if (!$result) {
        exit;
    }

    process_results($result);

    // Get all the females who are their children and had marriages before that date
    $datestr1 = "";
    $datestr2 = "";
    if ($date != null) {
        $datestr1 = "AND p.\"BirthDate\" <= '$date'";
        $datestr2 = "AND m.\"MarriageDate\" <= '$date'";
    }
    $result = pg_query($db, "SELECT DISTINCT p.\"ID\",n.\"First\",n.\"Middle\",n.\"Last\",p.\"BirthDate\",p.\"DeathDate\",
        p.\"Gender\", p.\"BirthPlaceID\", pm.\"PersonID\" as \"ChildOf\", m.\"SpouseID\", m.\"Type\"
            , m.\"MarriageDate\", m.\"DivorceDate\", m.\"CancelledDate\"
        FROM public.\"Person\" p, public.\"Name\" n, public.\"PersonMarriage\" pm,
            (SELECT DISTINCT m1.\"PersonID\" as \"PersonID\", m2.\"PersonID\" as \"SpouseID\", m.\"Type\"
                , m.\"MarriageDate\", m.\"DivorceDate\", m.\"CancelledDate\"
                FROM public.\"PersonMarriage\" m1, public.\"PersonMarriage\" m2, public.\"Marriage\" m
                WHERE m1.\"MarriageID\" = m2.\"MarriageID\" AND m1.\"Role\" = 'Wife' AND m2.\"Role\" = 'Husband' 
                    AND m.\"ID\" = m1.\"MarriageID\" $datestr2
                GROUP BY m1.\"PersonID\", m2.\"PersonID\", m.\"Type\", m.\"MarriageDate\", m.\"DivorceDate\", m.\"CancelledDate\") m
        WHERE p.\"ID\"=n.\"PersonID\" AND n.\"Type\"='authoritative' AND p.\"Gender\" = 'Female' 
            AND pm.\"MarriageID\" = p.\"BiologicalChildOfMarriage\" AND pm.\"Role\" = 'Husband'
            AND m.\"PersonID\" = p.\"ID\" AND pm.\"PersonID\" in $prevmales $datestr1
        ORDER BY p.\"ID\" asc");
    if (!$result) {
        exit;
    }

    process_results($result);

    // Get all the people who are their wives
    $datestr = "";
    if ($date != null)
        $datestr = "AND m.\"MarriageDate\" <= '$date'";
    //TODO: I think I need to add here the marriage dates.
    $result = pg_query($db, "
    SELECT DISTINCT p.\"ID\",n.\"First\",n.\"Middle\",n.\"Last\",p.\"BirthDate\",p.\"DeathDate\",
        p.\"Gender\", p.\"BirthPlaceID\", pm.\"PersonID\" as \"ChildOf\", m.\"SpouseID\", m.\"Type\"
            , m.\"MarriageDate\", m.\"DivorceDate\", m.\"CancelledDate\"
        FROM public.\"Person\" p INNER JOIN public.\"Name\" n ON (p.\"ID\"=n.\"PersonID\" AND n.\"Type\"='authoritative') 
        LEFT OUTER JOIN public.\"PersonMarriage\" pm ON (pm.\"MarriageID\" = p.\"BiologicalChildOfMarriage\" AND pm.\"Role\" = 'Husband')
        INNER JOIN
            (SELECT DISTINCT m1.\"PersonID\" as \"PersonID\", m2.\"PersonID\" as \"SpouseID\", m.\"Type\" 
                , m.\"MarriageDate\", m.\"DivorceDate\", m.\"CancelledDate\"
                FROM public.\"PersonMarriage\" m1, public.\"PersonMarriage\" m2, public.\"Marriage\" m
                WHERE m1.\"MarriageID\" = m2.\"MarriageID\" AND m1.\"Role\" = 'Wife' 
                    AND m2.\"Role\" = 'Husband' AND m.\"ID\" = m1.\"MarriageID\" $datestr 
                GROUP BY m1.\"PersonID\", m2.\"PersonID\", m.\"Type\", m.\"MarriageDate\", m.\"DivorceDate\", m.\"CancelledDate\") m
            ON (m.\"PersonID\" = p.\"ID\")
        WHERE p.\"Gender\" = 'Female' 
            AND m.\"SpouseID\" in $prevmales
        ORDER BY p.\"ID\" asc");
    if (!$result) {
        exit;
    }

    process_results($result);

    // Get all the people who are their parents
    $result = pg_query($db, "SELECT DISTINCT p.\"ID\",n.\"First\",n.\"Middle\",n.\"Last\",p.\"BirthDate\",p.\"DeathDate\",
        p.\"Gender\", p.\"BirthPlaceID\", pm.\"PersonID\" as \"ChildOf\", m.\"Type\"
        FROM public.\"Person\" p INNER JOIN public.\"Name\" n ON (p.\"ID\"=n.\"PersonID\" AND n.\"Type\"='authoritative') 
        LEFT OUTER JOIN public.\"PersonMarriage\" pm ON (pm.\"MarriageID\" = p.\"BiologicalChildOfMarriage\" AND pm.\"Role\" = 'Husband')
        INNER JOIN
    (SELECT p.\"ID\",n.\"First\",n.\"Middle\",n.\"Last\",p.\"BirthDate\",p.\"DeathDate\",
        p.\"Gender\", p.\"BirthPlaceID\", pm.\"PersonID\" as \"ChildOf\"
        FROM public.\"Person\" p, public.\"Name\" n, public.\"PersonMarriage\" pm
        WHERE p.\"ID\"=n.\"PersonID\" AND n.\"Type\"='authoritative'
            AND pm.\"MarriageID\" = p.\"BiologicalChildOfMarriage\" AND pm.\"Role\" = 'Husband'
        ORDER BY p.\"ID\" asc) ch ON (ch.\"ChildOf\" = p.\"ID\") INNER JOIN public.\"Marriage\" m ON m.\"ID\" = pm.\"MarriageID\"

        WHERE ch.\"ID\" in $prevmales
        ORDER BY p.\"ID\" asc");

    if (!$result) {
        exit;
    }
    process_results($result);
    // have a person, need that the people who are biological children of their marriages are in the list of known people

    // Look up all the new males we've just added and put them in
    $newones = "(" . implode(",", array_keys($newmales)) . ")";
    $result = pg_query($db, "SELECT DISTINCT p.\"ID\",n.\"First\",n.\"Middle\",n.\"Last\",p.\"BirthDate\",p.\"DeathDate\",
        p.\"Gender\", p.\"BirthPlaceID\", pm.\"PersonID\" as \"ChildOf\", m.\"Type\"
        FROM public.\"Person\" p INNER JOIN public.\"Name\" n ON (p.\"ID\"=n.\"PersonID\" AND n.\"Type\"='authoritative') 
        LEFT OUTER JOIN public.\"PersonMarriage\" pm ON (pm.\"MarriageID\" = p.\"BiologicalChildOfMarriage\" AND pm.\"Role\" = 'Husband')
        INNER JOIN public.\"Marriage\" m ON m.\"ID\" = pm.\"MarriageID\"
        WHERE p.\"Gender\" = 'Male' AND p.\"ID\" in $newones 
        ORDER BY p.\"ID\" asc");
    if (!$result) {
        exit;
    }
    

    process_results($result);

    // Put all the new males into the list of all males
    foreach ($newmales as $k=>$v) {
        $males[$k] = $v;
    }

}

if ($iterations == 100) error_log("Went 100 iterations without stopping\n");







function process_results($result) {
    global $newmales, $nodes, $edges, $dummyCounter;
    while ($person = pg_fetch_array($result)) {
        // if they don't have a to-marriage, then add one for their ID.
        if ($person["Gender"] == "Male") {
            $newmales[$person["ID"]] = true;
            $nodes[$person["ID"]] = array(
                "id" => $person["ID"],
                "label" => htmlspecialchars($person["First"] . " " . $person["Last"] . " Marriage"),
                "begin" => $person["BirthDate"],
                "end" => "1900-01-01" 
            );
        }

        // set up the target
        $target = $person["ID"];
        if ($person["Gender"] == "Female") {
            if (isset($person["SpouseID"]) && $person["SpouseID"] != null && $person["SpouseID"] != "") {
                $target = $person["SpouseID"];
                $newmales[$target] = true;
            } else {
                $target = $dummyCounter++;
                $nodes[$target] = array(
                    "id" => $target,
                    "label" => "Dummy Marriage",
                    "begin" => $person["BirthDate"],
                    "end" => "1900-01-01" 
                );
            }
        }

        // set up the source
        $childOf = null; 
        if (isset($person["ChildOf"]) && $person["ChildOf"] != null && $person["ChildOf"] != "") {
            $childOf = $person["ChildOf"];
            $newmales[$childOf] = true;
        } else {
            $childOf = $dummyCounter++;
            $nodes[$childOf] = array(
                "id" => $childOf,
                "label" => "Dummy Marriage",
                "begin" => $person["BirthDate"],
                "end" => "1900-01-01" 
            );
            
        }
        // Add the person link from their marriage of birth to their marriage of adulthood
        $begindate = "";
        $enddate = "";
        if ($person["Gender"] == "Male") {
            $begindate = $person["BirthDate"];
            $enddate = $person["DeathDate"];
        } else {
            if (isset($person["MarriageDate"]))
                $begindate = $person["MarriageDate"];
            else
                $begindate = $person["BirthDate"];


            if (isset($person["DivorceDate"]))
                $enddate = $person["DivorceDate"];
            else if (isset($person["CancelledDate"]))
                $enddate = $person["CancelledDate"];
            else
                $enddate = $person["DeathDate"];
        }
        $edge = array(
            "source" => $childOf,
            "target" => $target, 
            "label" => htmlspecialchars($person["First"] . " " . $person["Last"]),
            "weight" => calculate_weight($person["Type"], $person["Gender"]),
            "begin" => $begindate,
            "end" => $enddate
        ); // get the weight from the type

        // check that this edge is not already accounted for (inefficient)
        $inarray = false;
        foreach ($edges as $k => $e) {
            if ($edge["source"] == $e["source"] && $edge["target"] == $e["target"]) {
                if ($edge["end"] > $e["end"])
                    $edges[$k]["end"] = $edge["end"];
                if ($edge["begin"] < $e["begin"])
                    $edges[$k]["begin"] = $edge["begin"];
                $inarray = true;
                break;
            }
        }
        if (!$inarray)
            array_push($edges, $edge);
    }
}

function calculate_weight($marriage_type, $gender) {
    if ($gender == "Male")
        return 4;
    if ($marriage_type == "eternity")
        return 3;
    if ($marriage_type == "time")
        return 2;
    if ($marriage_type == "civil")
        return 1;
    return 0; // unknown and BYU types
}


//***************************************************************************************************
// Print the results
header("Content-Type: text/json");
// Opening of the file

// Nodes

// Edges
foreach ($edges as $i => $edge) {
    $edges[$i]["id"] = $i;
}

$outNodes = array();
$outEdges = array();

foreach ($nodes as $node)
    array_push($outNodes, $node);
foreach ($edges as $edge)
    array_push($outEdges, $edge);

$output = array("nodes" => $outNodes, "edges"=>$outEdges);

echo json_encode($output, JSON_PRETTY_PRINT);

?>
