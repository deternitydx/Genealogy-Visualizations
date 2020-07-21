<html>
<head>
<title>Query View</title>
<!-- DataTables CSS -->
<link rel="stylesheet" type="text/css" href="../css/style.css"/>
<link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.2/css/jquery.dataTables.css"/>
  
<!-- jQuery -->
<script type="text/javascript" charset="utf8" src="../js/jquery-2.1.1.js"></script>
  
<!-- DataTables -->
<script type="text/javascript" charset="utf8" src="//cdn.datatables.net/1.10.2/js/jquery.dataTables.js"></script>

</head>
<body>
<script>
$(document).ready( function () {
    $('#datatable').DataTable( {paging: false});
} );
</script>

<h1>Query View</h1>
<?php

include("../database.php");
$db = pg_connect($db_conn_string);

$result = pg_query($db, "

select a.\"ID\" as \"HusbandID\", an.\"Last\" as \"HusbandLast\",
 an.\"First\" as \"HusbandFirst\", 

 c.\"ID\" as \"WifeCID\", cn.\"Last\" as \"WifeCLast\",
 cn.\"First\" as \"WifeCFirst\", c.\"DeathDate\" as \"WifeCDeath\",

 b.\"ID\" as \"WifeBID\", bn.\"Last\" as \"WifeBLast\",
 bn.\"First\" as \"WifeBFirst\", b.\"DeathDate\" as \"WifeBDeath\",

 ab.\"Type\" as \"ABType\", ab.\"MarriageDate\" as \"ABMarriageDate\", 
 ac.\"Type\" as \"ACType\", ac.\"MarriageDate\" as \"ACMarriageDate\"

 from public.\"Person\" a, public.\"Person\" b, public.\"Person\" c, 
    public.\"Name\" an, public.\"Name\" bn, public.\"Name\" cn, 
    (select m.\"Type\", m.\"MarriageDate\", h.\"PersonID\" as \"HusbandID\",
       w.\"PersonID\" as \"WifeID\" from public.\"Marriage\" m, public.\"PersonMarriage\" h,
       public.\"PersonMarriage\" w
       where (w.\"MarriageID\" = m.\"ID\" and w.\"Role\" = 'Wife') and
            (h.\"MarriageID\" = m.\"ID\" and h.\"Role\" = 'Husband') and
            (m.\"Type\" = 'time' OR m.\"Type\" = 'eternity') 
    ) ab,
    (select m.\"Type\", m.\"MarriageDate\", h.\"PersonID\" as \"HusbandID\",
       w.\"PersonID\" as \"WifeID\" 
       from public.\"Marriage\" m, public.\"PersonMarriage\" h, public.\"PersonMarriage\" w
       where (w.\"MarriageID\" = m.\"ID\" and w.\"Role\" = 'Wife') and
            (h.\"MarriageID\" = m.\"ID\" and h.\"Role\" = 'Husband') and
            m.\"Type\" <> 'time' and m.\"Type\" <> 'eternity'
            and m.\"Type\" = 'civil' and  (
               ROW(h.\"PersonID\", w.\"PersonID\") not in 
                ( select h.\"PersonID\" as \"HusbandID\",
                       w.\"PersonID\" as \"WifeID\" 
                       from public.\"Marriage\" m, public.\"PersonMarriage\" h, public.\"PersonMarriage\" w
                       where (w.\"MarriageID\" = m.\"ID\" and w.\"Role\" = 'Wife') and
                       (h.\"MarriageID\" = m.\"ID\" and h.\"Role\" = 'Husband') and
                       (m.\"Type\" = 'time' or m.\"Type\" = 'eternity') 
                )
            )
    ) ac

 where (a.\"ID\" = an.\"PersonID\" and an.\"Type\" = 'authoritative')
 and (b.\"ID\" = bn.\"PersonID\" and bn.\"Type\" = 'authoritative')
 and (c.\"ID\" = cn.\"PersonID\" and cn.\"Type\" = 'authoritative')
 and ab.\"HusbandID\" = a.\"ID\" and ab.\"WifeID\" = b.\"ID\"
 and ac.\"HusbandID\" = a.\"ID\" and ac.\"WifeID\" = c.\"ID\"
 and ab.\"MarriageDate\" is not null and c.\"DeathDate\" is not null
 and c.\"DeathDate\" > ab.\"MarriageDate\"

 order by a.\"ID\", c.\"ID\", b.\"ID\" asc;



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

