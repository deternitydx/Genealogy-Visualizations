<?php

include("../database.php");

$db = pg_connect($db_conn_string);

$result = pg_query($db, "SELECT DISTINCT  
    h.\"PersonID\" as \"HusbandID\",
    hn.\"Last\" as \"HusbandLast\", hn.\"First\" as \"HusbandFirst\",hp.\"DeathDate\" as \"HusbandDeath\", w.\"PersonID\" as \"WifeID\", wn.\"Last\" as \"WifeLast\", wn.\"First\" as \"WifeFirst\", wp.\"DeathDate\" as \"WifeDeath\",
    m.\"Type\", m.\"MarriageDate\", m.\"DivorceDate\",m.\"CancelledDate\"
    FROM public.\"Marriage\" m, public.\"PersonMarriage\" h, public.\"PersonMarriage\" w, public.\"Name\" wn, public.\"Name\" hn, public.\"Person\" wp, public.\"Person\" hp
    WHERE
    h.\"MarriageID\" = m.\"ID\" AND h.\"Role\" = 'Husband' AND w.\"MarriageID\" = m.\"ID\" AND w.\"Role\" = 'Wife' 
    AND wn.\"PersonID\" = w.\"PersonID\" AND wn.\"Type\" = 'authoritative'
    AND hn.\"PersonID\" = h.\"PersonID\" AND hn.\"Type\" = 'authoritative'
    AND wp.\"ID\" = w.\"PersonID\"
    AND hp.\"ID\" = h.\"PersonID\"
    AND m.\"MarriageDate\" < '1852-09-02'
    ORDER BY w.\"PersonID\" ASC, m.\"MarriageDate\" ASC;");
if (!$result) {
    echo "1An error occurred.\n";
    exit;
}
$arr = pg_fetch_all($result);

$names = array();
$json = array();
$head = array();
$data = array();
//echo "<tr><td>ID</td><td>Surname</td><td>Given Name</td><td>Birth Date</td><td>Death Date</td><td>Number of Wives</td></tr>";
foreach ($arr[0] as $k=>$v) 
	array_push($head, $k);
array_push($json, "<tr style='font-weight: bold;'><td>".implode("</td><td>", $head) . "</td></tr>");

foreach ($arr as $i => $mar) {
	$resa = array();

	// only add if the second marriage is < 1846
    //if ($i > 0 && $arr[$i-1]["HusbandID"] == $mar["HusbandID"] && $mar["MarriageDate"] != "" && $mar["MarriageDate"] < "1846-03-01"
    //    && $arr[$i-1]["WifeDeath"] != "" && ($arr[$i-1]["WifeDeath"] <= $mar["MarriageDate"]) ) { // and if the marriage is after the previous wife's death
    // check if the next marriage is before this wife's death.  If so, add this marriage
    if (($i < count($arr) - 1 && $mar["WifeID"] === $arr[$i+1]["WifeID"] && $arr[$i+1]["MarriageDate"] != ""
            && $arr[$i+1]["MarriageDate"] < "1852-09-02" && $mar["HusbandDeath"] != ""
            && ($mar["HusbandDeath"] >= $arr[$i+1]["MarriageDate"]))
            || // OR if this marriage is the last for a man and the last one was a plural marriage
            ($i < count($arr) - 1 && $i > 0 && 
                ($arr[$i+1]["WifeID"] != $mar["WifeID"] || // next person is not me
                $mar["HusbandDeath"] != "" && $mar["HusbandDeath"] <= $arr[$i+1]["MarriageDate"]) // or this wife died before next
            && $arr[$i-1]["WifeID"] === $mar["WifeID"] && $mar["MarriageDate"] != "" 
            && $mar["MarriageDate"] < "1852-09-02" && $arr[$i-1]["HusbandDeath"] != "" 
            && ($arr[$i-1]["HusbandDeath"] >= $mar["MarriageDate"]))
            || // OR this is the last marriage and the one before it was a plural marriage
            ($i == count($arr) - 1
            && $arr[$i-1]["WifeID"] == $mar["WifeID"] && $mar["MarriageDate"] != ""
            && $mar["MarriageDate"] < "1852-09-02" && $arr[$i-1]["HusbandDeath"] != ""
            && ($arr[$i-1]["HusbandDeath"] >= $mar["MarriageDate"]))

    ) {
		foreach ($mar as $k=>$v) {
			//array_push($resa,"\"$k\": \"$v\"");
			if ($k == "WifeID")
				array_push($resa, "<a href=\"../chord.html?id=$v\">$v</a>");
			else
				array_push($resa, "$v");
        }
        array_push($data, $mar);
		array_push($json, "<tr><td>" . implode("</td><td>", $resa) . "</td></tr>");

		$name = $mar["WifeLast"] . ", ". $mar["WifeFirst"];
		if (!in_array($name, $names))
			array_push($names, $name);
	
	}

}
$wives = array();
foreach ($data as $mar) {
    if (!isset($wives[$mar["WifeID"]]))
        $wives[$mar["WifeID"]] = array();

    // Since they are in order by wife, this will be in order and keep the latest
    $wives[$mar["WifeID"]][$mar["HusbandID"]] = $mar;
}

$final = array();
$finalh = array();
$names = array();
foreach ($wives as $i=>$w) {
    if (count($w) > 1) {
        $final[$i] = $w;
		$name = current($w)["WifeLast"] . ", ". current($w)["WifeFirst"];
        $names[$i]= $name;
    }
    foreach ($w as $k => $h) 
        $finalh[$k] = true;
}

$wives = $final;
$husbands = $finalh;

asort($names);

echo "<html><head><title>Polygamists</title></head><body>";
echo "<h1>Women with multiple marriages before Sep 1, 1852</h1>";
echo "<p><b>Total: </b>".count($names)."</p>";
echo "<p><b>Total Men: </b>".count($husbands)."</p>";
echo "<p><a href='#list'>List View</a> - <a href='#women'>Women Only</a></p>";

echo "<a name=\"list\"></a><h2>List View</h2>";
echo "<dl>";
echo "<dt><i>Wife Name</i></dt><dd><i>Marriage Date (Type): Husband Name (Death Date)</i></dd>";
foreach ($names as $i => $name) {
    $d = $wives[$i];
    echo "<dt>" . current($d)["WifeLast"]. ", " . current($d)["WifeFirst"] . " [".current($d)["WifeID"]."]</dt>";
    foreach ($d as $mar) {
        echo "<dd>" . $mar["MarriageDate"] . " <i>(".$mar["Type"].")</i>: " .$mar["HusbandLast"] . ", " .$mar["HusbandFirst"] ." [".$mar["HusbandID"]."] <i>(died " . $mar["HusbandDeath"].")</i></dd>";
    }
}
echo "</dl>";

echo "<a name='women'></a><h2>Women Only, Alphabetically</h2>";
sort($names);
foreach ($names as $name)
	echo "<br>$name";
?>
</body>
</html>

