<html>
<head>
<title>Annointed Quorum Members</title>
<!-- DataTables CSS -->
<link rel="stylesheet" type="text/css" href="../css/style.css"/>
<link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.2/css/jquery.dataTables.css"/>
  
<!-- jQuery -->
<script type="text/javascript" charset="utf8" src="../js/jquery-2.1.1.js"></script>
  
<!-- DataTables -->
<script type="text/javascript" charset="utf8" src="//cdn.datatables.net/1.10.2/js/jquery.dataTables.js"></script>

<script type="text/javascript">
function goSankey() {
    var vals = new Array();
    var add = "";
    $(':checkbox:checked[name^=ids]').val(function() { 
        if (this.value.indexOf("&wife=1") != -1) {
            add = "&wife=-1";
            vals.push(this.value.substr(0, this.value.indexOf('&'))); 
        } else
            vals.push(this.value); 
    });
    var goTo = vals.join(",");
    var link = "../marriageflow.html?id=" + goTo + add;
    console.log(link);
    window.location.href = link;
    return false;
}
</script>

<style>
/*
td {
   padding: 4px;
   margin: 0px;
   border: 1px solid black;
}

table {
   border: 1px solid black;
   border-spacing: 0px;
}

tr {
   border: 1px solid black;
}

th {
   color: #ffffff;
   background: #444444;
}
*/
</style>
</head>
<body>
<script>
$(document).ready( function () {
    $('#datatable').DataTable( {paging: false});
} );
</script>

<h1>Members of the Annointed Quorum with Birth and Death Places</h1>
<?php

include("../database.php");
$db = pg_connect($db_conn_string);

$result = pg_query($db, "SELECT DISTINCT ON (n.\"Last\",p.\"ID\") p.\"ID\",n.\"First\", n.\"Middle\", n.\"Last\",p.\"BirthDate\",bp.\"OfficialName\" as \"BirthPlace\", p.\"DeathDate\", dp.\"OfficialName\" as \"DeathPlace\" FROM \"Person\" p LEFT JOIN \"Name\" n ON (p.\"ID\" = n.\"PersonID\" AND n.\"Type\" = 'authoritative') LEFT JOIN \"Place\" bp ON (bp.\"ID\" = p.\"BirthPlaceID\") LEFT JOIN \"Place\" dp ON (dp.\"ID\" = p.\"DeathPlaceID\") LEFT JOIN \"ChurchOrgMembership\" m ON (m.\"PersonID\" = p.\"ID\") LEFT JOIN \"ChurchOrganization\" c ON (m.\"ChurchOrgID\" = c.\"ID\") WHERE c.\"Name\" = 'Annointed Quorum' ORDER BY n.\"Last\", p.\"ID\" ASC");
if (!$result) {
    echo "An error occurred.\n";
    exit;
}

$tmparr = pg_fetch_all($result);
$arr = array();
$currlevel = array();
foreach ($tmparr as $v) {
	$currlevel[$v["ID"]] = $v;
}

echo "\n\n<!-- Run notes \n";

if (isset($_GET["levels"])) {
	for ($counter = 0; $counter < $_GET["levels"]; $counter++) {
		echo "       Running for level $counter\n";
		$children = array();
		foreach ($currlevel as $person) {
			//echo "   Looking for children of {$person['ID']}.\n";
			 //echo "      Code: SELECT DISTINCT ON (n.\"Last\",p.\"ID\") p.\"ID\",n.\"First\", n.\"Middle\", n.\"Last\",p.\"BirthDate\",bp.\"OfficialName\" as \"BirthPlace\", p.\"DeathDate\", dp.\"OfficialName\" as \"DeathPlace\" FROM \"Person\" p LEFT JOIN \"Name\" n ON (p.\"ID\" = n.\"PersonID\" AND n.\"Type\" = 'authoritative') LEFT JOIN \"Place\" bp ON (bp.\"ID\" = p.\"BirthPlaceID\") LEFT JOIN \"Place\" dp ON (dp.\"ID\" = p.\"DeathPlaceID\") LEFT JOIN \"PersonMarriage\" pm ON (pm.\"MarriageID\" = p.\"BiologicalChildOfMarriage\") WHERE pm.\"PersonID\" = {$person["ID"]} AND pm.\"Role\" in ('Husband', 'Wife') ORDER BY n.\"Last\", p.\"ID\" ASC\n";
			
			$result = pg_query($db, "SELECT DISTINCT ON (n.\"Last\",p.\"ID\") p.\"ID\",n.\"First\", n.\"Middle\", n.\"Last\",p.\"BirthDate\",bp.\"OfficialName\" as \"BirthPlace\", p.\"DeathDate\", dp.\"OfficialName\" as \"DeathPlace\" FROM \"Person\" p LEFT JOIN \"Name\" n ON (p.\"ID\" = n.\"PersonID\" AND n.\"Type\" = 'authoritative') LEFT JOIN \"Place\" bp ON (bp.\"ID\" = p.\"BirthPlaceID\") LEFT JOIN \"Place\" dp ON (dp.\"ID\" = p.\"DeathPlaceID\") LEFT JOIN \"PersonMarriage\" pm ON (pm.\"MarriageID\" = p.\"BiologicalChildOfMarriage\") WHERE pm.\"PersonID\" = {$person["ID"]} AND pm.\"Role\" in ('Husband', 'Wife') ORDER BY n.\"Last\", p.\"ID\" ASC");
			if (!$result) {
				echo "An error occurred.\n";
				exit;
			}

			$tmp = pg_fetch_all($result);
			foreach ($tmp as $v)
				$children[$v["ID"]] = $v;
		}
		foreach ($currlevel as $v) 
			$arr[$v["ID"]] = $v;
		$currlevel = $children;
		//print_r($children);
	}
	foreach ($children as $v)
		$arr[$v["ID"]] = $v;
} else {
	$arr = $currlevel;
}

echo "-->\n\n";
echo "<form>";
echo "<table id='datatable' class='display'>";
$json = array();
$first = true;
foreach ($arr as $mar) {
	$resa = array();
    if ($first) $headings = array();
    $addl = "";

	foreach ($mar as $k=>$v) {
            //array_push($resa,"\"$k\": \"$v\"");
        if ($first) array_push($headings, "$k");
        if ($k == "ID"){
                array_push($resa, "$v");
                array_push($resa, "<input type=\"checkbox\" name=\"ids[]\" value=\"$v$addl\"/>");
                array_push($resa, "<a href=\"../chord.html?id=$v&temporal=1&$addl\">Temporal</a> - <a href=\"../chord.html?id=$v$addl\">Static</a>");
                array_push($resa, "<a href=\"../marriageflow.html?id=$v$addl\">View</a>");
                if ($first) array_push($headings, " ");
                if ($first) array_push($headings, "Chord");
                if ($first) array_push($headings, "Lineage");
        } else if ($v == "") {
                array_push($resa, "&nbsp;");
        } else {
                array_push($resa, "$v");
        }
	}
	
	
	if ($first) 
		array_push($json, "<thead><tr><th>" . implode("</th><th>", $headings) . "</th></tr></thead><tbody>");
	array_push($json, "<tr><td>" . implode("</td><td>", $resa) . "</td></tr>");
	$first = false;


}
	echo implode("", $json);

echo "</tbody></table></form>";
echo "<h3>Actions available for selected members</h3>";
echo "<p><button onClick='goSankey();'>Combined Marriage Flow View</button> Note: this view is only available currently if all those members selected are male or female.  No mixed gender isplays are available at this time.</p>";
?>
</body>
</html>
