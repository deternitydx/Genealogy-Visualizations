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

<h1>Civil/Sealed Marriages with Participants Under 18</h1>
<?php

include("../database.php");
$db = pg_connect($db_conn_string);

$result = pg_query($db, "
SELECT DISTINCT  
    h.\"PersonID\" AS \"HusbandID\",
    hn.\"Last\" AS \"HusbandLast\", hn.\"First\" AS \"HusbandFirst\", AGE(TO_TIMESTAMP(m.\"MarriageDate\", 'YYYY-MM-DD'), TO_TIMESTAMP(hp.\"BirthDate\", 'YYYY-MM-DD')) AS \"HusbandAgeAtMarriage\", w.\"PersonID\" AS \"WifeID\", wn.\"Last\" AS \"WifeLast\", wn.\"First\" AS \"WifeFirst\", AGE(TO_TIMESTAMP(m.\"MarriageDate\", 'YYYY-MM-DD'), TO_TIMESTAMP(wp.\"BirthDate\", 'YYYY-MM-DD')) AS \"WifeAgeAtMarriage\", wp.\"DeathDate\" AS \"WifeDeath\",
    m.\"Type\", m.\"MarriageDate\",
    GREATEST(AGE(TO_TIMESTAMP(hp.\"BirthDate\", 'YYYY-MM-DD'), TO_TIMESTAMP(wp.\"BirthDate\", 'YYYY-MM-DD')), AGE(TO_TIMESTAMP(wp.\"BirthDate\", 'YYYY-MM-DD'), TO_TIMESTAMP(hp.\"BirthDate\", 'YYYY-MM-DD'))) AS \"AgeDifferenceAtMarriage\"
    FROM public.\"Marriage\" m,  public.\"PersonMarriage\" h, \"Person\" hp, public.\"PersonMarriage\" w, public.\"Name\" wn, public.\"Name\" hn, public.\"Person\" wp
    WHERE
    h.\"MarriageID\" = m.\"ID\" AND h.\"Role\" = 'Husband' AND w.\"MarriageID\" = m.\"ID\" AND w.\"Role\" = 'Wife'
    AND wn.\"PersonID\" = w.\"PersonID\" AND wn.\"Type\" = 'authoritative'
    AND hn.\"PersonID\" = h.\"PersonID\" AND hn.\"Type\" = 'authoritative'
    AND hp.\"ID\" = h.\"PersonID\"
    AND wp.\"ID\" = w.\"PersonID\"
    AND (AGE(TO_TIMESTAMP(m.\"MarriageDate\", 'YYYY-MM-DD'), TO_TIMESTAMP(wp.\"BirthDate\", 'YYYY-MM-DD')) < '18 years'
    OR AGE(TO_TIMESTAMP(m.\"MarriageDate\", 'YYYY-MM-DD'), TO_TIMESTAMP(hp.\"BirthDate\", 'YYYY-MM-DD')) < '18 years')
    AND AGE(TO_TIMESTAMP(m.\"MarriageDate\", 'YYYY-MM-DD'), TO_TIMESTAMP(wp.\"BirthDate\", 'YYYY-MM-DD')) > '0 years'
    AND AGE(TO_TIMESTAMP(m.\"MarriageDate\", 'YYYY-MM-DD'), TO_TIMESTAMP(hp.\"BirthDate\", 'YYYY-MM-DD')) > '0 years'
    AND m.\"Type\" IN ('civil', 'eternity', 'time')
    ORDER BY h.\"PersonID\" ASC, m.\"MarriageDate\" ASC;

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

