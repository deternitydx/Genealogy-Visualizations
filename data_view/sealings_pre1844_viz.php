<html>
<head>
<title>Sealings</title>
<!-- DataTables CSS -->
<link rel="stylesheet" type="text/css" href="../css/style.css"/>
<link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.2/css/jquery.dataTables.css"/>
  
<!-- jQuery -->
<script type="text/javascript" charset="utf8" src="../js/jquery-2.1.1.js"></script>
  
<!-- DataTables -->
<script type="text/javascript" charset="utf8" src="//cdn.datatables.net/1.10.2/js/jquery.dataTables.js"></script>

</head>
<style>
body {
    width: 100%;
    padding: 10px;
}

.year {
    width: 100%;
    clear: both;
    display: block;
    float: left;
    margin-bottom: 40px;
}

.month {
    width: 7%;
    float: left;
    clear: none;
}

.heading {
    width: 100%;
    border-bottom: 1px solid #000;
}

.marriage {
    width: 25px;
    height: 25px;
    border: 1px solid;
    margin: 3px;
    float: left;
    clear: none;
    font-size: 10px;
    color: #000;
    text-align: center;
    padding: 2px;
}

.first {
    border-color: #31B404;
    background-color: #D8F6CE;
}

.additional {
    border-color: #0B2161;
    background-color: #81BEF7;
}

.resealed {
    border-color: #8A0808;
    background-color: #F5A9A9;
}

.legend {
    border: 1px solid #000;
    padding: 4px;
    margin-bottom: 90px;
    background-color: #ddd;
    height: 150px;
    width: 300px;
}

.legend .marriage {
    height: 8px;
    width: 8px;
    display: inline-block;
    clear: left;
}

.yearheading {
    padding-bottom: 0px;
    margin-bottom: 2px;
}

</style>

<body>
<script>
$(document).ready( function () {
    $('#datatable').DataTable( {paging: false});
} );
</script>

<h1>Sealings Before July 1, 1844</h1>

<div class="legend">
<h3>Legend</h3>

<div class="marriage first"></div> First Marriage <br>
<div class="marriage additional"></div> Additional Marriage <br>
<div class="marriage resealed"></div> Resealing to same wife <br>

</div>

<?php

//header('Content-type: application/json');

include("../database.php");
$db = pg_connect($db_conn_string);

$result = pg_query($db, "SELECT DISTINCT h.\"PersonID\" as \"HusbandID\", h.\"First\" as \"HusbandFirst\", h.\"Last\" as \"HusbandLast\", w.\"PersonID\" as \"WifeID\", w.\"First\" as \"WifeFirst\", w.\"Last\" as \"WifeLast\",m.\"Type\",m.\"MarriageDate\" as \"Date\", p.\"OfficialName\" as \"Place\", m.\"PrivateNotes\" as \"Notes\" from \"Marriage\" m LEFT JOIN \"Place\" p ON p.\"ID\"=m.\"PlaceID\",\"PersonMarriage\" pm, \"PersonMarriage\" pmw, \"Name\" h, \"Name\" w WHERE  m.\"ID\" = pm.\"MarriageID\" AND m.\"ID\" = pmw.\"MarriageID\" AND h.\"PersonID\"=pm.\"PersonID\" AND pm.\"Role\"='Husband' AND h.\"Type\" = 'authoritative' AND w.\"PersonID\"=pmw.\"PersonID\" AND pmw.\"Role\"='Wife' AND w.\"Type\" = 'authoritative' AND \"MarriageDate\" < '1844-07-01' AND m.\"Type\" IN ('eternity', 'time') ORDER BY m.\"MarriageDate\"");
if (!$result) {
    echo "An error occurred.\n";
    exit;
}

$arr = pg_fetch_all($result);
$hSeen = array();
$mSeen = array();

$json = array();
$first = true;
foreach ($arr as &$mar) {
    $mar["status"] = "first";
    if (isset($hSeen[$mar["HusbandID"]]))
        $mar["status"] = "additional";
    $hSeen[$mar["HusbandID"]] = true;
    if (isset($mSeen["{$mar["HusbandID"]} - {$mar["WifeID"]}"]))
        $mar["status"] = "resealed";
    $mSeen["{$mar["HusbandID"]} - {$mar["WifeID"]}"] = true;
}

$year = 0000;
$month = 01;
$sorted = array();
foreach ($arr as $mar) {
    $date = explode("-", $mar["Date"]);
    if (!isset($date[1]))
        $date[1] = 0;
    else
        $date[1] = (int) $date[1];
    if (!isset($date[2]))
        $date[2] = 0;

    if ($date[0] > $year) {
        $year = $date[0];
        $sorted[$year] = array();
        for ($i = 0; $i <= 12; $i++) 
            $sorted[$year][$i] = array();
    }

    array_push($sorted[$year][$date[1]], $mar);
}

foreach ($sorted as $year => $months) {

    echo "<h2 class='yearheading'>$year</h2>\n";
    echo "<div class='year'>\n";
    foreach ($months as $month => $data) {
        echo "<div class='month'>\n<div class='heading'>$month</div>\n";
        foreach ($data as $seal) {
            echo "<div class='marriage {$seal['status']}' title=\"".$seal["HusbandFirst"]." ".$seal["HusbandLast"]." to ".$seal["WifeFirst"]." ".$seal["WifeLast"]." (".$seal["Date"].")\">".substr($seal["HusbandFirst"],0,1).substr($seal["HusbandLast"],0,1)."</div> ";
        }
        echo "</div>\n";
    }
    echo "</div>\n";
}

?>
</body>
</html>
