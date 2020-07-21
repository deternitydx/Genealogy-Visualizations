<?php
include("../database.php");

header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');

$id = 50;
if (isset($_GET["id"]))
	$id = $_GET["id"];
	

$db = pg_connect($db_conn_string);

$result = pg_query($db, "SELECT m.\"ID\", m.\"PlaceID\", m.\"MarriageDate\", m.\"DivorceDate\",m.\"CancelledDate\", m.\"Type\", w.\"PersonID\" as \"WifeID\", h.\"PersonID\" as \"HusbandID\", m.\"Root\" FROM public.\"Marriage\" m, public.\"PersonMarriage\" h, public.\"PersonMarriage\" w WHERE
       h.\"MarriageID\" = m.\"ID\" AND h.\"Role\" = 'Husband' AND w.\"MarriageID\" = m.\"ID\" AND w.\"Role\" = 'Wife' AND h.\"PersonID\"=$id ORDER BY m.\"MarriageDate\" ASC");
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

$result = pg_query($db, "SELECT p.*, n.\"First\", n.\"Middle\", n.\"Last\"  FROM public.\"Person\" p, public.\"Name\" n WHERE p.\"ID\" = n.\"PersonID\" AND n.\"Type\" = 'authoritative' AND p.\"ID\"=" . $marriages[0]["HusbandID"]);
if (!$result) {
    print_empty("No marriages for this man.");
    exit;
}

$arr = pg_fetch_all($result);

// got the husband
$husband = $arr[0];
$husband["Married"] = "";
$husband["Divorced"] = "";

array_push($parents, $husband);


// Get the wives and their children and adoptions to this wife
foreach ($marriages as $marriage) {
    $result = pg_query($db, "SELECT DISTINCT p.*, n.\"First\", n.\"Middle\", n.\"Last\" FROM public.\"Person\" p, public.\"Name\" n WHERE p.\"ID\" = n.\"PersonID\" AND n.\"Type\" = 'authoritative' AND p.\"ID\"=" . $marriage["WifeID"]);
	if (!$result) {
        print_empty("Error finding wife information.");
	    exit;
	}

	$arr = pg_fetch_all($result);

	// got the wife
	$wife = $arr[0];
	$wife["Married"] = $marriage["MarriageDate"];
    $wife["Divorced"] = $marriage["DivorceDate"];

    // Add the wife if she's not already here
    $found = false;
    foreach($parents as $parent)
            if ($parent["ID"] == $wife["ID"]) {
                    $found = true;
                    break;
            }
    if (!$found)
	    array_push($parents,$wife);

    // Add the husband-wife relationship
    array_push($relations, "{\"desc\": \"Married To\", \"type\":\"{$marriage["Type"]}\", \"from\":\"" . $husband["ID"] . "\", \"to\":\"" . $wife["ID"] . "\", \"root\":\"{$marriage["Root"]}\", \"marriageDate\":\"{$wife["Married"]}\", \"divorceDate\":\"{$wife["Divorced"]}\"}");

	// Get the biological children of this marriage
    $result = pg_query($db, "SELECT DISTINCT p.*, n.\"First\", n.\"Middle\", n.\"Last\" FROM public.\"Person\" p, public.\"Name\" n WHERE p.\"ID\" = n.\"PersonID\" AND n.\"Type\" = 'authoritative' AND p.\"BiologicalChildOfMarriage\"=" . $marriage["ID"] . " ORDER BY p.\"BirthDate\" ASC");
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
		array_push($relations, "{\"desc\": \"Child Of\", \"type\":\"biological\", \"from\":\"" . $child["ID"] . "\", \"to\":\"" . $wife["ID"] . "\"}");
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
		array_push($relations, "{\"desc\": \"Child Of\", \"type\":\"adoption\", \"from\":\"" . $child["ID"] . "\", \"to\":\"" . $wife["ID"] . "\"}");
    }

	$children = array_merge($children, $tmpchildren);//array_reverse($tmpchildren));
}

$toremove = array();
// Check to see if there are any marriages and children
foreach ($children as $i => $child) {
        foreach ($parents as $j => $tmp)
            if ($tmp["ID"] === $child["ID"]) { 
                $parents[$j]["AdoptionDate"] = $child["AdoptionDate"];
                array_push($toremove, $i);
            }
}

foreach ($toremove as $i) {
    unset($children[$i]);
}

//reorder the children by birthday or adoption date
$births = array();
foreach ($children as $k => $child) {
    if (isset($child["AdoptionDate"]) && $child["AdoptionDate"] != "")
        $births[$k] = $child["AdoptionDate"];
    else
        $births[$k] = $child["BirthDate"];
}
array_multisort($births, $children);


echo "{ \"parents\": [";
$parPrint = array();
foreach ($parents as $parent) {
    $str = "{ \"id\": \"{$parent["ID"]}\", \"name\": \"" . $parent["Last"] . ", " . $parent["First"] . " " . $parent["Middle"] . "\", ".
            "\"birthDate\":\"".$parent["BirthDate"]."\", \"deathDate\":\"".$parent["DeathDate"]."\", \"gender\": \"". 
            $parent["Gender"] ."\", \"marriageDate\": \"".$parent["Married"]."\", \"divorceDate\":\"".$parent["Divorced"]."\"";
    if (isset($parent["AdoptionDate"]))
        $str .= ", \"adoptionDate\":\"".$parent["AdoptionDate"]."\"";
    $str .= "}";
	array_push($parPrint, $str);
} 
echo implode(",\n", $parPrint);

echo "],\n \"children\": [\n";

$chiPrint = array();
foreach ($children as $child) {
	array_push($chiPrint, "{ \"id\": \"{$child["ID"]}\", \"name\": \"" . $child["Last"] . ", " . $child["First"] . " " . $child["Middle"] .  "\", ".
		"\"birthDate\":\"".$child["BirthDate"]."\", \"deathDate\":\"".$child["DeathDate"]."\", ".
		"\"gender\": \"". $child["Gender"] ."\", \"adoptionDate\": \"".$child["AdoptionDate"]."\"}");
} 

echo implode(",\n", $chiPrint);

echo "],\n \"relationships\": [ \n" . implode(",\n", $relations) ."] }\n";


function print_empty($error) {
    echo "{ \"error\" : \"$error\", ";
    echo " \"parents\": [";
    echo "], \"children\": [";
    echo "], \"relationships\": [ ] }";
}
//print_r($arr);

?>

