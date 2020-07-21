<?php
include("../database.php");
    header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');

    /*
     * If there is no query, then we will return a default list
     * that is helpful to UVA.
     */
    if (!isset($_GET['q']) || strlen($_GET['q']) < 1) {
        $marriages = array(
            array("id"=>17902,"text"=>"Cutler, Alpheus to Lathrop, Lois (1843-11-15 : eternity) 17902"),
            array("id"=>19221,"text"=>"Kimball, Heber to Murray, Vilate (1841 : eternity) 19221"),
            array("id"=>18026,"text"=>"Taylor, John to Cannon, Leonora (1844-01-30 : eternity) 18026"),
            array("id"=>9952,"text"=>"Smith, Hyrum to Barden, Jerusha (?? : eternity) 9952"),
            array("id"=>17973,"text"=>"Young, Brigham to Works, Miriam (1843-05-29 : eternity) 17973"),
            array("id"=>5305,"text"=>"Miller, George to Fry, Mary Catherine (1844-08-15 : eternity) 5305"),
            array("id"=>17888,"text"=>"Morely, Isaac to Gunn, Lucy (1844-02-26 : eternity) 17888"),
            array("id"=>7597,"text"=>"Richards, Willard to Richards, Jenetta (1843-05-29 : eternity) 7597"),
            array("id"=>19962,"text"=>"Smith, John to Lyman, Clarissa (?? : eternity) 19962"),
            array("id"=>20330,"text"=>"Whitney, Newek K. to Smith, Elizabeth (1846-01-26 : eternity) 20330")
        );
        echo json_encode($marriages);
        exit();
    }

    $q = $_GET['q'];

    $db = pg_connect($db_conn_string);

    $query = "
        SELECT DISTINCT m.*, pl.\"OfficialName\" as \"PlaceName\", hn.\"First\" as \"HusbandFirst\", hn.\"Last\" as \"HusbandLast\", wn.\"First\" as \"WifeFirst\", wn.\"Last\" as \"WifeLast\" 

        FROM public.\"Marriage\" m

        LEFT JOIN public.\"PersonMarriage\" hpm ON hpm.\"MarriageID\" = m.\"ID\" AND hpm.\"Role\" = 'Husband'
        LEFT JOIN public.\"PersonMarriage\" wpm ON wpm.\"MarriageID\" = m.\"ID\" AND wpm.\"Role\" = 'Wife'

        LEFT JOIN public.\"Name\" hn ON hpm.\"PersonID\" = hn.\"PersonID\" AND hn.\"Type\" = 'authoritative'
        LEFT JOIN public.\"Name\" wn ON wpm.\"PersonID\" = wn.\"PersonID\" AND wn.\"Type\" = 'authoritative' 

        LEFT JOIN public.\"Place\" pl ON m.\"PlaceID\" = pl.\"ID\"

        WHERE 
        hn.\"First\" || ' ' || hn.\"Last\" ilike '%$q%'
        OR wn.\"First\" || ' ' || wn.\"Last\" ilike '%$q%'

        ORDER BY hn.\"Last\", hn.\"First\", wn.\"Last\", wn.\"First\" ASC";
    // Need to select join personmarriage with name for the husbands and wives and marriage for the type
    $result = pg_query($db, $query);
    if (!$result) {
        exit;
    }
    $results = pg_fetch_all($result);

    $marriages = array();

    foreach($results as $res) {
        array_push($marriages, array("id"=>$res["ID"], "text"=> $res["HusbandLast"] . ", " . $res["HusbandFirst"] . " to " . $res["WifeLast"] . ", " . $res["WifeFirst"] . " (" . $res["MarriageDate"] . " : " . $res["Type"] . ")"));
    }
    echo json_encode($marriages);
?>
