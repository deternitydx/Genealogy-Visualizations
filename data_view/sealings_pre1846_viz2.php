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

h2 {
    clear: both;
}

.year {
    width: 93%;
    clear: both;
    display: block;
    float: left;
    margin-bottom: 40px;
}

.month {
    width: 7.6%;
    float: left;
    clear: none;
}

.month.unknown {
    float: right;
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

.wasCivil {
    color: #444;
-moz-border-radius: 15px;
-webkit-border-radius: 15px;
border-radius: 15px; /* future proofing */
-khtml-border-radius: 15px; /* for old Konqueror browsers */
}

.legend {
    border: 1px solid #000;
    padding: 10px;
    margin-bottom: 90px;
    background-color: #ddd;
    height: 200px;
    width: 400px;
}

.legend .marriage {
    height: 6px;
    width: 6px;
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

<h1>Sealings Before End of 1846</h1>

<div class="legend">
<h3>Legend</h3>

<div class="marriage first"></div> First Sealing NOT to civil wife<br>
<div class="marriage first wasCivil"></div> First sealing was to civil wife<br>
<div class="marriage additional"></div> Additional Sealing (not to a civil wife)<br>
<div class="marriage additional wasCivil"></div> Sealing to a civil wife that was not the first sealing<br>
<div class="marriage resealed"></div> Re-sealing to same wife <br>
<div class="marriage wasCivil"></div> Sealed event to a Civil Wife<br>
</div>
<?php

//header('Content-type: application/json');

include("../database.php");
$db = pg_connect($db_conn_string);

$result = pg_query($db, "SELECT DISTINCT h.\"PersonID\" as \"HusbandID\", h.\"First\" as \"HusbandFirst\", h.\"Last\" as \"HusbandLast\", w.\"PersonID\" as \"WifeID\", w.\"First\" as \"WifeFirst\", w.\"Last\" as \"WifeLast\",m.\"Type\",m.\"MarriageDate\" as \"Date\", p.\"OfficialName\" as \"Place\", m.\"PrivateNotes\" as \"Notes\" from \"Marriage\" m LEFT JOIN \"Place\" p ON p.\"ID\"=m.\"PlaceID\",\"PersonMarriage\" pm, \"PersonMarriage\" pmw, \"Name\" h, \"Name\" w WHERE  m.\"ID\" = pm.\"MarriageID\" AND m.\"ID\" = pmw.\"MarriageID\" AND h.\"PersonID\"=pm.\"PersonID\" AND pm.\"Role\"='Husband' AND h.\"Type\" = 'authoritative' AND w.\"PersonID\"=pmw.\"PersonID\" AND pmw.\"Role\"='Wife' AND w.\"Type\" = 'authoritative' AND \"MarriageDate\" <= '1846-12-31' ORDER BY m.\"MarriageDate\"");
if (!$result) {
    echo "An error occurred.\n";
    exit;
}

$arr = pg_fetch_all($result);
$hSeen = array();
$mSeen = array();
$cSeen = array();

$json = array();
$first = true;
foreach ($arr as &$mar) {
    if ($mar["Type"] == 'time' || $mar["Type"] == 'eternity') {
        $mar["status"] = "first";
        if (isset($hSeen[$mar["HusbandID"]]))
            $mar["status"] = "additional";
        $hSeen[$mar["HusbandID"]] = true;
        if (isset($mSeen["{$mar["HusbandID"]} - {$mar["WifeID"]}"]))
            $mar["status"] = "resealed";
        $mSeen["{$mar["HusbandID"]} - {$mar["WifeID"]}"] = true;
        if (isset($cSeen["{$mar["HusbandID"]} - {$mar["WifeID"]}"]))
            $mar["status"] .= " wasCivil";
    } else {
        $cSeen["{$mar["HusbandID"]} - {$mar["WifeID"]}"] = true;
    }
}

$year = 0000;
$month = 01;
$sorted = array();
foreach ($arr as $mar) {
    if ($mar["Type"] == 'time' || $mar["Type"] == 'eternity') {
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
}

foreach ($sorted as $year => $months) {

    echo "<h2 class='yearheading'>$year</h2>\n";
    echo "<div class='year'>\n";
    foreach ($months as $month => $data) {
        $known = "";
        $tmpMonth = $month;
        if ($month == 0) {
            $known = " unknown";
            $tmpMonth = "???";
        }
        echo "<div class='month$known'>\n<div class='heading'>$tmpMonth</div>\n";
        foreach ($data as $seal) {
            echo "<div class='marriage {$seal['status']}' title=\"".$seal["HusbandFirst"]." ".$seal["HusbandLast"]." to ".$seal["WifeFirst"]." ".$seal["WifeLast"].
                " (".$seal["Date"]." ".$seal["Type"].")\">".
                substr($seal["HusbandFirst"],0,1).substr($seal["HusbandLast"],0,1);
            if ($seal["Type"] == "time")
                echo "*";
            echo "</div> ";
        }
        echo "</div>\n";
    }
    echo "</div>\n";
}

?>
</body>
</html>
