<?php
include("../database.php");

header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');

$id = 50;
if (isset($_GET["id"]))
	$id = $_GET["id"];
	

$db = pg_connect($db_conn_string);

$result = pg_query($db, "SELECT m.\"ID\", m.\"PlaceID\", m.\"MarriageDate\", m.\"DivorceDate\",m.\"CancelledDate\", w.\"PersonID\" as \"WifeID\", h.\"PersonID\" as \"HusbandID\", m.\"Root\", m.\"Type\" FROM public.\"Marriage\" m, public.\"PersonMarriage\" h, public.\"PersonMarriage\" w WHERE
       h.\"MarriageID\" = m.\"ID\" AND h.\"Role\" = 'Husband' AND w.\"MarriageID\" = m.\"ID\" AND w.\"Role\" = 'Wife' AND w.\"PersonID\"=$id ORDER BY m.\"MarriageDate\" ASC");
if (!$result) {
    print_empty("Error finding marriages.");
    exit;
}

$arr = pg_fetch_all($result);

// got the marriage
$marriages = $arr;

$parents = array();
$children = array();
$relations = array();

$result = pg_query($db, "SELECT p.*, n.\"First\", n.\"Middle\", n.\"Last\"  FROM public.\"Person\" p, public.\"Name\" n WHERE p.\"ID\" = n.\"PersonID\" AND n.\"Type\" = 'authoritative' AND p.\"ID\"=" . $marriages[0]["WifeID"]);
if (!$result) {
    print_empty("No marriages with this woman.");
    exit;
}

$arr = pg_fetch_all($result);

// got the husband
$arr[0]["Married"] = "";
$arr[0]["Divorced"] = "";
$wife = $arr[0];
array_push($parents, $arr[0]);


// Get the husbands and their children and adoptions to this wife
foreach ($marriages as $marriage) {
    $result = pg_query($db, "SELECT DISTINCT p.*, n.\"First\", n.\"Middle\", n.\"Last\"   FROM public.\"Person\" p, public.\"Name\" n WHERE p.\"ID\" = n.\"PersonID\" AND n.\"Type\" = 'authoritative' AND p.\"ID\"=" . $marriage["HusbandID"]);
	if (!$result) {
        print_empty("Error finding husband information.");
	    exit;
	}

	$arr = pg_fetch_all($result);

	// got the husband
	$husband = $arr[0];
	$husband["Married"] = $marriage["MarriageDate"];
	$husband["Divorced"] = $marriage["DivorceDate"];

    // Add the husband if he's not already here
    $found = false;
    foreach($parents as $parent)
            if ($parent["ID"] == $husband["ID"]) {
                    $found = true;
                    break;
            }
    if (!$found)
	    array_push($parents,$husband);

    // Add the husband-wife relationship
    array_push($relations, "{\"desc\": \"Married To\", \"type\":\"{$marriage["Type"]}\", \"from\":\"" . $wife["ID"] . "\", \"to\":\"" . $husband["ID"] . "\", \"root\":\"{$marriage["Root"]}\", \"marriageDate\":\"{$husband["Married"]}\", \"divorceDate\":\"{$husband["Divorced"]}\"}");


    $result = pg_query($db, "SELECT DISTINCT p.*, n.\"First\", n.\"Middle\", n.\"Last\"  FROM public.\"Person\" p, public.\"Name\" n WHERE p.\"ID\" = n.\"PersonID\" AND n.\"Type\" = 'authoritative' AND p.\"BiologicalChildOfMarriage\"=" . $marriage["ID"]);
	if (!$result) {
        print_empty("Error finding biological children.");
	    exit;
	}

	$arr = pg_fetch_all($result);


	$tmpchildren = array();
	// got the biological children
	foreach ($arr as $child) {
		$child["AdoptionDate"] = "";
		array_push($tmpchildren, $child);
		array_push($relations, "{\"desc\": \"Child Of\", \"type\":\"biological\", \"from\":\"" . $child["ID"] . "\", \"to\":\"" . $husband["ID"] . "\"}");
	}

	// Get the adopted children of this marriage
	$result = pg_query($db, "SELECT DISTINCT p.\"ID\", p.\"DeathDate\", n.\"First\", n.\"Middle\", n.\"Last\", n.\"Prefix\", n.\"Suffix\", p.\"Gender\", p.\"BiologicalChildOfMarriage\",  p.\"BirthDate\", nms.\"Date\" as \"AdoptionDate\" FROM public.\"Person\" p LEFT JOIN public.\"Name\" n  ON p.\"ID\" = n.\"PersonID\" LEFT JOIN public.\"NonMaritalSealings\" nms ON nms.\"AdopteeID\" = p.\"ID\" WHERE nms.\"MarriageID\" = {$marriage['ID']} AND n.\"Type\" = 'authoritative' ORDER BY p.\"BirthDate\" ASC");
	if (!$result) {
        print_empty("Error finding adopted children.");
	    exit;
	}

	$arr = pg_fetch_all($result);
	
    // Got the adopted children.  Unfortunately, we must check all the biological and adopted children to make sure this person hasn't been adopted twice or have been a biological child that has been adopted.
    foreach ($arr as $child) {
        $add = true;
        foreach ($tmpchildren as $i =>$tmp)
            if ($tmp["ID"] === $child["ID"]) { 
                $add = false;
                $tmpchildren[$i]["AdoptionDate"] = $child["AdoptionDate"];
            }
        foreach ($children as $i => $tmp)
            if ($tmp["ID"] === $child["ID"]) { 
                $add = false;
                $children[$i]["AdoptionDate"] = $child["AdoptionDate"];
            }
        if ($add)
            array_push($tmpchildren, $child);
		array_push($relations, "{\"desc\": \"Child Of\", \"type\":\"adoption\", \"from\":\"" . $child["ID"] . "\", \"to\":\"" . $husband["ID"] . "\"}");
	}
	$children = array_merge($children, $tmpchildren);//array_reverse($tmpchildren));
}

//reorder the children by birthday
$births = array();
foreach ($children as $k => $child)
    $births[$k] = $child["BirthDate"];
array_multisort($births, $children);


echo "{ \"parents\": [";
$parPrint = array();
foreach ($parents as $parent) {
	array_push($parPrint, "{ \"id\": \"{$parent["ID"]}\", \"name\": \"" . $parent["Last"] . ", " . $parent["First"] . " " . $parent["Middle"] . "\", ".
		"\"birthDate\":\"".$parent["BirthDate"]."\", \"deathDate\":\"".$parent["DeathDate"]."\", \"gender\": \"". $parent["Gender"] ."\", \"marriageDate\": \"".$parent["Married"]."\", \"divorceDate\":\"".$parent["Divorced"]."\"}");
} 
echo implode(",", $parPrint);

echo "], \"children\": [";

$chiPrint = array();
foreach ($children as $child) {
	array_push($chiPrint, "{ \"id\": \"{$child["ID"]}\", \"name\": \"" . $child["Last"] . ", " . $child["First"] . " " . $child["Middle"] . "\", ".
		"\"birthDate\":\"".$child["BirthDate"]."\", \"deathDate\":\"".$child["DeathDate"]."\", ".
		"\"gender\": \"". $child["Gender"] ."\", \"adoptionDate\": \"".$child["AdoptionDate"]."\"}");
} 

echo implode(",", $chiPrint);

echo "], \"relationships\": [ " . implode(",", $relations) ."] }";


//print_r($arr);
function print_empty($error) {
    echo "{ \"error\" : \"$error\", ";
    echo " \"parents\": [";
    echo "], \"children\": [";
    echo "], \"relationships\": [ ] }";
}

?>

