<?php
include("../database.php");

header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');

$db = pg_connect($db_conn_string);

$type = $_GET["type"];
$search = implode(" & ", explode(" ", pg_escape_string($_GET["q"])));

$query = "";

if ($type == "name") {
    $query = "SELECT DISTINCT p.\"ID\", n.\"First\", n.\"Middle\", n.\"Last\", p.\"Gender\", p.\"BirthDate\", p.\"DeathDate\", p.\"PrivateNotes\", p.\"PublicNotes\" FROM \"public\".\"Name\" n, \"public\".\"Person\" p WHERE n.\"First\" || ' ' || n.\"Last\" @@ to_tsquery('$search') AND n.\"Type\"='authoritative' AND n.\"PersonID\" = p.\"ID\" ORDER BY n.\"Last\", n.\"First\" ASC";
} else if ($type == "children") {
    $query = "SELECT DISTINCT p.\"ID\", n.\"First\", n.\"Middle\", n.\"Last\", p.\"Gender\", p.\"BirthDate\", p.\"DeathDate\", p.\"PrivateNotes\", p.\"PublicNotes\" FROM \"public\".\"Name\" n, \"public\".\"Person\" p, \"public\".\"PersonMarriage\" m WHERE m.\"PersonID\" = $search AND (m.\"Role\" = 'Husband' OR m.\"Role\" = 'Wife') AND n.\"Type\"='authoritative' AND n.\"PersonID\" = p.\"ID\" AND p.\"BiologicalChildOfMarriage\" = m.\"MarriageID\" ORDER BY n.\"Last\", n.\"First\", n.\"Middle\" ASC";
}

$result = pg_query($db, $query);
if (!$result) {
    exit;
}

$arr = pg_fetch_all($result);
echo "{ \n\"data\": [\n";
$json = array();
$first = true;
foreach ($arr as $mar) {
    $resa = array();
    $firsta = array();
    foreach ($mar as $k=>$v) {
        $clean = htmlspecialchars($v);
        if ($clean == "") $clean = "&nbsp;";
        array_push($resa, "\"$clean\"");
        if ($first) array_push($firsta, "\"$k\"");
    }

    // Add link to chord
    array_push($resa, "\"<a href='../chord.html?temporal=1&id={$mar["ID"]}'>Temporal</a> - <a href='../chord.html?id={$mar["ID"]}'>Static</a>\"");
    // Add link to edit
    array_push($resa, "\"<a href='../data_entry/individual.php?id={$mar["ID"]}'>Edit</a>\"");

    array_push($json, "[" . implode(", ", $resa) . "]");
    $first = false;


}
echo implode(",\n", $json);

echo "]\n }";

?>
