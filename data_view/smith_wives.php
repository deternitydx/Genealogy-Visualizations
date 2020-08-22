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
        select distinct on (wp.\"ID\") concat(wn.\"First\", ' ', wn.\"Middle\", ' ', wn.\"Last\") as \"WifeName\", wp.\"ID\", m.\"MarriageDate\"
        from \"Person\" hp, \"Person\" wp, \"PersonMarriage\" wpm, \"PersonMarriage\" hpm, \"Name\" wn, \"Marriage\" m
        where hp.\"ID\" = 495
        and hpm.\"PersonID\" = hp.\"ID\" and hpm.\"MarriageID\" = m.\"ID\"
        and wpm.\"PersonID\" = wp.\"ID\" and wpm.\"MarriageID\" = m.\"ID\"
        and hpm.\"Role\" = 'Husband' and wpm.\"Role\" = 'Wife'
        and wn.\"PersonID\" = wp.\"ID\" and wn.\"Type\" = 'authoritative') r
        order by r.\"MarriageDate\" asc;

    ");

    $marriages = pg_fetch_all($result);

    $out = "<ol>";
    $seen = [];
    foreach($marriages as $m){
        if(!in_array($m["ID"], $seen)){
            $wID = $m["ID"];
            $out = $out."<li><a href='http://nauvoo.iath.virginia.edu/viz/person.php?id={$wID}'>".trim($m["WifeName"])."</a></li>";
            array_push($seen, $wID);
        }
    }

    echo $out."</ol>";


?>