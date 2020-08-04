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

    $root = null;
    foreach($marriages as $m){
        if($m["Type"] != "civil" and $root == null){
            $root = $m;
        }
    }
    if($root == null) $root = $marriages[0];

    $plurals = array();
    $latest_restriction = "0000-00-00";

    foreach($marriages as $m1){
        if($m1["MarriageDate"] < $latest_restriction) array_push($plurals, $m1);
        $divdate = ($m1["DivorceDate"] == null)?"9999-99-99":$m1["DivorceDate"];
        $cncdate = ($m1["CancelledDate"] == null)?"9999-99-99":$m1["CancelledDate"];
        $earliest_marriage_restriction = min($divdate, $cncdate, $m1["SpouseDeath"]);
        $latest_restriction = max($latest_restriction, $earliest_marriage_restriction);
    }

    function spellout($number){
        $f = new NumberFormatter("en", NumberFormatter::SPELLOUT);
        return $f->format($number);
    }

    function fetchMarriagesBefore($date, $wifeID, $currentHusbandID){
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
        $out = "Previous Marriages: ";
        if(!empty($ms)){
            foreach($ms as $marriage){
                if($marriage["HusbandID"] != $currentHusbandID){
                    $divdate = ($marriage["DivorceDate"] == null)?"9999-99-99":$marriage["DivorceDate"];
                    $cncdate = ($marriage["CancelledDate"] == null)?"9999-99-99":$marriage["CancelledDate"];
                    $earliest_marriage_restriction = min($divdate, $cncdate, $marriage["SpouseDeath"]);
                    $out = $out."<a href=http://nauvoo.iath.virginia.edu/viz/person.php?id=".$marriage["HusbandID"].">".$marriage["HusbandName"]."</a> (".explode("-", $marriage["MarriageDate"])[0]."-".explode("-", $earliest_marriage_restriction)[0]."), ";
                }
            }
            return ($out=="Previous Marriages: ")?"":substr($out, 0, -2);
        }
        return "";
    }

?>

<h3>Root Marriage</h3>

<p><a href="http://nauvoo.iath.virginia.edu/viz/person.php?id=<?=$_GET["id"]?>"><?=$person["names"][0]["First"]." ".$person["names"][0]["Middle"]." ".$person["names"][0]["Last"]." "?></a><br>
<?=$root["MarriageDate"]?><br>
<a href="http://nauvoo.iath.virginia.edu/viz/person.php?id=<?=$root["SpouseID"]?>"><?=substr($root["SpouseName"], 0, strrpos($root["SpouseName"], " "))?></a></p>


<h3>First Plural Marriage</h3>

<p><?=$plurals[0]["MarriageDate"]?><br>
<a href="http://nauvoo.iath.virginia.edu/viz/person.php?id=<?=$plurals[0]["SpouseID"]?>"><?=substr($plurals[0]["SpouseName"], 0, strrpos($plurals[0]["SpouseName"], " "))?></a></p>
<?=($plurals[0]["children"]!="0")?$plurals[0]["child(ren)"]." children<br>":""?>
<?=fetchMarriagesBefore($plurals[0]["MarriageDate"], $plurals[0]["SpouseID"], $_GET["id"])?>

<h3>Subsequent Plural Marriages</h3>
<dl>
<?php
    $i = 0;
    $spouses = [];
    foreach($plurals as $m){
        if($i > 0){
            if(!isset($spouses[$m["SpouseID"]]))
                $spouses[$m["SpouseID"]] = [];
            array_push($spouses[$m["SpouseID"]], $m);
        }
        $i++;
    }
    foreach ($spouses as $s) {?>
    <dt><a href="http://nauvoo.iath.virginia.edu/viz/person.php?id=<?=$s[0]["SpouseID"]?>"><?=substr($s[0]["SpouseName"], 0, strrpos($s[0]["SpouseName"], " "))?></a></dt>
    <?php foreach ($s as $m) {?>
        <dd><?=$m["MarriageDate"].(($m["Type"] != null)?" <em>(".$m["Type"].")</em>":"")?></dd>
        <?=($m["CancelledDate"] != null)?"Cancelled: ".$m["CancelledDate"]:""?>
        <?=($m["CancelledDate"] != null)?"Cancelled: ".$m["CancelledDate"]:""?>
        <?=($m["DivorceDate"] != null)?"Divorced: ".$m["DivorceDate"]:""?>
    <?php }
}   
?>


</dl>
