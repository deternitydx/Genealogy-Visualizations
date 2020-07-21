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
    var levels = "&levels=" + $('#levels').val();
    $(':checkbox:checked[name^=ids]').val(function() { 
        if (this.value.indexOf("&wife=1") != -1) {
            add = "&wife=-1";
            vals.push(this.value.substr(0, this.value.indexOf('&'))); 
        } else
            vals.push(this.value); 
    });
    var goTo = vals.join(",");
    var link = "../marriageflow.html?id=" + goTo + add + levels;
    console.log(link);
    window.location.href = link;
    return false;
}
</script>

</head>
<body>
<script>
$(document).ready( function () {
    $('#datatable').DataTable( {paging: false});
} );
</script>

<h1>Members of the Annointed Quorum</h1>

<p>Below is the list of all AQ members in our database.  Use the links to view chord and lineage flow diagrams for that individual's adult marriage.  To visualize lineage flows with multiple user-chosen family units, select them with the checkboxes in the second column and use the options at the bottom of the page to generate the visualization.</p>

<?php

include("../database.php");
$db = pg_connect($db_conn_string);

$result = pg_query($db, "SELECT DISTINCT ON (n.\"Last\",p.\"ID\") p.\"ID\",n.\"First\", n.\"Middle\", n.\"Last\",p.\"BirthDate\",p.\"DeathDate\", p.\"Gender\" FROM \"Person\" p LEFT JOIN \"Name\" n ON (p.\"ID\" = n.\"PersonID\" AND n.\"Type\" = 'authoritative') LEFT JOIN \"ChurchOrgMembership\" m ON (m.\"PersonID\" = p.\"ID\") LEFT JOIN \"ChurchOrganization\" c ON (m.\"ChurchOrgID\" = c.\"ID\") WHERE c.\"Name\" = 'Annointed Quorum' ORDER BY n.\"Last\", p.\"ID\" ASC");
if (!$result) {
    echo "An error occurred.\n";
    exit;
}

$arr = pg_fetch_all($result);
echo "<form>";
echo "<table id='datatable' class='display'>";
$json = array();
$first = true;
foreach ($arr as $mar) {
	$resa = array();
    if ($first) $headings = array();
    $addl = "";
    if ($mar["Gender"] == "Female")
        $addl = "&wife=1";

	foreach ($mar as $k=>$v) {
            //array_push($resa,"\"$k\": \"$v\"");
        if ($first) array_push($headings, "$k");
        if ($k == "ID"){
                array_push($resa, "<a href='../data_entry/individual.php?id=$v' title='Edit'>$v</a>");
                array_push($resa, "<input type=\"checkbox\" name=\"ids[]\" value=\"$v$addl\"/>");
                array_push($resa, "<a href=\"../chord.html?id=$v&temporal=1\">Temporal</a> - <a href=\"../chord.html?id=$v\">Static</a>");
                array_push($resa, "<a href=\"../marriageflow_temporal.html?id=$v$addl\">Temporal</a> - <a href=\"../marriageflow.html?id=$v$addl\">Static</a>");
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
echo "<p>Degrees of separation: <select id='levels'><option selected value='0'>0</option><option value='1'>1</option><option value='2'>2</option></select>  View: <button onClick='goSankey();'>Combined Lineage Flow</button> <br/>Note: this combined view is only available currently if all selected members are male or female.  No mixed gender displays are available at this time.</p>";
?>
</body>
</html>
