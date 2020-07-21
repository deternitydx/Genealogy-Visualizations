<?php
include("../database.php");
    header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');

    /*
     * If there is no query, then we will return a default list
     * that is helpful to UVA.
     */
    if (!isset($_GET['q']) || strlen($_GET['q']) < 1) {
        $places = array(
            array("id"=>18524, "text"=>"Nauvoo, Hancock, Illinois, USA (UVA)"),
            array("id"=>18525, "text"=>"Nauvoo Temple, Nauvoo, Illinois, USA (UVA)"),
            array("id"=>18526, "text"=>"Red Brick Store, Nauvoo, Illinois, USA (UVA)"),
            array("id"=>18527, "text"=>"Salt Lake City, Utah, USA (UVA)")
        );

        echo json_encode($places);
        exit();
    }

    $query = $_GET['q'];

    $db = pg_connect($db_conn_string);

    $result = pg_query($db, "SELECT \"ID\" as \"id\", \"OfficialName\" as \"text\"  FROM public.\"Place\" WHERE lower(\"OfficialName\") ilike '%$query%' ORDER BY \"OfficialName\" ASC");
    if (!$result) {
        exit;
    }
    $places = pg_fetch_all($result);

    echo json_encode($places);
?>
