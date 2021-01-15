<?php

include("../database.php");
$db = pg_connect($db_conn_string);


$restype = $_POST["res"]; //this is the main returned value. For marriages, we also return husband and wife names/ids, and for people we return marriage and child counts.
$martype = json_decode($_POST["mt"]);
$dates = json_decode($_POST["dt"]);
$dateCls = json_decode($_POST["dtcls"]);
$cols = json_decode($_POST["cols"]);
$texts = json_decode($_POST["txt"]);
$knunk = json_decode($_POST["knu"]);
$nums = json_decode($_POST["num"]);
$numCls = json_decode($_POST["numcls"]);
$offices = json_decode($_POST["off"]);
// $sort = $_POST["sort"];
// $dir = $_POST["dir"];
// $lim = $_POST["lim"];
$restrict = $_POST["restrict"];
$isisnot = json_decode($_POST["isisnot"]);

$query_sel = "select ";
$query_from = " from ";
$query_where = $query_joins = "";
$query_before = "select * from (";
$query_after = ") c where 1 = 1 ";
$where_for_stats = "";
$dateCount = $iinCount = $textCount = $numCount = $knCount = $offCount = $mtCount = 0;
$paramCount = 1;
$params = [];
$aq_query = "SELECT DISTINCT p.\"ID\" FROM \"Person\" p, \"ChurchOrgMembership\" m, \"ChurchOrganization\" c where m.\"PersonID\" = p.\"ID\" and m.\"ChurchOrgID\" = c.\"ID\" and c.\"Name\" = 'Annointed Quorum'";


if($restype == "Person"){
    $query_sel .= "p0.\"ID\", concat(n0.\"First\", ' ', n0.\"Middle\", ' ', n0.\"Last\") as \"FullName\", n0.\"First\", n0.\"Last\", p0.\"BirthDate\", p0.\"DeathDate\", 
    AGE(TO_TIMESTAMP(p0.\"DeathDate\", 'YYYY-MM-DD'), TO_TIMESTAMP(p0.\"BirthDate\", 'YYYY-MM-DD')) as \"Lifespan\", 
    string_agg(distinct o.\"Name\", ', ') as \"Office\", string_agg(distinct cast(m.\"Type\" as varchar), ', ') as \"MarriageTypes\", count(distinct sp.\"ID\") as \"MarriageCount\", count(distinct cp.\"ID\") as \"NatChildCount\", count(distinct ap.\"ID\") as \"AdChildCount\", (count(distinct ap.\"ID\")+count(distinct cp.\"ID\")) as \"TotChildCount\"";
    $query_from .= "\"Person\" p0 ";
    $query_joins .= "left join \"Name\" n0 on n0.\"PersonID\" = p0.\"ID\" and n0.\"Type\" = 'authoritative' 
    left join \"PersonMarriage\" pm on p0.\"ID\" = pm.\"PersonID\" and pm.\"Role\" in ('Husband', 'Wife')
    left join \"Marriage\" m on m.\"ID\" = pm.\"MarriageID\"
    left join \"PersonMarriage\" spm on spm.\"MarriageID\" = m.\"ID\" and spm.\"PersonID\" != p0.\"ID\" and spm.\"Role\" in ('Husband', 'Wife')
    left join \"Person\" sp on sp.\"ID\" = spm.\"PersonID\"
    left join \"Person\" cp on cp.\"BiologicalChildOfMarriage\" = m.\"ID\"
    left join \"NonMaritalSealings\" nms on nms.\"MarriageID\" = m.\"ID\"
    left join \"Person\" ap on nms.\"AdopteeID\" = ap.\"ID\"
    left join \"PersonOffice\" po on po.\"PersonID\" = p0.\"ID\"
    left join \"Office\" o on po.\"OfficeID\" = o.\"ID\"";
    $query_where .= " where 1=1  ";
    switch($restrict){
        case "AnnointedQuorum":
            $query_where .= " and p0.\"ID\" in (".$aq_query.") ";
    }
    if(count($cols) > 0){
        foreach(range(0, count($cols)-1) as $q){
            $decider = ($isisnot[$iinCount] == "isnot")?" not ":" ";
            switch($cols[$q]){
                case "BirthDate":
                case "DeathDate":
                    switch($dateCls[$dateCount]){
                        case "before":
                            $query_where .= "and".$decider."p0.\"".$cols[$q]."\" < $".$paramCount." ";
                            array_push($params, $dates[$dateCount]);
                            $paramCount++;
                        break;
                        case "after":
                            $query_where .= "and".$decider."p0.\"".$cols[$q]."\" > $".$paramCount." ";
                            array_push($params, $dates[$dateCount]);
                            $paramCount++;
                        break;
                        case "on":
                            $query_where .= "and".$decider."p0.\"".$cols[$q]."\" = $".$paramCount." ";
                            array_push($params, $dates[$dateCount]);
                            $paramCount++;
                        break;
                        case "known":
                            $query_where .= "and".$decider."(p0.\"".$cols[$q]."\" is not null and not p0.\"".$cols[$q]."\" = '') ";
                        break;
                        case "unknown":
                            $query_where .= "and".$decider."(p0.\"".$cols[$q]."\" is null or p0.\"".$cols[$q]."\" = '') ";
                        break;
                    }
                    $dateCount++;
                break;
                case "First":
                case "Last":
                    if($texts[$textCount] != ""){
                        $query_where .= "and".$decider."n0.\"".$cols[$q]."\" ilike $".$paramCount." ";
                        array_push($params, $texts[$textCount]);
                        $textCount++;
                        $paramCount++;
                    }
                break;
                case "Lifespan":
                    switch($knunk[$knCount]){
                        case "known":
                            $query_after .= "and".$decider."\"Lifespan\" is not null ";
                        break;
                        case "unknown":
                            $query_after .= "and".$decider."\"Lifespan\" is null ";
                        break;
                    }
                    $knCount++;
                break;
                case "MarriageCount":
                case "NatChildCount":
                    switch($numCls[$numCount]){
                        case "less":
                            $query_after .= "and".$decider."\"".$cols[$q]."\" < $".$paramCount." ";
                        break;
                        case "equal":
                            $query_after .= "and".$decider."\"".$cols[$q]."\" = $".$paramCount." ";
                        break;
                        case "greater":
                            $query_after .= "and".$decider."\"".$cols[$q]."\" > $".$paramCount." ";
                        break;
                    }
                    array_push($params, $nums[$numCount]);
                    $numCount++;
                    $paramCount++;
                break;
                case "Office":
                    switch($offices[$offCount]){
                        case "First Presidency":
                        case "Apostle":
                        case "Seventy":
                        case "High Priest":
                        case "Elder":
                        case "Teacher":
                        case "Priest":
                        case "Deacon":
                        case "Bishop":
                        case "Patriarch":
                        case "Relief Society":
                        case "Temple Worker":
                        case "Midwife":
                        case "Female Relief Society of Nauvoo":
                            $query_after .= "and".$decider."\"".$cols[$q]."\" ilike '%".$offices[$offCount]."%'";
                        break;
                        case "known":
                            $query_where .= "and".$decider."o.\"Name\" is not null ";
                        break;
                        case "unknown":
                            $query_where .= "and".$decider."o.\"Name\" is null ";
                    }
                    $offCount++;
            }
        $iinCount++;
        }
    }
    $query_sel_stats = "select 
            cast(extract(epoch from avg(\"Lifespan\"))/31557600 as numeric) as \"AvgLifespanDec\", 
            avg(\"Lifespan\") as \"AvgLifespan\",
            avg(\"MarriageCount\") as \"AvgMarriage\",
            avg(\"NatChildCount\") as \"AvgNatChild\",
            cast(extract(epoch from max(\"Lifespan\"))/31557600 as numeric) as \"MaxLifespanDec\",
            max(\"Lifespan\") as \"MaxLifespan\",
            max(\"MarriageCount\") as \"MaxMarriage\",
            max(\"NatChildCount\") as \"MaxNatChild\",
            cast(extract(epoch from min(\"Lifespan\"))/31557600 as numeric) as \"MinLifespanDec\",
            min(\"Lifespan\") as \"MinLifespan\", 
            min(\"MarriageCount\") as \"MinMarriage\", 
            min(\"NatChildCount\") as \"MinNatChild\",
            count(\"ID\") as \"ResultCount\"
            from (";
    $query_where .= "group by p0.\"ID\", n0.\"First\", n0.\"Middle\", n0.\"Last\", p0.\"BirthDate\", p0.\"DeathDate\" ";
    // switch($sort){
    //     case "First":
    //     case "Last":
    //     case "BirthDate":
    //     case "DeathDate":
    //     case "MarriageCount":
    //     case "TotChildCount":
    //     case "AdChildCount":
    //     case "NatChildCount":
    //     case "Lifespan":
    //         if($dir == "asc") $query_where .= " order by \"".$sort."\" asc ";
    //         elseif($dir == "desc") $query_where .= " order by \"".$sort."\" desc ";
    //     break;
    // }
    // $query_where .= " nulls last ";
    $where_for_stats = $query_where;
    $query_after_stats = $query_after;
    // if(is_numeric($lim)) $query_after .= "limit ".$lim;
    // else $query_after .= "limit 15";
    //echo $query_before.$query_sel.$query_from.$query_where.$query_after;
    $result = pg_query_params($db, $query_before.$query_sel.$query_from.$query_joins.$query_where.$query_after, $params);
    if(!$result){
        die("Errored on data query");
    }
    //$query_sel_stats = "select * from (".$query_sel_stats;
    //echo $query_before.$query_sel.$query_from.$query_where.$query_after;
    $query_after_stats .= ") a ";
    // if(is_numeric($lim)) $query_after_stats .= "limit ".$lim;
    // else $query_after_stats .= "limit 15";
    //echo $query_sel_stats.$query_before.$query_sel.$query_from.$where_for_stats.$query_after;
    // echo $query_sel_stats.$query_before.$query_sel.$query_from.$query_joins.$where_for_stats.$query_after_stats;
    $stats_result = pg_query_params($db, $query_sel_stats.$query_before.$query_sel.$query_from.$query_joins.$where_for_stats.$query_after_stats, $params);
    if(!$stats_result){
        die("Errored on stats query");
    }
    $rows = pg_fetch_all($result);
    $stats = pg_fetch_all($stats_result);
    //print_r($stats);
    if($rows && $stats) array_unshift($rows, $stats);
}
elseif($restype == "Marriage"){
    #MIN(AGE(TO_TIMESTAMP(wp.\"BirthDate\", 'YYYY-MM-DD'), TO_TIMESTAMP(cp.\"BirthDate\", 'YYYY-MM-DD'))) as \"FirstChildAge\",
    $query_sel .= "distinct hp.\"ID\" as \"HusbandID\",  wp.\"ID\" as \"WifeID\",
        case when m.\"DivorceDate\" is null and m.\"CancelledDate\" is null then 'N' else 'Y' end as \"IsSeparated\",
        case when exists(select * from \"Marriage\" m1 
						inner join \"PersonMarriage\" pm1 on pm1.\"MarriageID\" = m1.\"ID\" and pm1.\"PersonID\" in (hpm.\"PersonID\", wpm.\"PersonID\")
						where extract(epoch from to_timestamp(m.\"MarriageDate\", 'YYYY-MM-DD') - to_timestamp(m1.\"MarriageDate\", 'YYYY-MM-DD')) > 0
					    and (m1.\"DivorceDate\" is null or extract(epoch from to_timestamp(m1.\"DivorceDate\", 'YYYY-MM-DD') - to_timestamp(m.\"MarriageDate\", 'YYYY-MM-DD')) > 0)
						and (m1.\"CancelledDate\" is null or extract(epoch from to_timestamp(m1.\"CancelledDate\", 'YYYY-MM-DD') - to_timestamp(m.\"MarriageDate\", 'YYYY-MM-DD')) > 0)
					   ) then 'Y' else 'N' end as \"IsPlural\",
        case when to_timestamp(m.\"MarriageDate\", 'YYYY-MM-DD') > '1829-01-01' and  to_timestamp(m.\"MarriageDate\", 'YYYY-MM-DD') < '1840-06-30' then 1
        when to_timestamp(m.\"MarriageDate\", 'YYYY-MM-DD') < '1844-06-27' then 2
        when to_timestamp(m.\"MarriageDate\", 'YYYY-MM-DD') < '1845-12-09' then 3
        when to_timestamp(m.\"MarriageDate\", 'YYYY-MM-DD') < '1846-02-28' then 4
        when to_timestamp(m.\"MarriageDate\", 'YYYY-MM-DD') < '1852-08-29' then 5
        when to_timestamp(m.\"MarriageDate\", 'YYYY-MM-DD') < '1874-06-23' then 6
        when to_timestamp(m.\"MarriageDate\", 'YYYY-MM-DD') < '1890-10-06' then 7
        when to_timestamp(m.\"MarriageDate\", 'YYYY-MM-DD') < '1910-10-05' then 8
        else null end as \"Phase\",
        m.\"MarriageDate\", m.\"ID\" as \"MarriageID\", concat(hn.\"First\", ' ', hn.\"Middle\", ' ', hn.\"Last\") as \"HusbandName\",
        hn.\"First\" as \"HusbandFirst\", hn.\"Last\" as \"HusbandLast\", wn.\"First\" as \"WifeFirst\", wn.\"Last\" as \"WifeLast\", 
        AGE(TO_TIMESTAMP(m.\"MarriageDate\", 'YYYY-MM-DD'), TO_TIMESTAMP(hp.\"BirthDate\", 'YYYY-MM-DD')) as \"HusbandAge\", 
        concat(wn.\"First\", ' ', wn.\"Middle\", ' ', wn.\"Last\") as \"WifeName\", AGE(TO_TIMESTAMP(m.\"MarriageDate\", 'YYYY-MM-DD'), 
        TO_TIMESTAMP(wp.\"BirthDate\", 'YYYY-MM-DD')) as \"WifeAge\",
        age(min(to_date(cp.\"BirthDate\", 'YYYY-MM-DD')), to_date(wp.\"BirthDate\", 'YYYY-MM-DD')) as \"WifeFirstBirthAge\", 
        m.\"Type\",
        m.\"DivorceDate\", m.\"CancelledDate\", wp.\"DeathDate\" as \"WifeDeath\", hp.\"DeathDate\" as \"HusbandDeath\",
        case when hp.\"BirthDate\" is null then null
        when wp.\"BirthDate\" is null then null
        else AGE(TO_TIMESTAMP(GREATEST(hp.\"BirthDate\", wp.\"BirthDate\"), 'YYYY-MM-DD'), TO_TIMESTAMP(LEAST(hp.\"BirthDate\", wp.\"BirthDate\"), 'YYYY-MM-DD')) end as \"AgeDiff\"
        ";
    $query_from .= "\"Marriage\" m ";
    $query_joins .= "left join \"PersonMarriage\" hpm on hpm.\"MarriageID\" = m.\"ID\" and hpm.\"Role\" = 'Husband'
    left join \"PersonMarriage\" wpm on wpm.\"MarriageID\" = m.\"ID\" and wpm.\"Role\" = 'Wife'
    left join \"Person\" hp on hp.\"ID\" = hpm.\"PersonID\"
    left join \"Person\" wp on wp.\"ID\" = wpm.\"PersonID\"
    left join \"Person\" cp on cp.\"BiologicalChildOfMarriage\" = m.\"ID\"
    left join \"Name\" hn on hn.\"PersonID\" = hp.\"ID\" and hn.\"Type\" = 'authoritative'
    left join \"Name\" wn on wn.\"PersonID\" = wp.\"ID\" and wn.\"Type\" = 'authoritative'";
    $query_where .= " where 1=1 ";
    if(count($cols) > 0){
        foreach(range(0, count($cols)-1) as $q){
            $decider = ($isisnot[$iinCount] == "isnot")?" not ":" ";
            switch($cols[$q]){
                case "MarriageDate":
                case "DivorceDate":
                case "CancelledDate":
                    switch($dateCls[$dateCount]){
                        case "before":
                            $query_where .= "and".$decider."m.\"".$cols[$q]."\" < $".$paramCount." ";
                            array_push($params, $dates[$dateCount]);
                            $paramCount++;
                        break;
                        case "after":
                            $query_where .= "and".$decider."m.\"".$cols[$q]."\" > $".$paramCount." ";
                            array_push($params, $dates[$dateCount]);
                            $paramCount++;
                        break;
                        case "on":
                            $query_where .= "and".$decider."m.\"".$cols[$q]."\" = $".$paramCount." ";
                            array_push($params, $dates[$dateCount]);
                            $paramCount++;
                        break;
                        case "known":
                            $query_where .= "and".$decider."(m.\"".$cols[$q]."\" is not null and not m.\"".$cols[$q]."\" = '' )";
                        break;
                        case "unknown":
                            $query_where .= "and".$decider."(m.\"".$cols[$q]."\" is null or m.\"".$cols[$q]."\" = '') ";
                        break;
                    }
                    $dateCount++;
                break;
                case "HusbandFirst":
                    if($texts[$textCount] != ""){
                        $query_where .= "and".$decider."hn.\"First\" ilike $".$paramCount." ";
                        array_push($params, $texts[$textCount]);
                        $textCount++;
                        $paramCount++;
                    }
                break;
                case "HusbandLast":
                    if($texts[$textCount] != ""){
                        $query_where .= "and".$decider."hn.\"Last\" ilike $".$paramCount." ";
                        array_push($params, $texts[$textCount]);
                        $textCount++;
                        $paramCount++;
                    }
                break;
                case "WifeFirst":
                    if($texts[$textCount] != ""){
                        $decider = ($isisnot[$iinCount] == "isnot")?" not ":" ";
                        $query_where .= "and".$decider."wn.\"First\" ilike $".$paramCount." ";
                        array_push($params, $texts[$textCount]);
                        $textCount++;
                        $paramCount++;
                    }
                break;
                case "WifeLast":
                    if($texts[$textCount] != ""){
                        $query_where .= "and".$decider."wn.\"Last\" ilike $".$paramCount." ";
                        array_push($params, $texts[$textCount]);
                        $textCount++;
                        $paramCount++;
                    }
                break;
                case "Phase":
                    switch($numCls[$numCount]){
                        case "less":
                            $query_after .= "and".$decider."\"".$cols[$q]."\" < $".$paramCount." ";
                        break;
                        case "equal":
                            $query_after .= "and".$decider."\"".$cols[$q]."\" = $".$paramCount." ";
                        break;
                        case "greater":
                            $query_after .= "and".$decider."\"".$cols[$q]."\" > $".$paramCount." ";
                        break;
                    }
                    array_push($params, $nums[$numCount]);
                    $numCount++;
                    $paramCount++;
                break;
                case "Type":
                    if($martype[$mtCount] == "unknown") $query_where .= "and".$decider."(m.\"Type\" = $".$paramCount." or m.\"Type\" is null) ";
                    else $query_where .= "and".$decider."m.\"Type\" = $".$paramCount." ";
                    array_push($params, $martype[$mtCount]);
                    $paramCount++;
                    $mtCount++;
                break;
            }
        $iinCount++;
        }
    }
    $query_where .= " group by hp.\"ID\", wp.\"ID\", hn.\"First\", hn.\"Middle\", hn.\"Last\", wn.\"First\", wn.\"Middle\", wn.\"Last\", m.\"MarriageDate\", hp.\"BirthDate\", wp.\"BirthDate\", m.\"Type\", m.\"DivorceDate\", m.\"CancelledDate\", wp.\"DeathDate\", hp.\"DeathDate\", m.\"ID\", hpm.\"PersonID\", wpm.\"PersonID\" ";
    // switch($sort){
    //     case "WifeFirst":
    //     case "HusbandFirst":
    //     case "WifeLast":
    //     case "HusbandLast":
    //     case "MarriageDate":
    //     case "WifeAge":
    //     case "HusbandAge":
    //     case "AgeDiff":
    //         if($dir == "asc") $query_where .= " order by \"".$sort."\" asc ";
    //         elseif($dir == "desc") $query_where .= " order by \"".$sort."\" desc ";
    //     break;
    // }
    
    // $query_where .= " nulls last ";
    $where_for_stats = $query_where;
    $query_after_stats = $query_after;
    // if(is_numeric($lim)) $query_after .= "limit ".$lim;
    // else $query_after .= "limit 15";
    $result = pg_query_params($db, $query_before.$query_sel.$query_from.$query_joins.$query_where.$query_after, $params);
    if(!$result){
        die("Errored on data query");
    }
    $query_sel_stats = "select count(distinct \"MarriageID\") as \"ResultCount\" from (";
    $query_after_stats .= ") a;";
    $stats_result = pg_query_params($db, $query_sel_stats.$query_before.$query_sel.$query_from.$query_joins.$where_for_stats.$query_after_stats, $params);
    if(!$stats_result){
        die("Errored on stats query");
    }
    //echo $query_sel_stats.$query_before.$query_sel.$query_from.$query_joins.$where_for_stats.$query_after_stats;
    $rows = pg_fetch_all($result);
    $stats = pg_fetch_all($stats_result);
    if($rows && $stats) array_unshift($rows, $stats);
}
//echo $query_before.$query_sel.$query_from.$query_where.$query_after;
//$result = pg_query_params($db, $query_before.$query_sel.$query_from.$query_where.$query_after, $params);
// $result = pg_query_params($db, $query_sel.$query_from.$query_where, $params);
// $query_sel_stats = "select * from (";
// $query_after .= ") a;";
//echo $query_sel_stats.$query_before.$query_sel.$query_from.$where_for_stats.$query_after;
//$stats_result = pg_query_params($db, $query_sel_stats.$query_before.$query_sel.$query_from.$where_for_stats.$query_after, $params);

//$rows = pg_fetch_all($result);
//$stats = pg_fetch_all($stats_result);
//print_r($result);

//if($rows && $stats) array_unshift($rows, $stats);
echo json_encode($rows);





?>