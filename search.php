<?php
date_default_timezone_set('America/New_York');
error_reporting(E_ALL);
ini_set("display_errors", 1);

    $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $split = explode("search.php", $url);
    $base_url = $split[0];
    // load the person
    if (!isset($_GET["first"]) && !isset($_GET["last"]) && !isset($_GET["birth"]) && !isset($_GET["death"])) {
        // creating a new person
        die("No parameters provided.  Cannot continue.");
    }
    $first = pg_escape_string((isset($_GET["first"]))?$_GET["first"]:"");
    $middle = pg_escape_string((isset($_GET["middle"]))?$_GET["middle"]:"");
    $last = pg_escape_string((isset($_GET["last"]))?$_GET["last"]:"");
    $birthdate = pg_escape_string((isset($_GET["birth"]))?$_GET["birth"]:"");
    $deathdate = pg_escape_string((isset($_GET["death"]))?$_GET["death"]:"");

    if($first == "" && $middle == "" && $last == "" && $birthdate == "" && $deathdate == ""){
        echo "Not enough parameters specified. Please try again.\n";
        exit;
    }

    include("database.php");
    $db = pg_connect($db_conn_string);

    $query_start = "SELECT * FROM (SELECT distinct on (p.\"ID\") p.\"ID\", concat(n.\"First\", ' ', n.\"Middle\", ' ', n.\"Last\", ' ', n.\"Suffix\") as \"FullName\", p.\"BirthDate\", p.\"DeathDate\" from \"Person\" p, \"Name\" n where p.\"ID\" = n.\"PersonID\" and n.\"Type\" = 'authoritative'";
    if($first != "") $query_start = $query_start."and n.\"First\" like '{$first}' ";
    if($middle != "") $query_start = $query_start."and n.\"Middle\" like '{$middle}' ";
    if($last != "") $query_start = $query_start."and n.\"Last\" ilike '{$last}' ";
    if($birthdate != "") $query_start = $query_start."and p.\"BirthDate\" like '%{$birthdate}%' ";
    if($deathdate != "") $query_start = $query_start."and p.\"DeathDate\" like '%{$deathdate}%' ";
    $query_start = $query_start.") m order by \"FullName\"";


    $result = pg_query($db, $query_start);
    if (!$result) {
        echo "An error occurred.\n";
        exit;
    }

    $arr = pg_fetch_all($result);
    $mars = [];
    $bchilds = [];
    $achilds = [];
    if($arr){
        foreach($arr as $p){

            $myID = $p["ID"];
            $result = pg_query($db, "
            
            select count(*) from ( select distinct m.\"ID\" from \"Person\" p, \"Marriage\" m, \"PersonMarriage\" pm
            where p.\"ID\" = {$myID}
            and p.\"ID\" = pm.\"PersonID\"
            and m.\"ID\" = pm.\"MarriageID\"
            and pm.\"Role\" in ('Husband', 'Wife')) m;
            
            ");

            $mars[$p["ID"]] = pg_fetch_all($result)[0]["count"];

            $result = pg_query($db, "

            SELECT count(cp.\"ID\")
            from public.\"Person\" p, public.\"Marriage\" m, public.\"PersonMarriage\" pm, \"Person\" cp
            where p.\"ID\" = {$myID}
            and pm.\"PersonID\" = p.\"ID\"
            and pm.\"MarriageID\" = m.\"ID\"
            and pm.\"Role\" not in ('Officiator', 'ProxyHusband', 'ProxyWife')
            and cp.\"BiologicalChildOfMarriage\" = m.\"ID\"
            
            ");

            $bchilds[$p["ID"]] = pg_fetch_all($result)[0]["count"];

            $result = pg_query($db, "

            SELECT count(cp.\"ID\")
            from public.\"Person\" p, public.\"Marriage\" m, public.\"PersonMarriage\" pm, \"Person\" cp, \"NonMaritalSealings\" nms
            where p.\"ID\" = {$myID}
            and pm.\"PersonID\" = p.\"ID\"
            and pm.\"MarriageID\" = m.\"ID\"
            and (nms.\"AdopteeID\" = cp.\"ID\" or nms.\"AdopteeProxyID\" = cp.\"ID\")
            and (nms.\"MarriageID\" = m.\"ID\" or nms.\"MarriageProxyID\" = m.\"ID\")
            and pm.\"Role\" not in ('Officiator', 'ProxyHusband', 'ProxyWife')

            ");

            $achilds[$p["ID"]] = pg_fetch_all($result)[0]["count"];

        }
    }
    /*
     * Display Dates
     *
     * Takes a YYYY-MM-DD date string and splits it out appropriately.  Then, will print out the
     * html required to display that date as a data entry element.  The prefix and suffix params
     * are used around the portion of the date (day, month, or year) to define the name of the 
     * input box.  Currently, it uses the format:
     *              YYYY    Month (select)  DD
     */
    function displayDate($datestr, $prefix, $suffix) {
        $dateSplit = explode("-", $datestr);
        if (!isset($dateSplit[0]) || empty($dateSplit[0]))
            $dateSplit[0] = "";
        if (!isset($dateSplit[1]) || empty($dateSplit[1]))
            $dateSplit[1] = "";
        if (!isset($dateSplit[2]) || empty($dateSplit[2]))
            $dateSplit[2] = "";

        $month = '';
        for( $i = 1; $i <= 12; $i++ ) {
            if ($i == $dateSplit[1])
                $month = date( 'F', mktime( 0, 0, 0, $i + 1, 0, 0 ) );
        }
        echo "<span>{$dateSplit[2]}</span> \n";
        echo "<span>$month</span> \n";
        echo "<span>{$dateSplit[0]}</span>\n";

    }

    function display($kind, $val, $label, $isdate=false, $ismarriage=false) {
        if (isset($kind[$val])) {
            $trimmed = trim($kind[$val]);
            if (!empty($trimmed)) {
                echo "<p class='card-text'><span style='width: 150px; font-weight: bold;'>$label: </span> ";
                if ($isdate)
                    displayDate($kind[$val], "", "");
                else {
                    $parts = explode(' ', $kind[$val]);
                    $last = array_pop($parts);
                    if (!$ismarriage && is_numeric($last)) 
                        echo "<a href='?id=$last'>".implode(" ", $parts)."</a>";
                    else
                        echo $kind[$val];
                }
                echo "</p>\n";
            }
        }
    }

?>
<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">

    <link rel="stylesheet" href="css/font-awesome.min.css">
    <style>
        body {
            overflow-y: scroll;
        }
        h1 {
            margin-top: 30px;
            margin-bottom: 20px;
        }
        .card {
            margin-bottom: 20px;
        }
        .marriage-type {
            font-style: italic;
        }
        .marinfo {
            text-align: center;
        }
        .datetype {
            margin-top: 0px;
            padding-top: 0px;
            margin-left: 20px;
        }
        .pull-right{
            text-align:right;
        }
        .grid-container{
            display:grid;
            grid-template-columns: repeat(5, 20%);
        }
    </style>
    <title>View Person</title>
    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>
  </head>
  <body>
    <div class="container">
    <h1>Search Results</h1>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item"><a href="#">Search</a></li>
        <li class="breadcrumb-item active" aria-current="page">Search Results</li>
      </ol>
    </nav>

    <div class="row mb-3">
        <div class="col-md-12 themed-grid-col">
            <div class="card text-center">
              <div class="card-body text-left">
            <?php if($arr){?>
              <?php foreach($arr as $res){ ?>
              <div class="card">
                              <div class="card-header">
                              <a href='person.php?id=<?=$res["ID"]?>'><?=$res["FullName"]?></a>
                                
                              </div>
                              <div class="card-body grid-container">
                                    <span style="grid-column: 1;"><b>Birth Date: </b><?=($res["BirthDate"] != null)?$res["BirthDate"]:"UNK"?></span>
                                    <span style="grid-column: 2;"><b>Death Date: </b><?=($res["DeathDate"] != null)?$res["DeathDate"]:"UNK"?></span>
                                    <span style="grid-column: 3;"><b>Spouses: </b><?=$mars[$res["ID"]]?></span>
                                    <span style="grid-column: 4;"><b>Children: </b><?=$bchilds[$res["ID"]]?></span>
                                    <span style="grid-column: 5;"><b>Adoptions: </b><?=$achilds[$res["ID"]]?></span>
                              </div>
                            </div>
                                <?php }}else echo "<h5 style='text-align:center'>Your search returned no results.</h5>"; ?>
              </div>
            </div>
        </div>
    </div>
  </body>
</html>
