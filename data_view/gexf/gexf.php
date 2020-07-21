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

header("Content-Type: text/xml");
// Opening of the file
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo "<gexf xmlns=\"http://www.gexf.net/1.2draft\" version=\"1.2\">\n";
echo "<meta lastmodifieddate=\"" . date("Y-m-d") . "\">\n";
echo "\t<creator>Robbie Hott</creator>\n";
echo "\t<description>Nauvoo Graph</description>\n";
echo "</meta>\n";
echo "<graph mode=\"static\" defaultedgetype=\"directed\">\n";


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

$nodes = array();
$edges = array();
$dummyCounter = 100000000;
include("../../database.php");
$db = pg_connect($db_conn_string);


// Query for all the main gender in the AQ
$result = pg_query($db, "SELECT DISTINCT p.\"ID\",n.\"First\",n.\"Middle\",n.\"Last\",p.\"BirthDate\",p.\"DeathDate\",
    p.\"Gender\", p.\"BirthPlaceID\", pm.\"PersonID\" as \"ChildOf\"
    FROM public.\"Person\" p INNER JOIN public.\"Name\" n ON (p.\"ID\"=n.\"PersonID\" AND n.\"Type\"='authoritative') 
    LEFT OUTER JOIN public.\"PersonMarriage\" pm ON (pm.\"MarriageID\" = p.\"BiologicalChildOfMarriage\" AND pm.\"Role\" = 'Husband')
    WHERE p.\"Gender\" = 'Male'
    ORDER BY p.\"ID\" asc");
if (!$result) {
    exit;
}

process_results($result);

// Query for all the secondary gender in the AQ
$result = pg_query($db, "SELECT DISTINCT p.\"ID\",n.\"First\",n.\"Middle\",n.\"Last\",p.\"BirthDate\",p.\"DeathDate\",
    p.\"Gender\", p.\"BirthPlaceID\", pm.\"PersonID\" as \"ChildOf\", m.\"SpouseID\"
    FROM public.\"Person\" p INNER JOIN public.\"Name\" n ON (p.\"ID\"=n.\"PersonID\" AND n.\"Type\"='authoritative') 
    LEFT OUTER JOIN public.\"PersonMarriage\" pm ON (pm.\"MarriageID\" = p.\"BiologicalChildOfMarriage\" AND pm.\"Role\" = 'Husband')
    LEFT OUTER JOIN
        (SELECT DISTINCT m1.\"PersonID\" as \"PersonID\", m2.\"PersonID\" as \"SpouseID\" 
            FROM public.\"PersonMarriage\" m1, public.\"PersonMarriage\" m2
            WHERE m1.\"MarriageID\" = m2.\"MarriageID\" AND m1.\"Role\" = 'Wife' AND m2.\"Role\" = 'Husband' GROUP BY m1.\"PersonID\", m2.\"PersonID\") m
        ON (m.\"PersonID\" = p.\"ID\")
    WHERE p.\"Gender\" = 'Female'
    ORDER BY p.\"ID\" asc");
if (!$result) {
    exit;
}

// For each (secondary level) person
process_results($result);


function process_results($result) {
    global $nodes, $edges, $dummyCounter;
    while ($person = pg_fetch_array($result)) {
        // if they don't have a to-marriage, then add one for their ID.
        if ($person["Gender"] == "Male") {
            $nodes[$person["ID"]] = array(
                "id" => $person["ID"],
                "label" => htmlspecialchars($person["First"] . " " . $person["Last"] . " Marriage"));
        }

        // set up the target
        $target = $person["ID"];
        if ($person["Gender"] == "Female") {
            if (isset($person["SpouseID"]) && $person["SpouseID"] != null && $person["SpouseID"] != "")
                $target = $person["SpouseID"];
            else
                $target = $dummyCounter++;
        }

        // set up the source
        $childOf = null; 
        if (isset($person["ChildOf"]) && $person["ChildOf"] != null && $person["ChildOf"] != "")
            $childOf = $person["ChildOf"];
        else
            $childOf = $dummyCounter++;
        // Add the person link from their marriage of birth to their marriage of adulthood
        $edge = array(
            "source" => $childOf,
            "target" => $target, 
            "label" => htmlspecialchars($person["First"] . " " . $person["Last"]));

        // check that this edge is not already accounted for (inefficient)
        $inarray = false;
        foreach ($edges as $e) {
            if ($edge["source"] == $e["source"] && $edge["target"] == $e["target"]) {
                $inarray = true;
                break;
            }
        }
        if (!$inarray)
            array_push($edges, $edge);
    }
}


// Nodes
echo "<nodes>\n";
foreach ($nodes as $node) {
    echo "\t<node ";
    foreach ($node as $key => $val) echo "$key = \"$val\" ";
    echo "/>\n";
}
echo "</nodes>\n";

// Edges
echo "<edges>\n";
foreach ($edges as $i => $edge) {
    echo "\t<edge ";
    echo "id = \"$i\" ";
    foreach ($edge as $key => $val) echo "$key = \"$val\" ";
    echo "/>\n";
}
echo "</edges>\n";

// Closing of the file
echo "</graph>\n";
echo "</gexf>\n";


?>
