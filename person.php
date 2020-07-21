<!DOCTYPE html>
<!--
    Notes
    -----
-->

<?php

error_reporting(E_ALL);
ini_set("display_errors", 1);

    $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $split = explode("person.php", $url);
    $base_url = $split[0];
    if (!isset($_GET["id"])) {
        // creating a new person
        die("Missing UVA Person ID.  Cannot continue.");
    }
    // load the person
    $person = json_decode(file_get_contents($base_url . "api/edit_person.php?id=".$_GET["id"]), true);
    // load the brown data
    $brown = array();
    if (isset($_GET["brown"])) {
        $brown_id = $_GET["brown"];
        $brown = json_decode(file_get_contents($base_url . "api/brown_individual.php?id=".$brown_id), true);
        $brown = $brown[0];
    } else
        $brown_id = "UNKNOWN";

    /*
     * Display Dates
     *
     * Takes a YYYY-MM-DD date string and splits it out appropriately.  Then, will print out the
     * html required to display that date as a data entry element.  The prefix and suffix params
     * are used around the portion of the date (day, month, or year) to define the name of the 
     * input box.  Currently, it uses the format:
     *              YYYY    Month (select)  DD
     */
    function displayDate($datestr, $prefix, $suffix) {
        $dateSplit = explode("-", $datestr);
        if (!isset($dateSplit[0]) || empty($dateSplit[0]))
            $dateSplit[0] = "";
        if (!isset($dateSplit[1]) || empty($dateSplit[1]))
            $dateSplit[1] = "";
        if (!isset($dateSplit[2]) || empty($dateSplit[2]))
            $dateSplit[2] = "";

        $month = '';
        for( $i = 1; $i <= 12; $i++ ) {
            if ($i == $dateSplit[1])
                $month = date( 'F', mktime( 0, 0, 0, $i + 1, 0, 0 ) );
        }
        echo "<span>{$dateSplit[2]}</span> \n";
        echo "<span>$month</span> \n";
        echo "<span>{$dateSplit[0]}</span>\n";

    }

?>
<html>
    <head>
        <title>Nauvoo - View Entry</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="css/bootstrap.css" rel="stylesheet" media="screen">
        <link href="css/form.css" rel="stylesheet" media="screen">
        <link href="css/jquery.fancybox.css" rel="stylesheet" media="screen">
        <script type="text/javascript" src="js/view_page/jquery-1-10-2-min.js"></script>
        <script type="text/javascript" src="js/view_page/chosen.jquery.js"></script>
        <script type="text/javascript" src="js/view_page/bootstrap.min.js"></script>
        <!--<script type="text/javascript" src="js/custom-form.js"></script>
        <script type="text/javascript" src="js/custom-form.scrollable.js"></script>
        <script type="text/javascript" src="js/scripts.js"></script>
        <link href="//cdnjs.cloudflare.com/ajax/libs/select2/4.0.0-rc.1/css/select2.min.css" rel="stylesheet" />
        <script src="//cdnjs.cloudflare.com/ajax/libs/select2/4.0.0-rc.1/js/select2.min.js"></script>
        <script type="text/javascript" src="js/custom-form.file.js"></script>-->
        <script type="text/javascript" src="js/view_page/jquery.mousewheel-3.0.6.pack.js"></script>
        <script type="text/javascript" src="js/view_pagejquery.fancybox.pack.js"></script>
        <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css">
        <link rel="stylesheet" type="text/css" href="css/view_page_style.css" media="all">

<style>
.form-control-static, .frame span, p.option {
padding-top: 0px;
padding-bottom: 0px;
margin-bottom: 0;
margin-top: 10px;
font-size: 14px;
line-height: 18px;
display: block;
float: left;
padding-right: 3px;
}
.form-control-static::after, .frame span::after {
content: ' ';
}
label {
font-weight: bold!important;
}
.heading {
margin-bottom:0px!important;
}
</style>
    </head>
    <body>
        <div id="wrapper">
            <div class="main-area container">
                <div class="page-header page-header-01">
                    <div class="frame">
                        <div class="clearfix">
                        </div>
                    </div><!-- frame -->
<?php
    $n_i = 1;
    foreach ($person["names"] as $name) {
        if ($name["Type"] == 'authoritative') {
            echo "<h1>
                                                <span>{$name["Prefix"]}</span>
                                                <span>{$name["First"]}</span>
                                                <span>{$name["Middle"]}</span>
                                                <span>{$name["Last"]}</span>
                                                <span>{$name["Suffix"]}</span>
</h1>";
            $n_i++;
        } // endif
    } // end foreach
?>
                </div><!-- page-header -->
                <div class="alert alert-01 alert-success" style="display: none">
                    <p>Successfully saved!</p>
                </div><!-- end alert -->
                <div class="alert alert-01 alert-failure" style="display: none">
                    <p>An error occured while saving</p>
                </div><!-- end alert -->
                <div class="clearfix">
                    <div id="nauvoo_form" action="#">
                        <fieldset>
                            <!-- This is the sidebar -->
                            <aside id="aside">
                                <h2 class="visible-md visible-lg">Record Information</h2>
                                <div class="details-bar">
                                    <a href="#" data-toggle="dropdown" class="open-close"></a>
                                    <div class="drop dropdown-menu" role="menu">
                                        <div class="info-box">
                                            <dl>
                                            <dt class="visible-md visible-lg">UVA Person ID:</dt><dd class="visible-md visible-lg" id="UVAPersonID"><?=$person["information"]["ID"]?></dd>
<?php
    if ($brown_id != "UNKNOWN") {
        echo "<dt class=\"visible-md visible-lg\">Brown ID:</dt><dd class=\"visible-md visible-lg\">$brown_id</dd>";
        if (count($person["brown_ids"]) > 1) {
            echo '<dt class="visible-md visible-lg">Other Brown IDs:</dt>';
            //=$brown_id
            foreach ($person["brown_ids"] as $alt_id) {
                if ($alt_id != $brown_id)
                    echo "<dd class=\"visible-md visible-lg\"><a href=\"?brown=$alt_id&id={$person["information"]["ID"]}\">$alt_id</a></dd>";
            }
        }
    } else if (!empty($person["brown_ids"])) {
        echo '<dt class="visible-md visible-lg">Available Brown IDs:</dt>';
        foreach ($person["brown_ids"] as $alt_id) {
            echo "<dd class=\"visible-md visible-lg\"><a href=\"?brown=$alt_id&id={$person["information"]["ID"]}\">$alt_id</a></dd>";
        }
    }
?>
                                            
                                            
                                            </dl>
                                        </div><!-- info-box -->
                                    </div>
                                </div><!-- details-bar -->
<?php
    // SHOW THE FOLLOWING ONLY IF THE BROWN ID IS ACTUALLY SET
    // This will hopefully remove some confusion when looking at the page without
    // a Brown entry linked.
    if ($brown_id != "UNKNOWN") {
?>
                                <h2 class="visible-md visible-lg">Brown Information</h2>
                                <div class="box">
                                    <h3>Context</h3>
                                    <div class="subbox">
                                        <p><?=isset($brown["context"]) ? $brown["context"]:""?></p>
                                    </div>
                                    <h3>Name</h3>
                                    <div class="subbox">
                                        <h4><?=isset($brown["Name"])?$brown["Name"]:""?></h4>
                                        <p><?=isset($brown["NameFootnotes"])?$brown["NameFootnotes"]:""?></p>
                                    </div>
                                    <h3>Birthdate</h3>
                                    <div class="subbox">
                                        <h4><?=isset($brown["BD"])?$brown["BD"]:""?></h4>
                                        <p><?=isset($brown["BDFootnotes"])?$brown["BDFootnotes"]:""?></p>
                                    </div>
                                    <h3>Priesthood</h3>
                                    <div class="subbox">
                                        <h4><?=isset($brown["PH"])?$brown["PH"]:""?></h4>
                                        <p><?=isset($brown["PHFootnotes"])?$brown["PHFootnotes"]:""?></p>
                                    </div>
                                    <h3>Endowment</h3>
                                    <div class="subbox">
                                        <h4><?=isset($brown["E"])?$brown["E"]:""?></h4>
                                        <p><?=isset($brown["EFootnotes"])?$brown["EFootnotes"]:""?></p>
                                    </div>
                                    <h3>Sealed / Marriage</h3>
                                    <div class="subbox">
                                        <h4><?=isset($brown["SM"])?$brown["SM"]:""?></h4>
                                        <p><?=isset($brown["SMFootnotes"])?$brown["SMFootnotes"]:""?></p>
                                    </div>
                                    <h3>Adopted / Sealed Child</h3>
                                    <div class="subbox">
                                        <h4><?=isset($brown["ASC"])?$brown["ASC"]:""?></h4>
                                        <p><?=isset($brown["ASCFootnotes"])?$brown["ASCFootnotes"]:""?></p>
                                    </div>
                                    <h3>Second Anointing</h3>
                                    <div class="subbox">
                                        <h4><?=isset($brown["SA"])?$brown["SA"]:""?></h4>
                                        <p><?=isset($brown["SAFootnotes"])?$brown["SAFootnotes"]:""?></p>
                                    </div>
                                </div><!-- details-bar -->
<?php
    } // end of $brown_id != "UNKNOWN" if statement
?>
                            </aside><!-- aside -->
                            <section class="tabs">
                            <ul class="nav nav-tabs">
                                <li class="active"><a href="#tab-01" data-toggle="tab">Personal Information</a></li>
                                <li><a href="#tab-02" data-toggle="tab">Non-Sealing Ordinances (NSO)</a></li>
                                <li><a href="#tab-03" data-toggle="tab">Sealed Child (SC)</a></li>
                                <li><a href="#tab-04" data-toggle="tab">Marriages (M)</a></li>
                                <li><a href="#tab-05" data-toggle="tab">Offices (O)</a></li>
                            </ul><!-- nav-tabs -->
                            <div class="tab-content">
                                <!-- Personal Information -->
                                <div class="tab-pane active" id="tab-01">
                                    <section class="section">
                                    <div class="heading">
                                        <h2>Alternative Names (Also Known As)</h2>
                                    </div>
                                    <div class="form-area name-form" id="alternative-names">
<?php
    foreach ($person["names"] as $name) {
        if ($name["Type"] == 'alternate') {
            echo "
                                        <div class=\"row-area\" id=\"name_$n_i\">
                                            
                                            
                                            <div class=\"frame\">
                                                <span>{$name["Prefix"]}</span>
                                            </div>
                                            <div class=\"frame\">
                                                <span>{$name["First"]}</span>
                                            </div>
                                            <div class=\"frame\">
                                                <span>{$name["Middle"]}</span>
                                            </div>
                                            <div class=\"frame\">
                                                <span>{$name["Last"]}</span>
                                            </div>
                                            <div class=\"frame\">
                                                <span>{$name["Suffix"]}</span>
                                            </div>
                                        </div><!-- row-area -->
";
            $n_i++;
        } // endif
    } // end foreach
?>
                                    </div><!-- form-area -->
                                    <div class="form-area">
                                        <div class="row-area">
                                        </div><!-- row-area -->
                                    </div><!-- form-area -->
                                    </section><!-- section -->
                                    <section class="section">
                                    <div class="heading">
                                        <h2>Birth Information</h2>
                                    </div>
                                    <div class="form-area">
                                        <div class="row-area">
                                            <div class="col-area">
                                                <div class="frame">
                                                    <label class="fixed" for="gender">Gender:</label>
                                                    <span class="form-control-static"><?=$person["information"]["Gender"]?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row-area">
                                            <div class="col-area">
                                                <div class="frame">
                                                    <label class="fixed">Birth Date:</label>
                                                    <?php displayDate($person["information"]["BirthDate"], "birth", ""); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row-area">
                                            <div class="col-area">
                                                <div class="frame">
                                                    <label class="fixed" for="b_place_id">Birth Place:</label>
                                                    
                                                        
                                                        <span><?=$person["information"]["BirthPlaceName"]?></span>
                                                    
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row-area">
                                            <div class="col-area">
                                                <div class="frame">
                                                    <label class="fixed" for="bpmarriage">Birth Parent Marriage:</label>
                                                    
                                                        
                                                        <span><?=$person["information"]["ParentMarriageString"]?></span>
                                                    
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    </section><!-- section -->
                                    <section class="section">
                                    <div class="heading">
                                        <h2>Death Information</h2>
                                    </div>
                                    <div class="form-area">
                                        <div class="row-area">
                                            <div class="col-area">
                                                <div class="frame">
                                                    <label class="fixed">Death Date:</label>
                                                    <?php displayDate($person["information"]["DeathDate"], "death", ""); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row-area">
                                            <div class="col-area">
                                                <div class="frame">
                                                    <label class="fixed" for="d_place_id">Death Place:</label>
                                                    
                                                        
                                                        <span><?=$person["information"]["DeathPlaceName"]?></span>
                                                    
                                                </div>
                                            </div>
                                        </div>
                                        </section><!-- section -->
                                        <section class="section">
                                        <div class="heading">
                                            <h2>Notes</h2>
                                        </div>
                                        <div class="notes">
                                        <p class='textarea'><?=$person["notes"]["personal"]?></p>
                                        </div>
                                        </section><!-- section -->
                                    </div><!-- tab-01 -->
                                    <!-- Temple Rites -->
                                    <div class="tab-pane" id="tab-02">
                                        <section class="section">
                                        <div class="heading">
                                            <h2>Non-Sealing Ordinances Information</h2>
                                        </div>
                                        <div>
                                            <div id="temple-rites-formarea">
<?php
    $r_i = 1;
if ($person["temple_rites"] != null && $person["temple_rites"] != false) {
    foreach ($person["temple_rites"] as $rite) {
        
        if ($rite["ProxyID"] == null)
            $rite["ProxyName"] = "";
        if ($rite["AnnointedToID"] == null)
            $rite["AnnointedToName"] = "";
        if ($rite["AnnointedToProxyID"] == null)
            $rite["AnnointedToProxyName"] = "";


?>
                                                <div class="row-area form-area form-block" id="tr_<?=$r_i?>">
                                                    <div class="delete-area">
                                                        
                                                    </div>
                                                    <div class="row-area">
                                                        
                                                        <div class="frame">
                                                            <label class="fixed" for="tr_type_<?=$r_i?>">Type:</label>
                                                            
                                                                <?php if ($rite["Type"] == "baptism")  echo "<p class='option'>Baptism</p>";?>
                                                                <?php if ($rite["Type"] == "endowment")  echo "<p class='option'>Endowment</p>";?>
                                                                <?php if ($rite["Type"] == "secondAnnointing")  echo "<p class='option'>Second Anointing</p>";?>
                                                                <?php if ($rite["Type"] == "secondAnnointingTime")  echo "<p class='option'>Second Anointing (for time)</p>";?>
                                                                <?php if ($rite["Type"] == "firstAnnointing")  echo "<p class='option'>First Anointing</p>";?>
                                                            
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed">Date:</label>
                                                            <?php displayDate($rite["Date"], "tr_date_", "_".$r_i); ?>
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="tr_place_id_<?=$r_i?>">Place:</label>
                                                            
                                                                
                                                                <span><?=$rite["PlaceName"]?></span>
                                                            
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="tr_officiator_person_id_<?=$r_i?>">Officiator:</label>
                                                            
                                                                
                                                                <span><?=$rite["OfficiatorName"]?></span>
                                                            
                                                        </div>
                                                    </div>
                                                    <!--
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="tr_officiator_role_<?=$r_i?>">Officiator Role:</label>
                                                            <input type="text" class="form-control" value="<?=$rite["OfficiatorRole"]?>" id="tr_officiator_role_<?=$r_i?>" name="tr_officiator_role_<?=$r_i?>" size="25">
                                                        </div>
                                                    </div>
                                                    -->
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="tr_proxy_person_id_<?=$r_i?>">Proxy:</label>
                                                            
                                                                
                                                                <span><?=$rite["ProxyName"]?></span>
                                                            
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="tr_annointed_to_person_id_<?=$r_i?>">Anointed To:</label>
                                                            
                                                                
                                                                <span><?=$rite["AnnointedToName"]?></span>
                                                            
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="tr_annointed_to_proxy_person_id_<?=$r_i?>">Anointed To (Proxy):</label>
                                                            
                                                                
                                                                <span><?=$rite["AnnointedToProxyName"]?></span>
                                                            
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="tr_name_id_<?=$r_i?>">Name as Performed:</label>
                                                            
                                                                <span><?=trim($rite["NameUsed"])?></span>
                                                            
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="tr_notes_<?=$r_i?>">Notes:</label>
                                                            <span><?=$rite["PrivateNotes"]?></span>
                                                        </div>
                                                    </div>
                                                </div>
<?php
    $r_i++;
    } // Temple Rites for loop
}
?>
                                            </div>
                                        </div><!-- info-form -->
                                        <div class="form-area">
                                            <div class="row-area">
                                            </div><!-- row-area -->
                                        </div><!-- form-area -->
                                        </section><!-- section -->
                                        <section class="section">
                                        <div class="heading">
                                            <h2>Notes</h2>
                                        </div>
                                        <div class="notes">
                                        <p class='textarea'><?=$person["notes"]["rites"]?></p>
                                        </div>
                                        </section><!-- section -->
                                    </div><!-- tab-02 -->
                                    <!-- Non-Marital Sealings -->
                                    <div class="tab-pane" id="tab-03">
                                        <section class="section">
                                        <div class="heading">
                                            <h2>Sealed Child Information</h2>
                                        </div>
                                        <div>
                                            <div id="nonmarital-sealings-formarea">
<?php
    $s_i = 1;
if ($person["non_marital_sealings"] != null && $person["non_marital_sealings"] != false) {
    foreach ($person["non_marital_sealings"] as $sealing) {

        if ($sealing["AdopteeProxyID"] == null)
            $sealing["ProxyName"] = "";
        if ($sealing["MarriageProxyID"] == null)
            $sealing["ProxyMarriageString"] = "";



?>
                                                <div class="row-area form-area form-block" id="nms_<?=$s_i?>">
                                                    <div class="delete-area">
                                                        
                                                    </div>
                                                    <div class="row-area">
                                                    
                                                        <div class="frame">
                                                            <label class="fixed" for="nms_type_<?=$s_i?>">Type:</label>
                                                            
                                                                
                                                                <?php if ($sealing["Type"] == "adoption")  echo "<p class='option'>Adoption</p>";?>
                                                                <?php if ($sealing["Type"] == "natural")  echo "<p class='option'>Natural</p>";?>
                                                            
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed">Date:</label>
                                                            <?php displayDate($sealing["Date"], "nms_date_", "_".$s_i); ?>
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="nms_place_id_<?=$s_i?>">Place:</label>
                                                            
                                                                
                                                                <span><?=$sealing["PlaceName"]?></span>
                                                            
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="nms_officiator_person_id_<?=$s_i?>">Officiator:</label>
                                                            
                                                                
                                                                <span><?=$sealing["OfficiatorName"]?></span>
                                                            
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="nms_proxy_person_id_<?=$s_i?>">Proxy:</label>
                                                            
                                                                
                                                                <span><?=$sealing["ProxyName"]?></span>
                                                            
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="nms_marriage_id_<?=$s_i?>">Sealed to Marriage:</label>
                                                            
                                                                
                                                                <span><?=$sealing["MarriageString"]?></span>
                                                            
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="nms_proxy_father_person_id_<?=$s_i?>">Proxy Father:</label>
                                                            
                                                                
                                                                <span><?=$sealing["ProxyFatherName"] != NULL ? $sealing["ProxyFatherName"] : ""?></span>
                                                            
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="nms_proxy_mother_person_id_<?=$s_i?>">Proxy Mother:</label>
                                                            
                                                                
                                                                <span><?=$sealing["ProxyMotherName"] != NULL ? $sealing["ProxyMotherName"] : ""?></span>
                                                            
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="nms_name_id_<?=$s_i?>">Name as Sealed:</label>
                                                            
                                                                <span><?=trim($sealing["NameUsed"])?></span>
                                                            
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="nms_notes_<?=$s_i?>">Notes:</label>
                                                            <span><?=$sealing["PrivateNotes"]?></span>
                                                        </div>
                                                    </div>
                                                </div>
<?php
    $s_i++;
    } // Non marital Sealing for loop
}
?>
                                            </div>
                                        </div><!-- info-form -->
                                        <div class="form-area">
                                            <div class="row-area">
                                            </div><!-- row-area -->
                                        </div><!-- form-area -->
                                        </section><!-- section -->
                                        <section class="section">
                                        <div class="heading">
                                            <h2>Notes</h2>
                                        </div>
                                        <div class="notes">
                                            <p class='textarea'><?=$person["notes"]["nms"]?></p>
                                        </div>
                                        </section><!-- section -->
                                    </div>
                                    <div class="tab-pane" id="tab-04">
                                        <section class="section">
                                        <div class="heading">
                                            <h2>Marriages</h2>
                                        </div>
                                        <div>
                                            <div id="marital-sealings-formarea">
<?php
    $m_i = 1;
if ($person["marriages"] != null && $person["marriages"] != false) {
    foreach ($person["marriages"] as $marriage) {

?>
                                                <div class="row-area form-area form-block" id="mar_<?=$m_i?>">
                                                    <div class="delete-area">
                                                        
                                                    </div>
                                                    <div class="row-area">
                                                        
                                                        <div class="frame">
                                                            <label class="fixed" for="mar_type_<?=$m_i?>">Type:</label>
                                                            
                                                                
                                                                <?php if ($marriage["Type"] == "eternity")  echo "<p class='option'>Sealed for Eternity</p>";?>
                                                                <?php if ($marriage["Type"] == "time")  echo "<p class='option'>Sealed for Time</p>";?>
                                                                <?php if ($marriage["Type"] == "civil")  echo "<p class='option'>Civil Marriage</p>";?>
                                                            
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="mar_spouse_person_id_<?=$m_i?>">Spouse:</label>
                                                            
                                                                
                                                                <span><?php 
        $spouse_name = substr($marriage["SpouseName"],0,strrpos($marriage["SpouseName"], " "));
        $spouse_id = substr($marriage["SpouseName"],strrpos($marriage["SpouseName"], " ")+1);
        echo "<a href='person.php?id=$spouse_id'>$spouse_name</a>";
        ?></span>
                                                            
                                                        </div>
                                                    </div>
<?php
        if (is_numeric($marriage["children"]) && $marriage["children"] > 0)
            echo '        
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed">&nbsp;</label>
                                                            <p style="width:600px; margin:0px; padding:0px;">This marriage has '.$marriage["children"]. ' child(ren) in the database</p>
                                                        </div>
                                                    </div>';
        if (is_numeric($marriage["adoptees"]) && $marriage["adoptees"] > 0)
            echo '        
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed">&nbsp;</label>
                                                            <p style="width:600px; margin:0px; padding:0px;">This marriage has '.$marriage["adoptees"]. ' adoptee(s) in the database</p>
                                                        </div>
                                                    </div>';
?>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed">Date:</label>
                                                            <?php displayDate($marriage["MarriageDate"], "mar_date_", "_".$m_i); ?>
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed">Divorce Date:</label>
                                                            <?php displayDate($marriage["DivorceDate"], "mar_div_", "_".$m_i); ?>
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed">Cancelled Date:</label>
                                                            <?php displayDate($marriage["CancelledDate"], "mar_cancel_", "_".$m_i); ?>
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="mar_place_id_<?=$m_i?>">Place:</label>
                                                            
                                                                
                                                                <span><?=$marriage["PlaceName"]?></span>
                                                            
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="mar_officiator_person_id_<?=$m_i?>">Officiator:</label>
                                                            
                                                                
                                                                <?php echo $marriage["OfficiatorName"];?>
                                                            
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="mar_proxy_person_id_<?=$m_i?>">Proxy:</label>
                                                            
                                                                
                                                                <?php echo $marriage["ProxyName"];?>
                                                            
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="mar_spouse_proxy_person_id_<?=$m_i?>">Spouse Proxy:</label>
                                                            
                                                                
                                                                <?php echo $marriage["SpouseProxyName"];?>
                                                            
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="mar_name_id_<?=$m_i?>">Name as Sealed:</label>
                                                            
                                                                <span><?=trim($marriage["NameUsed"])?></span>
                                                            
                                                        </div>
                                                    </div>

                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="mar_notes_<?=$m_i?>">Notes:</label>
                                                            <span><?=$marriage["PrivateNotes"]?></span>
                                                        </div>
                                                    </div>
                                                </div>
<?php
        $m_i++;
    } // foreach marriage
}
?>
                                            </div>
                                        </div><!-- info-form -->
                                        <div class="form-area">
                                            <div class="row-area">
                                            </div><!-- row-area -->
                                        </div><!-- form-area -->
                                        </section><!-- section -->
                                        <section class="section">
                                        <div class="heading">
                                            <h2>Notes</h2>
                                        </div>
                                        <div class="notes">
                                            <p class='textarea'><?=$person["notes"]["marriage"]?></p>
                                        </div>
                                        </section><!-- section -->
                                    </div>
                                    <!-- Offices Held -->
                                    <div class="tab-pane" id="tab-05">
                                        <section class="section">
                                        <div class="heading">
                                            <h2>Offices Held Information</h2>
                                        </div>
                                        <div>
                                            <div id="offices-formarea">
<?php
    $o_i = 1;
if ($person["offices"] != null && $person["offices"] != false) {
    foreach ($person["offices"] as $office) {
        

?>
                                                <div class="row-area form-area form-block" id="office_<?=$o_i?>">
                                                    <div class="delete-area">
                                                        
                                                    </div>
                                                    
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="office_office_id_<?=$o_i?>">Office Name:</label>
                                                            
                                                                
                                                                <span><?=$office["OfficeName"]?></span>
                                                            
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed">Start Date:</label>
                                                            <?php displayDate($office["From"], "office_from_", "_".$o_i); ?>
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="office_from_status_<?=$o_i?>">Start Date Status:</label>
                                                            
                                                                <?php if ($office["FromStatus"] == "exact")  echo "<p class='option'>Specific Known Date</p>";?>
                                                                <?php if ($office["FromStatus"] == "notBefore")  echo "<p class='option'>Not Before This Date</p>";?>
                                                                <?php if ($office["FromStatus"] == "atLeastBy")  echo "<p class='option'>At Least By This Date</p>";?>
                                                                <?php if ($office["FromStatus"] == "other")  echo "<p class='option'>Other (Unusual)</p>";?>
                                                            
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed">End Date:</label>
                                                            <?php displayDate($office["To"], "office_to_", "_".$o_i); ?>
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="office_to_status_<?=$o_i?>">End Date Status:</label>
                                                            
                                                                <?php if ($office["ToStatus"] == "exact")  echo "<p class='option'>Specific Known Date</p>";?>
                                                                <?php if ($office["ToStatus"] == "notAfter")  echo "<p class='option'>Not After This Date</p>";?>
                                                                <?php if ($office["ToStatus"] == "atLeastUntil")  echo "<p class='option'>At Least Until This Date</p>";?>
                                                                <?php if ($office["ToStatus"] == "other")  echo "<p class='option'>Other (Unusual)</p>";?>
                                                            
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="office_officiator1_person_id_<?=$o_i?>">Officiator:</label>
                                                            
                                                                
                                                                <?php echo $office["OfficiatorName1"];?>
                                                            
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="office_officiator2_person_id_<?=$o_i?>">Officiator:</label>
                                                            
                                                                
                                                                <?php echo $office["OfficiatorName2"];?>
                                                            
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="office_officiator3_person_id_<?=$o_i?>">Officiator:</label>
                                                            
                                                                
                                                                <?php echo $office["OfficiatorName3"];?>
                                                            
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="office_notes_<?=$o_i?>">Notes:</label>
                                                            <span><?=$office["PrivateNotes"]?></span>
                                                        </div>
                                                    </div>
                                                </div>
<?php
    $o_i++;
    } // Offices for loop
}
?>
                                            </div>
                                        </div><!-- info-form -->
                                        <div class="form-area">
                                            <div class="row-area">
                                            </div><!-- row-area -->
                                        </div><!-- form-area -->
                                        </section><!-- section -->
                                    </div><!-- tab-02 -->
                                    </section><!-- tabs -->
                                </fieldset>
                            </div>
                        </div>
                    </div><!-- main-area -->
                </div><!-- wrapper -->


                </div>
                <!--<script type="text/javascript">
                    customForm.customForms.replaceAll();
                    </script>-->
                </body>
            </html>
