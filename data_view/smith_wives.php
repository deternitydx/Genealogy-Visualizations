<?php
//495

    $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $split = explode("data_view/smith_wives.php", $url);
    $base_url = $split[0];
    // load the person
    
    include("../database.php");
    $db = pg_connect($db_conn_string);

    $result = pg_query($db, "
    
    select * from (
        select concat(wn.\"First\", ' ', wn.\"Middle\", ' ', wn.\"Last\") as \"WifeName\", wp.\"ID\", m.\"MarriageDate\"
        from \"Person\" hp, \"Person\" wp, \"PersonMarriage\" wpm, \"PersonMarriage\" hpm, \"Name\" wn, \"Marriage\" m
        where hp.\"ID\" = 495
        and hpm.\"PersonID\" = hp.\"ID\" and hpm.\"MarriageID\" = m.\"ID\"
        and wpm.\"PersonID\" = wp.\"ID\" and wpm.\"MarriageID\" = m.\"ID\"
        and hpm.\"Role\" = 'Husband' and wpm.\"Role\" = 'Wife'
        and wn.\"PersonID\" = wp.\"ID\" and wn.\"Type\" = 'authoritative'
        and m.\"MarriageDate\" < '1844-06-27') r
        order by r.\"MarriageDate\" asc;

    ");

    $marriages = pg_fetch_all($result);

    $out = "<ol>";
    $seen = [];
    function cmp($a, $b){
        $ad = trim($a["MarriageDate"]);
        $bd = trim($b["MarriageDate"]);
        if(strlen($ad) == 4) $ad .= "-01-01";
        if(strlen($bd) == 4) $bd .= "-01-01";
        if(strlen($ad) == 7) $ad .= "-01";
        if(strlen($bd) == 7) $bd .= "-01";
        
        if (strtotime($ad) == strtotime($bd)) {
            return 0;
        }
        return (strtotime($ad) < strtotime($bd)) ? -1 : 1;
    }
    uasort($marriages, 'cmp');
    foreach($marriages as $m){
        //echo $m["MarriageDate"],"\t", date('Y-m-d', strtotime($m["MarriageDate"])), "<br>";
        if(!in_array($m["ID"], $seen)){
            $wID = $m["ID"];
            $out = $out."<li><a href='http://nauvoo.iath.virginia.edu/viz/person.php?id={$wID}'>".trim($m["WifeName"])."</a></li>";
            array_push($seen, $wID);
        }
    }

    echo $out."</ol>";


?>