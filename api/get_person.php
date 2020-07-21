<?php
include("../database.php");
    header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');

    /*
     * If there is no query, then we will return a default list
     * that is helpful to UVA.
     */
    if (!isset($_GET['q']) || strlen($_GET['q']) < 1) {
        $people = array(
            array("id"=>25079, "text"=>"AML Amasa M. Lyman (1813-03-30 - 1877-02-04) 25079"),
            array("id"=>615, "text"=>"BY Brigham Young (1801-06-01 - 1877-08-29) 615"),
            array("id"=>447, "text"=>"CCR Charles C. Rich (1809-08-21 - 1883-11-17) 447"),
            array("id"=>36761, "text"=>"DS Daniel Spencer (1794-07-20 - 1868-12-08) 36761"),
            array("id"=>1351, "text"=>"ETB Ezra Taft Benson (1811-02-22 - 1869-09-03) 1351"),
            array("id"=>484, "text"=>"GAS George A. Smith (1817-06-26 - 1875-09-01) 484"),
            array("id"=>15277, "text"=>"GM George Miller (1794-11-25 - 1856-01-01) 15277"),
            array("id"=>5720, "text"=>"HCK Heber C. Kimball (1801-06-14 - 1868-06-22) 5720"),
            array("id"=>15728, "text"=>"IM Isaac Morley (1786-03-11 - 1864-07-21) 15728"),
            array("id"=>495, "text"=>"JS Joseph Smith () 495"),
            array("id"=>32267, "text"=>"JT John Taylor (1808-11-01 - 1887-07-25) 32267"),
            array("id"=>31692, "text"=>"OH Orson Hyde (1805-01-08 - 1878-11-28) 31692"),
            array("id"=>425, "text"=>"OP Orson Pratt (1811-09-19 - 1881-10-03) 425"),
            array("id"=>7727, "text"=>"OS Orson Spencer (1802-03-14 - 1855-10-15) 7727"),
            array("id"=>428, "text"=>"PPP Parley P. Pratt (1807-04-12 - 1857-05-13) 428"),
            array("id"=>1496, "text"=>"WdS Willard Snow (1811-11-06 - 1853-08-21) 1496"),
            array("id"=>19111, "text"=>"WF Winslow Farr (1794-01-12 - 1867-08-25) 19111"),
            array("id"=>282, "text"=>"WH William Huntington (1784-03-28 - 1846-08-19) 282"),
            array("id"=>8744, "text"=>"WmS William Snow (1806-12-14 - 1879-05-19) 8744"),
            array("id"=>34674, "text"=>"WWP William W. Phelps (1792-02-17 - 1872-03-06) 34674"),
            array("id"=>12094, "text"=>"ZC Zebedee Coltrin (1804-09-07 - 1887-07-21) 12094")
        );

        echo json_encode($people);
        exit();
    }
    $q = $_GET['q'];

    $db = pg_connect($db_conn_string);

    $query = "
        SELECT DISTINCT p.*, n.\"First\", n.\"Middle\", n.\"Last\", n.\"Prefix\", n.\"Suffix\",  n.\"Type\"

        FROM public.\"Name\" n

        LEFT JOIN public.\"Person\" p ON p.\"ID\" = n.\"PersonID\" 
        
        WHERE 
        n.\"First\" || ' ' || n.\"Last\" || ' ' || p.\"ID\" ilike '%$q%' OR
        n.\"Last\" || ', ' || n.\"First\" || ' ' || p.\"ID\" ilike '%$q%' 

        ORDER BY n.\"Last\", n.\"First\" ASC";
    $result = pg_query($db, $query);
    if (!$result) {
        exit;
    }
    $results = pg_fetch_all($result);

    $people = array();

    foreach($results as $res) {
        $n = array();
        if (isset($res["Prefix"]) && !empty($res["Prefix"]))
            array_push($n, $res["Prefix"]);
        if (isset($res["First"]) && !empty($res["First"]))
            array_push($n, $res["First"]);
        if (isset($res["Middle"]) && !empty($res["Middle"]))
            array_push($n, $res["Middle"]);
        if (isset($res["Last"]) && !empty($res["Last"]))
            array_push($n, $res["Last"]);
        if (isset($res["Suffix"]) && !empty($res["Suffix"]))
            array_push($n, $res["Suffix"]);
        array_push($people, array("id"=>$res["ID"], "text"=> implode(" ", $n) . " (" . $res["BirthDate"] . " -- " . $res["DeathDate"] . ") " . $res["ID"]));
    }
    echo json_encode($people);
?>
