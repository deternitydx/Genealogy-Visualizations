<?php

include("../database.php");

$db = pg_connect($db_conn_string);

$result = pg_query($db, "SELECT DISTINCT  
    h.\"PersonID\" as \"HusbandID\", 
    hn.\"Last\" as \"HusbandLast\", hn.\"First\" as \"HusbandFirst\", w.\"PersonID\" as \"WifeID\", wn.\"Last\" as \"WifeLast\", wn.\"First\" as \"WifeFirst\", wp.\"DeathDate\" as \"WifeDeath\",
    m.\"Type\", m.\"MarriageDate\", m.\"DivorceDate\",m.\"CancelledDate\"
    FROM public.\"Marriage\" m, public.\"PersonMarriage\" h, public.\"PersonMarriage\" w, public.\"Name\" wn, public.\"Name\" hn, public.\"Person\" wp
    WHERE
    h.\"MarriageID\" = m.\"ID\" AND h.\"Role\" = 'Husband' AND w.\"MarriageID\" = m.\"ID\" AND w.\"Role\" = 'Wife' 
    AND wn.\"PersonID\" = w.\"PersonID\" AND wn.\"Type\" = 'authoritative'
    AND hn.\"PersonID\" = h.\"PersonID\" AND hn.\"Type\" = 'authoritative'
    AND wp.\"ID\" = w.\"PersonID\"
    AND m.\"MarriageDate\" < '1842-06-28'
    ORDER BY h.\"PersonID\" ASC, m.\"MarriageDate\" ASC;");
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
    //if ($i > 0 && $arr[$i-1]["HusbandID"] == $mar["HusbandID"] && $mar["MarriageDate"] != "" && $mar["MarriageDate"] < "1842-06-28"
    //    && $arr[$i-1]["WifeDeath"] != "" && ($arr[$i-1]["WifeDeath"] <= $mar["MarriageDate"]) ) { // and if the marriage is after the previous wife's death
    // check if the next marriage is before this wife's death.  If so, add this marriage
    if (($i < count($arr) - 1 && $mar["HusbandID"] === $arr[$i+1]["HusbandID"] 
            && $arr[$i+1]["MarriageDate"] < "1842-06-28" )
            || // OR if this marriage is the last for a man and the last one was a plural marriage
            ($i < count($arr) - 1 && $i > 0 && 
                ($arr[$i+1]["HusbandID"] != $mar["HusbandID"] || // next person is not me
                $mar["WifeDeath"] != "" && $mar["WifeDeath"] <= $arr[$i+1]["MarriageDate"]) // or this wife died before next
            && $arr[$i-1]["HusbandID"] === $mar["HusbandID"] && $mar["MarriageDate"] != "" 
            && $mar["MarriageDate"] < "1842-06-28" && $arr[$i-1]["WifeDeath"] != "" 
            && ($arr[$i-1]["WifeDeath"] >= $mar["MarriageDate"]))
            || // OR this is the last marriage and the one before it was a plural marriage
            ($i == count($arr) - 1
            && $arr[$i-1]["HusbandID"] == $mar["HusbandID"] && $mar["MarriageDate"] != ""
            && $mar["MarriageDate"] < "1842-06-28" && $arr[$i-1]["WifeDeath"] != ""
            && ($arr[$i-1]["WifeDeath"] >= $mar["MarriageDate"]))

    ) {
		foreach ($mar as $k=>$v) {
			//array_push($resa,"\"$k\": \"$v\"");
			if ($k == "HusbandID")
				array_push($resa, "<a href=\"../chord.html?id=$v\">$v</a>");
			else
				array_push($resa, "$v");
        }
        array_push($data, $mar);
		array_push($json, "<tr><td>" . implode("</td><td>", $resa) . "</td></tr>");

		$name = $mar["HusbandLast"] . ", ". $mar["HusbandFirst"];
		if (!in_array($name, $names))
			array_push($names, $name);
	
	}

}

echo "<html><head><title>Polygamists</title></head><body>";
echo "<h1>Men with multiple marriages before Jun 28, 1842</h1>";
echo "<p><b>Total: </b>".count($names)."</p>";
echo "<p><a href='#list'>List View</a> -  <a href='#table'>Table View</a> - <a href='#men'>Men Only</a></p>";

echo "<a name=\"list\"></a><h2>List View</h2>";
echo "<dl>";
echo "<dt><i>Husband Name</i></dt><dd><i>Marriage Date (Type): Wife Name (Death Date)</i></dd>";
foreach ($data as $i => $d) {
    if ($i == 0 || $d["HusbandID"] != $data[$i-1]["HusbandID"])
        echo "<dt>" . $d["HusbandLast"]. ", " . $d["HusbandFirst"] . "</dt>";
    echo "<dd>" . $d["MarriageDate"] . " <i>(".$d["Type"].")</i>: " .$d["WifeLast"] . ", " .$d["WifeFirst"] ." <i>(died " . $d["WifeDeath"].")</i></dd>";
}
echo "</dl>";

echo "<a name='table'></a><h2>Table View</h2>";
echo "<table border='1'>";
echo implode("", $json);
echo "</table>";

echo "<a name='men'></a><h2>Men Only, Alphabetically</h2>";
sort($names);
foreach ($names as $name)
	echo "<br>$name";
?>
</body>
</html>

