<?php
include("../database.php");
    header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');

    if (!isset($_GET['q'])) {
        echo "{ 'error': 'no search term given'}";
        die();
    }

    $q = $_GET['q'];

    $db = pg_connect($db_conn_string);

    $query = "
        SELECT DISTINCT n.\"ID\", n.\"Prefix\", n.\"First\", n.\"Middle\", n.\"Last\", n.\"Suffix\"

        FROM public.\"Name\" n

        WHERE n.\"PersonID\" = $q

        ORDER BY n.\"Last\", n.\"First\" ASC";
    $result = pg_query($db, $query);
    if (!$result) {
        exit;
    }
    $results = pg_fetch_all($result);

    $names = array();

    foreach($results as $res) {
        $name = array("id"=>$res["ID"]);
        $name["text"] = "";
        if ($res["Prefix"] != "")
            $name["text"] .= $res["Prefix"] . " ";
        if ($res["First"] != "")
            $name["text"] .= $res["First"] . " ";
        if ($res["Middle"] != "")
            $name["text"] .= $res["Middle"] . " ";
        if ($res["Last"] != "")
            $name["text"] .= $res["Last"] . " ";
        if ($res["Suffix"] != "")
            $name["text"] .= $res["Suffix"] . " ";
        array_push($names, $name);
    }
    echo json_encode($names);
?>

