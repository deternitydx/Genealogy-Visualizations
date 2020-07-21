<?php
include("../database.php");
header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');

$where = "";
if (isset($_GET["parentsID"])) {
    $where = "AND p.\"BiologicalChildOfMarriage\"=" . $_GET["parentsID"];
}

$db = pg_connect($db_conn_string);

$result = pg_query($db, "SELECT p.\"ID\",n.\"First\",n.\"Middle\",n.\"Last\",p.\"BirthDate\",p.\"DeathDate\",
    p.\"Gender\", p.\"BirthPlaceID\", p.\"BiologicalChildOfMarriage\" as \"ChildOf\",
    p.\"BYUID\"
    FROM public.\"Person\" p, public.\"Name\" n
    WHERE p.\"ID\"=n.\"PersonID\" AND n.\"Type\"='authoritative' $where
    ORDER BY n.\"Last\", n.\"First\",n.\"Middle\" asc");
if (!$result) {
    exit;
}

$arr = pg_fetch_all($result);
echo "{ \n\"data\": [\n";
$json = array();
$first = true;
foreach ($arr as $person) {
    $resa = array();
    $firsta = array();
    foreach ($person as $k=>$v) {
        $clean = htmlspecialchars($v);
        if ($clean == "") $clean = "&nbsp;";
        if ($k=="ChildOf") {
            $clean = "<a href='marriages.php?idSearch=$clean'>$clean</a>";    
        }
        array_push($resa, "\"$clean\"");
        if ($first) array_push($firsta, "\"$k\"");
    }

    array_push($resa, "\"<a href='../data_entry/individual.php?id={$person["ID"]}'>Edit</a>\"");

    array_push($json, "[" . implode(", ", $resa) . "]");
    $first = false;


}
echo implode(",\n", $json);

echo "]\n }";

?>
