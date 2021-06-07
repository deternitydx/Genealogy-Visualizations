<?php

//For debugging purposes. Comment out when pushing to repo
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $split = explode("data_view/sidebar.php", $url);
    $base_url = $split[0];
    if (!isset($_GET["id"])) {
        // creating a new person
        die("Missing UVA Person ID.  Cannot continue.");
    }
    
    // load the person
    $id = $_GET["id"];
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
    //var_dump($root);

    $plurals = array();
    $latest_restriction = "0000-00-00";

    uasort($marriages,"sortMarriages");
    foreach($marriages as $m1){
        //I'm not sure what the point of $latest_restriction is? Just going to ignore it for now because it seems like it's excluding marriage dates that shouldnt be excluded

        //Posthumous handling
        $husband_death_date = $person["information"]["DeathDate"];
        $not_posthumous = true;
        if(cmpDates($husband_death_date,$m1["MarriageDate"]) < 1) $not_posthumous = false;
        if($m1["ID"]!= $root["ID"] && $not_posthumous){
            //Only want to include marriages after the root and those that aren't posthumous
            //echo 'Marriage date is : ' . $m1["MarriageDate"] . "</br>";
            array_push($plurals, $m1);
        } 
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

    /**
     * Check for existence of second or "other" civil marriage
     * Condition 1: Previous wife must have died prior to the marriage date. In the case of  second civil, the root wife must have died before the marriage date
     * Condition 2: The marriage type must be civil
     */
    $civil_index = 0;
    $root_wife_death_date = $root["SpouseDeath"];
    $second_civil = array();
    $other_civil = array();
    while($civil_index < sizeof($plurals)){
        if($civil_index ==0){
            //Check for second civil marriage
            if(($plurals[0]["Type"]=="civil") && (cmpDates($root_wife_death_date,$plurals[0]["MarriageDate"])==-1)){
                array_push($second_civil,$plurals[0]);
                array_push($seen,$plurals[0]["SpouseID"]);
                //echo "Existence of second civil marriage! Date is : " . $plurals[0]["MarriageDate"] . "</br>";
            }
        }
        else{
            //Check for other civil marriage
            $previous_wife_death_date = $plurals[$civil_index-1]["SpouseDeath"];
            if(($plurals[$civil_index-1]["Type"]=="civil") && (cmpDates($previous_wife_death_date,$plurals[$civil_index-1]["MarriageDate"])==-1)){
                array_push($other_civil,$plurals[$civil_index]);
                array_push($seen,$plurals[$civil_index]["SpouseID"]);
                //echo "Existence of other civil marriage! Date is : " . $plurals[0]["MarriageDate"];
            }
        }
        $civil_index++;
    }




    //If there is an existence of a civil marriage, want to move the first plural to the second index
    $first_nondup = (empty($second_civil)) ? 0 : 1;
    if(!empty($other_civil)){
        //If there is an other civil marriage, move the first-plural-index to right after the other_civil array
        $first_nondup = 1 + sizeof($other_civil);
    }
    for($i =0; $i< sizeof($plurals);$i++){
        if(!in_array($plurals[$i]["SpouseID"], $seen)){
            //echo "First plural instance is : " . $plurals[$i]["SpouseName"] . "</br>";
            $first_nondup = $i;
            break;
        } 
    }


?>

<a href="http://nauvoo.iath.virginia.edu/viz/chord.html?id=<?php echo $id; ?>">View Chord Diagram</a>

<h3>Root Marriage</h3>

<dt><a target="_blank" href="http://nauvoo.iath.virginia.edu/viz/person.php?id=<?=$_GET["id"]?>"><?=$person["names"][0]["First"]." ".$person["names"][0]["Middle"]." ".$person["names"][0]["Last"]?></a><?=", ".years_between($person["information"]["BirthDate"], $root["MarriageDate"])?><br>
<a target="_blank" href="http://nauvoo.iath.virginia.edu/viz/person.php?id=<?=$root["SpouseID"]?>"><?=trim(substr($root["SpouseName"], 0, strrpos($root["SpouseName"], " ")))?></a><?=", ".years_between($root["SpouseBirth"], $root["MarriageDate"])?></dt>
<?=$root["MarriageDate"]?>


<?php
    //Second civil
    if(!empty($second_civil)){
        $wife_id = $second_civil[0]["SpouseID"];
        $wife_name = trim(substr($second_civil[0]["SpouseName"], 0, strrpos($second_civil[0]["SpouseName"], " ")));
        $wife_marriage_age_string = ", ".years_between($second_civil[0]["SpouseBirth"], $second_civil[0]["MarriageDate"]);
        $wife_marriage_date = $second_civil[0]["MarriageDate"];
        $wife_prior_marriages = fetchMarriagesBefore($second_civil[0]["MarriageDate"], $second_civil[0]["SpouseID"], $_GET["id"]);
        $wife_after_marriages = fetchMarriagesAfter($second_civil[0]["MarriageDate"], $second_civil[0]["SpouseID"], $_GET["id"]);
        echo <<<EOT
        <h3>Second Civil</h3>
        <dl><dt><a target="_blank" href="http://nauvoo.iath.virginia.edu/viz/person.php?id=$wife_id">$wife_name</a>$wife_marriage_age_string</dt>
        <dd>$wife_marriage_date</dd> 
        $wife_prior_marriages
        $wife_after_marriages</dl>
        EOT;
    }
?>

 <?php
    if(!empty($other_civil)){
        echo "<h3>Other Civil</h3>";
        for($i=0;$i<sizeof($other_civil);$i++){
            $wife_id = $other_civil[$i]["SpouseID"];
            $wife_name = trim(substr($other_civil[$i]["SpouseName"], 0, strrpos($other_civil[$i]["SpouseName"], " ")));
            $wife_marriage_age_string = ", ".years_between($other_civil[$i]["SpouseBirth"], $second_civil[$i]["MarriageDate"]);
            if($other_civil[$i]["SpouseBirth"]==null || $other_civil[$i]["SpouseBirth"]=="" || $other_civil[$i]["MarriageDate"]==null || $other_civil[$i]["MarriageDate"]==""){
                $wife_marriage_age_string="";
            }
            $wife_marriage_date = $other_civil[$i]["MarriageDate"];
            echo <<<EOT
            <dt><a target="_blank" href="http://nauvoo.iath.virginia.edu/viz/person.php?id=$wife_id"><$wife_name></a>$wife_marriage_age_string</dt>
            <dd>$wife_marriage_date</dd> 
            EOT;
        }         
    }
?> 




<h3><?=(count($plurals) > 1)?"First Plural":"First & Only Plural"?></h3>

<dl><dt><a target="_blank" href="http://nauvoo.iath.virginia.edu/viz/person.php?id=<?=$plurals[$first_nondup]["SpouseID"]?>"><?=trim(substr($plurals[$first_nondup]["SpouseName"], 0, strrpos($plurals[$first_nondup]["SpouseName"], " ")))?></a><?=", ".years_between($plurals[$first_nondup]["SpouseBirth"], $plurals[$first_nondup]["MarriageDate"])?></dt>
<dd><?=$plurals[$first_nondup]["MarriageDate"]?></dd> 
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
    
    <dd><?=($s[0]["MarriageDate"] != null && $s[0]["MarriageDate"] != "")?$s[0]["MarriageDate"]:"UNK"?></dd>
    <!-- <dd><?=($s[0]["SpouseDeath"] != null)?"(".explode("-", $s[0]["SpouseBirth"])[0]."-".explode("-", $s[0]["SpouseDeath"])[0].")":"b. ".explode("-", $s[0]["SpouseBirth"])[0]?></dd> -->
    <?php } ?>



    <?php
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

    function cmpDates($a, $b){
        $ad = trim($a);
        $bd = trim($b);
        if(strlen($ad) == 4) $ad .= "-99-99";
        if(strlen($bd) == 4) $bd .= "-99-99";
        if(strlen($ad) == 7) $ad .= "-99";
        if(strlen($bd) == 7) $bd .= "-99";
        
        if (strtotime($ad) == strtotime($bd)) {
            return 0;
        }
        return (strtotime($ad) < strtotime($bd)) ? -1 : 1;
    }

    function sortMarriages($marriage1,$marriage2){
        //Different from cmpDates in that it compares marriageDate for the purpose of usort()
        //usort() cannot call cmpDates()
        $marriage1_date = ($marriage1["MarriageDate"]);
        $marriage2_date = ($marriage2["MarriageDate"]);
        if(strlen($marriage1_date) == 4) $marriage1_date .= "-99-99";
        
        if(strlen($marriage2_date) == 4) $marriage2_date .= "-99-99";
        if(strlen($marriage1_date) == 7) $marriage1_date .= "-99";
        if(strlen($marriage2_date) == 7) $marriage2_date .= "-99";

        if (strcmp($marriage1_date,$marriage2_date)==0) {
            return 0;
        }
        $res = (strcmp($marriage1_date,$marriage2_date)<0) ? -1 : 1;
        return $res;
    }
    ?>
    


</dl>