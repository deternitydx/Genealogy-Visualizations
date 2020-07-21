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

select 
p.\"ID\", p.\"BirthDate\",
n.\"Last\", n.\"First\", p.\"Gender\", cm.\"ID\" as \"CivilID\", cm.\"MarriageDate\" as \"CivilDate\",
age(to_timestamp(text(cm.\"MarriageDate\"), 'YYYY-MM-DD'), to_timestamp(text(p.\"BirthDate\"), 'YYYY-MM-DD')) as \"Civil_Age\",
age(to_timestamp(text(cm.\"MarriageDate\"), 'YYYY-MM-DD'), to_timestamp(text(cm.\"BirthDate\"), 'YYYY-MM-DD')) as \"Civil_Spouse_Age\",
cm.\"SpouseID\" as \"Civil_SID\",
cm.\"SLast\" as \"CivilLast\", cm.\"SFirst\" as \"CivilFirst\",cm.\"Gender\" as \"CivilGender\", em.\"ID\" as \"SealID\", em.\"Type\" as \"SealType\", em.\"MarriageDate\" as \"SealDate\",
age(to_timestamp(text(em.\"MarriageDate\"), 'YYYY-MM-DD'), to_timestamp(text(p.\"BirthDate\"), 'YYYY-MM-DD')) as \"Seal_Age\",
age(to_timestamp(text(em.\"MarriageDate\"), 'YYYY-MM-DD'), to_timestamp(text(cm.\"BirthDate\"), 'YYYY-MM-DD')) as \"Seal_Spouse_Age\",
em.\"SpouseID\" as \"Seal_SID\",
em.\"SLast\" as \"SealLast\", em.\"SFirst\" as \"SealFirst\",em.\"Gender\" as \"SealGender\"
from \"Person\" p
    left join \"ChurchOrgMembership\" com on com.\"PersonID\" = p.\"ID\"
    left outer join \"Name\" n on n.\"PersonID\" = p.\"ID\" and n.\"Type\" = 'authoritative'
    left outer join (
            select distinct
            pm1.\"PersonID\",
            pm2.\"PersonID\" as \"SpouseID\",
            n2.\"Last\" as \"SLast\", n2.\"First\" as \"SFirst\", n2.\"Middle\" as \"SMid\", m.\"ID\", m.\"Type\", m.\"MarriageDate\", p.\"Gender\", p.\"BirthDate\"
            from
            (
                select
                pm1.\"PersonID\", pm1.\"Role\",
                min(m.\"MarriageDate\") as \"MarriageDate\"
                from
                \"Marriage\" m,
                \"PersonMarriage\" pm1
                where pm1.\"MarriageID\" = m.\"ID\" and  pm1.\"Role\" in ('Husband', 'Wife')
                and m.\"Type\" = 'civil'
                group by pm1.\"PersonID\", pm1.\"Role\"
            ) lm,
            \"Marriage\" m, \"PersonMarriage\" pm1, \"PersonMarriage\" pm2, \"Name\" n2, \"Person\" p
            where
            pm1.\"MarriageID\" = m.\"ID\" and pm1.\"Role\" = lm.\"Role\" and pm1.\"PersonID\" = lm.\"PersonID\"
            and pm2.\"MarriageID\" = pm1.\"MarriageID\" and pm2.\"Role\" in ('Husband', 'Wife') and pm2.\"Role\" != pm1.\"Role\"
            and pm2.\"PersonID\" = n2.\"PersonID\" and n2.\"Type\" = 'authoritative'
            and m.\"Type\" = 'civil' and m.\"MarriageDate\" = lm.\"MarriageDate\" and pm2.\"PersonID\" = p.\"ID\"
    ) cm on p.\"ID\" = cm.\"PersonID\" 
    left outer join (
            select distinct
            pm1.\"PersonID\",
            pm2.\"PersonID\" as \"SpouseID\",
            n2.\"Last\" as \"SLast\", n2.\"First\" as \"SFirst\", n2.\"Middle\" as \"SMid\", m.\"ID\", m.\"Type\", m.\"MarriageDate\", p.\"Gender\", p.\"BirthDate\"
            from
            (
                select
                pm1.\"PersonID\", pm1.\"Role\",
                min(m.\"MarriageDate\") as \"MarriageDate\"
                from
                \"Marriage\" m,
                \"PersonMarriage\" pm1
                where pm1.\"MarriageID\" = m.\"ID\" and  pm1.\"Role\" in ('Husband', 'Wife')
                and (m.\"Type\" = 'eternity' or m.\"Type\" = 'time')
                group by pm1.\"PersonID\", pm1.\"Role\"
            ) lm,
            \"Marriage\" m, \"PersonMarriage\" pm1, \"PersonMarriage\" pm2, \"Name\" n2, \"Person\" p
            where
            pm1.\"MarriageID\" = m.\"ID\" and pm1.\"Role\" = lm.\"Role\" and pm1.\"PersonID\" = lm.\"PersonID\"
            and pm2.\"MarriageID\" = pm1.\"MarriageID\" and pm2.\"Role\" in ('Husband', 'Wife') and pm2.\"Role\" != pm1.\"Role\"
            and pm2.\"PersonID\" = n2.\"PersonID\" and n2.\"Type\" = 'authoritative'
            and (m.\"Type\" = 'eternity' or m.\"Type\" = 'time') and m.\"MarriageDate\" = lm.\"MarriageDate\" and pm2.\"PersonID\" = p.\"ID\"
    ) em on p.\"ID\" = em.\"PersonID\"
where not (cm.\"ID\" is null and em.\"ID\" is null) and com.\"ChurchOrgID\" = 1
order by p.\"ID\" asc;



");
if (!$result) {
    echo "An error occurred.\n";
    echo pg_last_error();
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

