<?php

$url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $split = explode("data_view/sidebar.php", $url);
    $base_url = $split[0];
    if (!isset($_GET["id"])) {
        // creating a new person
        die("Missing UVA Person ID.  Cannot continue.");
    }
    // load the person
    $person = json_decode(file_get_contents($base_url . "api/edit_person.php?id=".$_GET["id"]), true);
    $marriages = $person["marriages"];

    // $root = null;
    // foreach($marriages as $m){
    //     if( and $root == null){
    //         $root = $m;
    //     }
    // }
    // if($root == null) $root = $marriages[0];
    $seen = [];
    $root = $marriages[0];
    array_push($seen, $root["SpouseID"]);

    $plurals = array();
    $latest_restriction = "0000-00-00";


    foreach($marriages as $m1){
        if($m1["MarriageDate"] < $latest_restriction) array_push($plurals, $m1);
        $divdate = ($m1["DivorceDate"] == null)?"9999-99-99":$m1["DivorceDate"];
        $cncdate = ($m1["CancelledDate"] == null)?"9999-99-99":$m1["CancelledDate"];
        $earliest_marriage_restriction = min($divdate, $cncdate, $m1["SpouseDeath"]);
        $latest_restriction = max($latest_restriction, $earliest_marriage_restriction);
    }

    function years_between($date1, $date2){
        $d1d = strtotime((strlen($date1) == 4)?$date1."-01-01":$date1);
        $d2d = strtotime((strlen($date2) == 4)?$date2."-01-01":$date2);

        $diff = abs($d1d-$d2d);
        return floor($diff / (365*60*60*24));
    }

    function cmpDates($a, $b){
        $ad = trim($a);
        $bd = trim($b);
        if(strlen($ad) == 4) $ad .= "-01-01";
        if(strlen($bd) == 4) $bd .= "-01-01";
        if(strlen($ad) == 7) $ad .= "-01";
        if(strlen($bd) == 7) $bd .= "-01";
        
        if (strtotime($ad) == strtotime($bd)) {
            return 0;
        }
        return (strtotime($ad) < strtotime($bd)) ? -1 : 1;
    }

    function fetchMarriagesBefore($date, $wifeID, $currentHusbandID){
        global $seen;
        include("../database.php");
        $db = pg_connect($db_conn_string);
        $result = pg_query($db, "
            select CONCAT(hn.\"First\", ' ', hn.\"Middle\", ' ', hn.\"Last\") as \"HusbandName\", hp.\"ID\" as \"HusbandID\", hp.\"DeathDate\" as \"SpouseDeath\", m.\"MarriageDate\", m.\"DivorceDate\", m.\"CancelledDate\"
            from \"Marriage\" m, \"PersonMarriage\" wpm, \"PersonMarriage\" hpm, \"Name\" hn, \"Person\" wp, \"Person\" hp
            where wpm.\"PersonID\" = ".$wifeID."
            and m.\"ID\" = wpm.\"MarriageID\"
            and hpm.\"MarriageID\" = m.\"ID\" and not hpm.\"PersonID\" = ".$wifeID."
            and hn.\"PersonID\" = hpm.\"PersonID\"
            and hp.\"ID\" = hpm.\"PersonID\"
            and wp.\"ID\" = wpm.\"PersonID\"
            and hpm.\"Role\" = 'Husband'
            and wpm.\"Role\" = 'Wife'
            and m.\"MarriageDate\" < '".$date."';
        ");

        $ms = pg_fetch_all($result);
        $out = "<dt>Previous Marriages:</dt>";
        if(!empty($ms)){
            foreach($ms as $marriage){
                if($marriage["HusbandID"] != $currentHusbandID && !in_array($marriage["HusbandID"], $seen)){
                    $divdate = ($marriage["DivorceDate"] == null)?"9999-99-99":$marriage["DivorceDate"];
                    $cncdate = ($marriage["CancelledDate"] == null)?"9999-99-99":$marriage["CancelledDate"];
                    $earliest_marriage_restriction = min($divdate, $cncdate, $marriage["SpouseDeath"]);
                    $out = $out."<dd><a target='_blank' href=http://nauvoo.iath.virginia.edu/viz/person.php?id=".$marriage["HusbandID"].">".$marriage["HusbandName"]."</a></dd>";//, ".explode("-", $marriage["MarriageDate"])[0];//."-".explode("-", $earliest_marriage_restriction)[0]."), ";
                    array_push($seen, $marriage["HusbandID"]);
                }
            }
            return ($out=="<dt>Previous Marriages:</dt>")?"":$out;//substr($out, 0, -2);
        }
        return "";
    }

    function fetchMarriagesAfter($date, $wifeID, $currentHusbandID){
        global $seen;
        include("../database.php");
        $db = pg_connect($db_conn_string);
        $result = pg_query($db, "
            select CONCAT(hn.\"First\", ' ', hn.\"Middle\", ' ', hn.\"Last\") as \"HusbandName\", hp.\"ID\" as \"HusbandID\", hp.\"DeathDate\" as \"SpouseDeath\", m.\"MarriageDate\", m.\"DivorceDate\", m.\"CancelledDate\"
            from \"Marriage\" m, \"PersonMarriage\" wpm, \"PersonMarriage\" hpm, \"Name\" hn, \"Person\" wp, \"Person\" hp
            where wpm.\"PersonID\" = ".$wifeID."
            and m.\"ID\" = wpm.\"MarriageID\"
            and hpm.\"MarriageID\" = m.\"ID\" and not hpm.\"PersonID\" = ".$wifeID."
            and hn.\"PersonID\" = hpm.\"PersonID\"
            and hp.\"ID\" = hpm.\"PersonID\"
            and wp.\"ID\" = wpm.\"PersonID\"
            and hpm.\"Role\" = 'Husband'
            and wpm.\"Role\" = 'Wife'
            and m.\"MarriageDate\" >= '".$date."';
        ");

        $ms = pg_fetch_all($result);
        $out = "<dt>Subsequent Marriages:</dt>";
        if(!empty($ms)){
            foreach($ms as $marriage){
                if($marriage["HusbandID"] != $currentHusbandID && !in_array($marriage["HusbandID"], $seen)){
                    $divdate = ($marriage["DivorceDate"] == null)?"9999-99-99":$marriage["DivorceDate"];
                    $cncdate = ($marriage["CancelledDate"] == null)?"9999-99-99":$marriage["CancelledDate"];
                    $earliest_marriage_restriction = min($divdate, $cncdate, $marriage["SpouseDeath"]);
                    $out = $out."<dd><a target='_blank' href=http://nauvoo.iath.virginia.edu/viz/person.php?id=".$marriage["HusbandID"].">".$marriage["HusbandName"]."</a></dd>";//, ".explode("-", $marriage["MarriageDate"])[0]."; ";//."-".explode("-", $earliest_marriage_restriction)[0]."), ";
                    array_push($seen, $marriage["HusbandID"]);
                }
            }
            return ($out=="<dt>Subsequent Marriages:</dt>")?"":$out;
        }
        return "";
    }

    $first_nondup = 0;
    foreach($plurals as $p){
        if(!in_array($p["SpouseID"], $seen)) break;
        $first_nondup++;
    }

?>

<h3>Root Marriage</h3>

<dt><a target="_blank" href="http://nauvoo.iath.virginia.edu/viz/person.php?id=<?=$_GET["id"]?>"><?=$person["names"][0]["First"]." ".$person["names"][0]["Middle"]." ".$person["names"][0]["Last"]?></a><?=", ".years_between($person["information"]["BirthDate"], $root["MarriageDate"])?><br>
<a target="_blank" href="http://nauvoo.iath.virginia.edu/viz/person.php?id=<?=$root["SpouseID"]?>"><?=trim(substr($root["SpouseName"], 0, strrpos($root["SpouseName"], " ")))?></a><?=", ".years_between($root["SpouseBirth"], $root["MarriageDate"])?></dt>
<?=$root["MarriageDate"]?>


<h3><?=(count($plurals) > 1)?"First Plural":"First & Only Plural"?></h3>

<dl><dt><a target="_blank" href="http://nauvoo.iath.virginia.edu/viz/person.php?id=<?=$plurals[$first_nondup]["SpouseID"]?>"><?=trim(substr($plurals[$first_nondup]["SpouseName"], 0, strrpos($plurals[$first_nondup]["SpouseName"], " ")))?></a><?=", ".years_between($plurals[$first_nondup]["SpouseBirth"], $plurals[$first_nondup]["MarriageDate"])?></dt>
<dd><?=$plurals[$first_nondup]["MarriageDate"]?></dd>
<?=($plurals[$first_nondup]["children"]!="0")?($plurals[$first_nondup]["children"]!="1")?"<dd>".$plurals[$first_nondup]["children"]." children</dd>":"<dd>".$plurals[$first_nondup]["children"]." child</dd>":""?>
<?=fetchMarriagesBefore($plurals[$first_nondup]["MarriageDate"], $plurals[$first_nondup]["SpouseID"], $_GET["id"])?>
<?=fetchMarriagesAfter($plurals[$first_nondup]["MarriageDate"], $plurals[$first_nondup]["SpouseID"], $_GET["id"])?></dl>

<h3>Subsequent Plural</h3>
<dl>
<?php
    $i = 0;
    $spouses = [];
    foreach($plurals as $m){
        if(!in_array($m["SpouseID"], $seen)){
            if($i > 0){
                if(!isset($spouses[$m["SpouseID"]]))
                    $spouses[$m["SpouseID"]] = [];
                array_push($spouses[$m["SpouseID"]], $m);
            }
            $i++;
            array_push($seen, $m["SpouseID"]);
        }
    }
    foreach ($spouses as $s) {?>
    <dt><a target="_blank" href="http://nauvoo.iath.virginia.edu/viz/person.php?id=<?=$s[0]["SpouseID"]?>"><?=trim(substr($s[0]["SpouseName"], 0, strrpos($s[0]["SpouseName"], " ")))?></a><?=($s[0]["MarriageDate"] != null && $s[0]["MarriageDate"] != "" && $s[0]["SpouseBirth"] != null && $s[0]["SpouseBirth"] != "")?", ".years_between($s[0]["SpouseBirth"], $s[0]["MarriageDate"]):""?></dt>
    <?php if(cmpDates($s[0]["MarriageDate"], $person["information"]["DeathDate"]) > 0){ ?>
        <dd>Posthumous</dd>
    <?php } ?>
    <dd><?=($s[0]["MarriageDate"] != null && $s[0]["MarriageDate"] != "")?$s[0]["MarriageDate"]:"UNK"?></dd>
    <!-- <dd><?=($s[0]["SpouseDeath"] != null)?"(".explode("-", $s[0]["SpouseBirth"])[0]."-".explode("-", $s[0]["SpouseDeath"])[0].")":"b. ".explode("-", $s[0]["SpouseBirth"])[0]?></dd> -->
    <?php } ?>


</dl>
