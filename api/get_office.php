<?php
include("../database.php");
    header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');

    $db = pg_connect($db_conn_string);

    $query = "
        SELECT DISTINCT o.\"ID\", o.\"Name\" 

        FROM public.\"Office\" o

        ORDER BY o.\"Name\" ASC";
    $result = pg_query($db, $query);
    if (!$result) {
        exit;
    }
    $results = pg_fetch_all($result);

    $offices = array();

    foreach($results as $res) {
        $office = array("id"=>$res["ID"], "text"=>$res["Name"]);
        array_push($offices, $office);
    }
    echo json_encode($offices);
?>

