<html>
<head>
<title>Query View</title>
<link rel="stylesheet" type="text/css" href="../css/style.css"/>

<!-- jQuery -->
<script type="text/javascript" charset="utf8" src="../js/jquery-2.1.1.js"></script>
  
<!-- DataTables -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/dt/jszip-3.1.3/pdfmake-0.1.27/dt-1.10.15/b-1.3.1/b-colvis-1.3.1/b-flash-1.3.1/b-html5-1.3.1/b-print-1.3.1/cr-1.3.3/fc-3.2.2/fh-3.1.2/r-2.1.1/se-1.2.2/datatables.min.css"/>
<script type="text/javascript" src="https://cdn.datatables.net/v/dt/jszip-3.1.3/pdfmake-0.1.27/dt-1.10.15/b-1.3.1/b-colvis-1.3.1/b-flash-1.3.1/b-html5-1.3.1/b-print-1.3.1/cr-1.3.3/fc-3.2.2/fh-3.1.2/r-2.1.1/se-1.2.2/datatables.min.js"></script>

</head>
<body>
<script>
$(document).ready( function () {
    $('#datatable').DataTable( {
    paging: false,        dom: 'Bfrtip',
            buttons: [
                'copy', 'excel', 'pdf'
            ]
});
} );
</script>

<h1>Adoptions View</h1>
<?php

include("../database.php");
$db = pg_connect($db_conn_string);

$result = pg_query($db, "

select nms.\"Date\", nms.\"Type\", nms.\"AdopteeID\" as \"AID\", p.\"First\"||' '||p.\"Last\" as \"Adoptee\",
    m.\"ID\" as \"MID\",mn.\"First\"||' '||mn.\"Last\" as \"Mother\", f.\"ID\" as \"FID\", fn.\"First\"||' '||fn.\"Last\" as \"Father\", nms.\"AdopteeProxyID\" as \"AProxyID\"
from \"NonMaritalSealings\" nms
left outer join \"Name\" p on p.\"PersonID\" = nms.\"AdopteeID\" and p.\"Type\"='authoritative'
left outer join \"PersonMarriage\" m on m.\"MarriageID\" = nms.\"MarriageID\" and m.\"Role\" = 'Wife'
left outer join \"Name\" mn on mn.\"PersonID\" = m.\"PersonID\" and mn.\"Type\"='authoritative'
left outer join \"PersonMarriage\" f on f.\"MarriageID\" = nms.\"MarriageID\" and f.\"Role\" = 'Husband'
left outer join \"Name\" fn on fn.\"PersonID\" = f.\"PersonID\" and fn.\"Type\"='authoritative'
order by nms.\"Date\" asc, nms.\"Type\", p.\"Last\" asc, p.\"First\" asc; 

");
if (!$result) {
    echo "An error occurred.\n";
    exit;
}

$arr = pg_fetch_all($result);
echo "<table id='datatable' class='display'>";
$json = array();
$first = true;
foreach ($arr as $mar) {
	$resa = array();
    if ($first) $headings = array();

	foreach ($mar as $k=>$v) {
            //array_push($resa,"\"$k\": \"$v\"");
        if ($first) array_push($headings, "$k");
        if ($v == "") {
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

echo "</tbody></table>";
?>
</body>
</html>

