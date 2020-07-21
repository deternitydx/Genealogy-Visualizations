<?php
include("../database.php");

header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');

$where = "";
if (isset($_GET["id"])) {
    $where = "AND m.\"ID\"=" . $_GET["id"];
}


$db = pg_connect($db_conn_string);

$result = pg_query($db, "SELECT DISTINCT m.\"ID\", m.\"MarriageDate\", m.\"DivorceDate\",m.\"CancelledDate\", m.\"Type\", 
    h.\"PersonID\" as \"HusbandID\", 
    hn.\"Last\" as \"HusbandLast\", hn.\"First\" as \"HusbandFirst\", w.\"PersonID\" as \"WifeID\", wn.\"Last\" as \"WifeLast\", wn.\"First\" as \"WifeFirst\"
    FROM public.\"Marriage\" m, public.\"PersonMarriage\" h, public.\"PersonMarriage\" w, public.\"Name\" wn, public.\"Name\" hn
    WHERE
    h.\"MarriageID\" = m.\"ID\" AND h.\"Role\" = 'Husband' AND w.\"MarriageID\" = m.\"ID\" AND w.\"Role\" = 'Wife' 
    AND wn.\"PersonID\" = w.\"PersonID\" AND wn.\"Type\" = 'authoritative'
    AND hn.\"PersonID\" = h.\"PersonID\" AND hn.\"Type\" = 'authoritative'
    $where
    ORDER BY m.\"ID\" DESC");
if (!$result) {
    echo "1An error occurred.\n";
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

    // add a link to children at the end
    array_push($resa, "\"<a href='/nauvoo/data_view/people.php?parentSearch={$mar["ID"]}'>View</a>\"");
    if ($first) array_push($firsta, "\"Children\"");

    array_push($json, "[" . implode(", ", $resa) . "]");
    $first = false;


}
echo implode(",\n", $json);

echo "]\n }";

?>

