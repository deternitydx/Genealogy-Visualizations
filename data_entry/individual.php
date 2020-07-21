<!DOCTYPE html>
<!--
    Notes
    -----
-->

<?php

error_reporting(E_ALL);
ini_set("display_errors", 1);

    $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $split = explode("data_entry", $url);
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

        $month_options = '<option></option>';
        for( $i = 1; $i <= 12; $i++ ) {
            $attr = "";
            if ($i == $dateSplit[1])
                $attr = " selected";
            $month_name = date( 'F', mktime( 0, 0, 0, $i + 1, 0, 0 ) );
            $month_options .= "<option value=\"$i\"$attr>$month_name</option>";
        }
        echo "<input type=\"text\" class=\"form-control\" value=\"{$dateSplit[2]}\" placeholder=\"DD\" name=\"{$prefix}day{$suffix}\" size=\"2\"> \n";
        echo "<select class=\"form-date\" data-placeholder=\"Month\" name=\"{$prefix}month{$suffix}\" id=\"{$prefix}month{$suffix}\">$month_options</select> \n";
        echo "<input type=\"text\" class=\"form-control\" value=\"{$dateSplit[0]}\" placeholder=\"YYYY\" name=\"{$prefix}year{$suffix}\" size=\"4\">\n";

    }

?>
<html>
    <head>
        <title>Nauvoo - Edit Entry</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="css/bootstrap.css" rel="stylesheet" media="screen">
        <link href="css/form.css" rel="stylesheet" media="screen">
        <link href="css/jquery.fancybox.css" rel="stylesheet" media="screen">
        <script type="text/javascript" src="js/jquery-1-10-2-min.js"></script>
        <script type="text/javascript" src="js/chosen.jquery.js"></script>
        <script type="text/javascript" src="js/bootstrap.min.js"></script>
        <!--<script type="text/javascript" src="js/custom-form.js"></script>
        <script type="text/javascript" src="js/custom-form.scrollable.js"></script>
        <script type="text/javascript" src="js/custom-form.file.js"></script>-->
        <script type="text/javascript" src="js/jquery.mousewheel-3.0.6.pack.js"></script>
        <script type="text/javascript" src="js/jquery.fancybox.pack.js"></script>
        <script type="text/javascript" src="js/scripts.js"></script>
        <link href="//cdnjs.cloudflare.com/ajax/libs/select2/4.0.0-rc.1/css/select2.min.css" rel="stylesheet" />
        <script src="//cdnjs.cloudflare.com/ajax/libs/select2/4.0.0-rc.1/js/select2.min.js"></script>
        <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css">
        <link rel="stylesheet" type="text/css" href="css/styles.css" media="all">

    </head>
    <body>
        <div id="wrapper">
            <header>
            <div class="container">
                <strong class="logo"><a href="/">Nauvoo Database</a></strong>
            </div><!-- container -->
            </header><!-- header -->
            <div class="main-area container">
                <div class="page-header page-header-01">
                    <div class="frame">
                        <div class="clearfix">
<?php
        if ($brown_id != "UNKNOWN") {
            echo '                <a href="index.php" class="back-link">Back to Brown List</a> 
                            <a href="../data_view/people.php" class="back-link">UVA People List</a>';
        } else {
            echo '
                            <a href="../data_view/people.php" class="back-link">Back to UVA People List</a>';
        }
?>
                        </div>
                    </div><!-- frame -->
                    <h1>Edit Person</h1>
                </div><!-- page-header -->
                <div class="alert alert-01 alert-success" style="display: none">
                    <p>Successfully saved!</p>
                </div><!-- end alert -->
                <div class="alert alert-01 alert-failure" style="display: none">
                    <p>An error occured while saving</p>
                </div><!-- end alert -->
                <div class="clearfix">
                    <form id="nauvoo_form" action="#">
                        <fieldset>
                            <!-- This is the sidebar -->
                            <aside id="aside">
                                <h2 class="visible-md visible-lg">Record Information</h2>
                                <div class="box">
                                    <button id="button-record-save" class="btn btn-success btn-save ie-fix"><span>Save</span></button>
                                </div><!-- box -->
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
                                            <input type="hidden" name="ID" id="ID" value="<?=$person["information"]["ID"]?>">
                                            <input type="hidden" name="BrownID" id="BrownID" value="<?=$brown_id?>">
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
                                <h2 class="visible-md visible-lg">Brown Status</h2>
                                <div class="box">
<?php
        // Set up the checkbox for "DONE" status, if the brown entry exists
        if (isset($brown["Progress"])) {
            $status = $brown["Progress"];
            echo "<p>State: ";
            echo "<select name='brown_state' data-placeholder='Select Status' id='brown_state' style='width:150px;'>";
            $s = "";
            if ($status == "unseen")
                $s = " selected='selected'";
            echo "<option value='unseen'$s>Unseen</option>";
            $s = "";
            if ($status == "inProgress")
                $s = " selected='selected'";
            echo "<option value='inProgress'$s>In Progress</option>";
            $s = "";
            if ($status == "done")
                $s = " selected='selected'";
            echo "<option value='done'$s>Done</option>";
            echo "</select>";
            echo "</p>";
        }
?>                                    
                                </div>
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
                                        <h2>Authoritative Name</h2>
                                    </div>
                                    <div class="form-area name-form">
<?php
    $n_i = 1;
    foreach ($person["names"] as $name) {
        if ($name["Type"] == 'authoritative') {
            echo "
                                            <div class=\"row-area\" id=\"name_$n_i\">
                                            <input type=\"hidden\" class=\"form-control\" value=\"{$name["ID"]}\" id=\"name_id_$n_i\" name=\"name_id_$n_i\">
                                            <input type=\"hidden\" class=\"form-control\" value=\"authoritative\" id=\"name_type_$n_i\" name=\"name_type_$n_i\">
                                            <div class=\"frame\">
                                                <input type=\"text\" class=\"form-control\" value=\"{$name["Prefix"]}\" id=\"name_prefix_$n_i\" name=\"name_prefix_$n_i\" size=\"4\">
                                                <label for=\"name_prefix_$n_i\">Prefix</label>
                                            </div>
                                            <div class=\"frame\">
                                                <input type=\"text\" class=\"form-control\" value=\"{$name["First"]}\" id=\"name_first_$n_i\" name=\"name_first_$n_i\" size=\"14\">
                                                <label for=\"name_first_$n_i\">First</label>
                                            </div>
                                            <div class=\"frame\">
                                                <input type=\"text\" class=\"form-control\" value=\"{$name["Middle"]}\" id=\"name_middle_$n_i\" name=\"name_middle_$n_i\" size=\"10\">
                                                <label for=\"name_middle_$n_i\">Middle</label>
                                            </div>
                                            <div class=\"frame\">
                                                <input type=\"text\" class=\"form-control\" value=\"{$name["Last"]}\" id=\"name_last_$n_i\" name=\"name_last_$n_i\" size=\"14\">
                                                <label for=\"name_last_$n_i\">Last</label>
                                            </div>
                                            <div class=\"frame\">
                                                <input type=\"text\" class=\"form-control\" value=\"{$name["Suffix"]}\" id=\"name_suffix_$n_i\" name=\"name_suffix_$n_i\" size=\"4\">
                                                <label for=\"name_suffix_$n_i\">Suffix</label>
                                            </div>
                                        </div><!-- row-area -->
";
            $n_i++;
        } // endif
    } // end foreach
?>
                                    </div><!-- form-area -->
                                    </section><!-- section -->
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
                                                <div class=\"delete-area\">
                                                    <button id=\"name_delete_button_$n_i\" class=\"btn btn-warning ie-fix\" onClick=\"deleteEntry('name', $n_i); return false;\"><span><i class=\"fa fa-times\"></i></span></button>
                                                    <input type=\"hidden\" id=\"name_deleted_$n_i\" name=\"name_deleted_$n_i\" value=\"NO\">
                                                </div>
                                            <input type=\"hidden\" class=\"form-control\" value=\"{$name["ID"]}\" id=\"name_id_$n_i\" name=\"name_id_$n_i\">
                                            <input type=\"hidden\" class=\"form-control\" value=\"alternate\" id=\"name_type_$n_i\" name=\"name_type_$n_i\">
                                            <div class=\"frame\">
                                                <input type=\"text\" class=\"form-control\" value=\"{$name["Prefix"]}\" id=\"name_prefix_$n_i\" name=\"name_prefix_$n_i\" size=\"4\">
                                                <label for=\"name_prefix_$n_i\">Prefix</label>
                                            </div>
                                            <div class=\"frame\">
                                                <input type=\"text\" class=\"form-control\" value=\"{$name["First"]}\" id=\"name_first_$n_i\" name=\"name_first_$n_i\" size=\"14\">
                                                <label for=\"name_first_$n_i\">First</label>
                                            </div>
                                            <div class=\"frame\">
                                                <input type=\"text\" class=\"form-control\" value=\"{$name["Middle"]}\" id=\"name_middle_$n_i\" name=\"name_middle_$n_i\" size=\"10\">
                                                <label for=\"name_middle_$n_i\">Middle</label>
                                            </div>
                                            <div class=\"frame\">
                                                <input type=\"text\" class=\"form-control\" value=\"{$name["Last"]}\" id=\"name_last_$n_i\" name=\"name_last_$n_i\" size=\"14\">
                                                <label for=\"name_last_$n_i\">Last</label>
                                            </div>
                                            <div class=\"frame\">
                                                <input type=\"text\" class=\"form-control\" value=\"{$name["Suffix"]}\" id=\"name_suffix_$n_i\" name=\"name_suffix_$n_i\" size=\"4\">
                                                <label for=\"name_suffix_$n_i\">Suffix</label>
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
                                            <button id="button-add-name" class="btn btn-success btn-save ie-fix"><span>Add New Name</span></button>
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
                                                    <select class="form-selector" data-placeholder="Select Gender" id="gender" name="gender">
                                                        <option></option>
                                                        <option value="Male" <?php if ($person["information"]["Gender"] == "Male") echo "selected";?>>Male</option>
                                                        <option value="Female" <?php if ($person["information"]["Gender"] == "Female") echo "selected";?>>Female</option>
                                                    </select>
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
                                                    <select data-placeholder="Select Birth Place" class="form-control" id="b_place_id" name="b_place_id">
                                                        <option></option>
                                                        <option value="<?=$person["information"]["BirthPlaceID"]?>" selected="selected"><?=$person["information"]["BirthPlaceName"]?></option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row-area">
                                            <div class="col-area">
                                                <div class="frame">
                                                    <label class="fixed" for="bpmarriage">Birth Parent Marriage:</label>
                                                    <select data-placeholder="Select Parent Marriage" class="form-control" id="b_marriage_id" name="b_marriage_id">
                                                        <option></option>
                                                        <option value="<?=$person["information"]["BiologicalChildOfMarriage"]?>" selected><?=$person["information"]["ParentMarriageString"]?></option>
                                                    </select>
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
                                                    <select data-placeholder="Select Death Place" class="form-control" id="d_place_id" name="d_place_id">
                                                        <option></option>
                                                        <option value="<?=$person["information"]["DeathPlaceID"]?>" selected="selected"><?=$person["information"]["DeathPlaceName"]?></option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        </section><!-- section -->
                                        <section class="section">
                                        <div class="heading">
                                            <h2>Notes</h2>
                                        </div>
                                        <div class="notes">
                                        <textarea class="form-control" cols="1" rows="1" id="personal_notes" name="personal_notes"><?=$person["notes"]["personal"]?></textarea>
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
                                                        <button id="tr_delete_button_<?=$r_i?>" class="btn btn-warning ie-fix" onClick="deleteEntry('tr', <?=$r_i?>); return false;"><span><i class="fa fa-times"></i></span></button>
                                                        <input type="hidden" id="tr_deleted_<?=$r_i?>" name="tr_deleted_<?=$r_i?>" value="NO">
                                                    </div>
                                                    <div class="row-area">
                                                        <input type="hidden" name="tr_id_<?=$r_i?>" id="tr_id_<?=$r_i?>" value="<?=$rite["ID"]?>">
                                                        <div class="frame">
                                                            <label class="fixed" for="tr_type_<?=$r_i?>">Type:</label>
                                                            <select class="form-selector" data-placeholder="Select Type" id="tr_type_<?=$r_i?>" name="tr_type_<?=$r_i?>">
                                                                <option value=""></option>
                                                                <option value="baptism" <?php if ($rite["Type"] == "baptism") echo "selected";?>>Baptism</option>
                                                                <option value="endowment" <?php if ($rite["Type"] == "endowment") echo "selected";?>>Endowment</option>
                                                                <option value="secondAnnointing" <?php if ($rite["Type"] == "secondAnnointing") echo "selected";?>>Second Anointing</option>
                                                                <option value="secondAnnointingTime" <?php if ($rite["Type"] == "secondAnnointingTime") echo "selected";?>>Second Anointing (for time)</option>
                                                                <option value="firstAnnointing" <?php if ($rite["Type"] == "firstAnnointing") echo "selected";?>>First Anointing</option>
                                                            </select>
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
                                                            <select data-placeholder="Select Place" class="form-control" id="tr_place_id_<?=$r_i?>" name="tr_place_id_<?=$r_i?>">
                                                                <option></option>
                                                                <option value="<?=$rite["PlaceID"]?>" selected="selected"><?=$rite["PlaceName"]?></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="tr_officiator_person_id_<?=$r_i?>">Officiator:</label>
                                                            <select data-placeholder="Select Officiator" class="form-control" id="tr_officiator_person_id_<?=$r_i?>" name="tr_officiator_person_id_<?=$r_i?>">
                                                                <option></option>
                                                                <option value="<?=$rite["OfficiatorID"]?>" selected="selected"><?=$rite["OfficiatorName"]?></option>
                                                            </select>
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
                                                            <select data-placeholder="Select Proxy" class="form-control" id="tr_proxy_person_id_<?=$r_i?>" name="tr_proxy_person_id_<?=$r_i?>">
                                                                <option></option>
                                                                <option value="<?=$rite["ProxyID"]?>" selected="selected"><?=$rite["ProxyName"]?></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="tr_annointed_to_person_id_<?=$r_i?>">Anointed To:</label>
                                                            <select data-placeholder="Select Anointed To" class="form-control" id="tr_annointed_to_person_id_<?=$r_i?>" name="tr_annointed_to_person_id_<?=$r_i?>">
                                                                <option></option>
                                                                <option value="<?=$rite["AnnointedToID"]?>" selected="selected"><?=$rite["AnnointedToName"]?></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="tr_annointed_to_proxy_person_id_<?=$r_i?>">Anointed To (Proxy):</label>
                                                            <select data-placeholder="Select Anointed To (Proxy)" class="form-control" id="tr_annointed_to_proxy_person_id_<?=$r_i?>" name="tr_annointed_to_proxy_person_id_<?=$r_i?>">
                                                                <option></option>
                                                                <option value="<?=$rite["AnnointedToProxyID"]?>" selected="selected"><?=$rite["AnnointedToProxyName"]?></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="tr_name_id_<?=$r_i?>">Name as Performed:</label>
                                                            <select data-placeholder="Select Name as Performed" class="form-control" id="tr_name_id_<?=$r_i?>" name="tr_name_id_<?=$r_i?>">
                                                                <option value=""></option>
                                                                <option value="<?=$rite["NameUsedID"]?>" selected="selected"><?=trim($rite["NameUsed"])?></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="tr_notes_<?=$r_i?>">Notes:</label>
                                                            <textarea class="notes-field" id="tr_notes_<?=$r_i?>" name="tr_notes_<?=$r_i?>"><?=$rite["PrivateNotes"]?></textarea>
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
                                                <button id="button-add-rite" class="btn btn-success btn-save ie-fix"><span>Add New Non-Sealing Ordinance</span></button>
                                            </div><!-- row-area -->
                                        </div><!-- form-area -->
                                        </section><!-- section -->
                                        <section class="section">
                                        <div class="heading">
                                            <h2>Notes</h2>
                                        </div>
                                        <div class="notes">
                                        <textarea class="form-control" cols="1" rows="1" id="temple_rite_notes" name="temple_rite_notes"><?=$person["notes"]["rites"]?></textarea>
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
                                                        <button id="nms_delete_button_<?=$s_i?>" class="btn btn-warning ie-fix" onClick="deleteEntry('nms', <?=$s_i?>); return false;"><span><i class="fa fa-times"></i></span></button>
                                                        <input type="hidden" id="nms_deleted_<?=$s_i?>" name="nms_deleted_<?=$s_i?>" value="NO">
                                                    </div>
                                                    <div class="row-area">
                                                    <input type="hidden" name="nms_id_<?=$s_i?>" id="nms_id_<?=$s_i?>" value="<?=$sealing["ID"]?>">
                                                        <div class="frame">
                                                            <label class="fixed" for="nms_type_<?=$s_i?>">Type:</label>
                                                            <select class="form-selector" data-placeholder="Select Type" id="nms_type_<?=$s_i?>" name="nms_type_<?=$s_i?>">
                                                                <option></option>
                                                                <option value="adoption" <?php if ($sealing["Type"] == "adoption") echo "selected";?>>Adoption</option>
                                                                <option value="natural" <?php if ($sealing["Type"] == "natural") echo "selected";?>>Natural</option>
                                                            </select>
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
                                                            <select data-placeholder="Select Place" class="form-control" id="nms_place_id_<?=$s_i?>" name="nms_place_id_<?=$s_i?>">
                                                                <option></option>
                                                                <option value="<?=$sealing["PlaceID"]?>" selected="selected"><?=$sealing["PlaceName"]?></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="nms_officiator_person_id_<?=$s_i?>">Officiator:</label>
                                                            <select data-placeholder="Select Officiator" class="form-control" id="nms_officiator_person_id_<?=$s_i?>" name="nms_officiator_person_id_<?=$s_i?>">
                                                                <option></option>
                                                                <option value="<?=$sealing["OfficiatorID"]?>" selected="selected"><?=$sealing["OfficiatorName"]?></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="nms_proxy_person_id_<?=$s_i?>">Proxy:</label>
                                                            <select data-placeholder="Select Proxy" class="form-control" id="nms_proxy_person_id_<?=$s_i?>" name="nms_proxy_person_id_<?=$s_i?>">
                                                                <option></option>
                                                                <option value="<?=$sealing["AdopteeProxyID"]?>" selected="selected"><?=$sealing["ProxyName"]?></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="nms_marriage_id_<?=$s_i?>">Sealed to Marriage:</label>
                                                            <select data-placeholder="Select Sealed to Marriage" class="form-control" id="nms_marriage_id_<?=$s_i?>" name="nms_marriage_id_<?=$s_i?>">
                                                                <option></option>
                                                                <option value="<?=$sealing["MarriageID"]?>" selected="selected"><?=$sealing["MarriageString"]?></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="nms_proxy_father_person_id_<?=$s_i?>">Proxy Father:</label>
                                                            <select data-placeholder="Select Proxy Father" class="form-control" id="nms_proxy_father_person_id_<?=$s_i?>" name="nms_proxy_father_person_id_<?=$s_i?>">
                                                                <option></option>
                                                                <option value="<?=$sealing["FatherProxyID"]?>" selected="selected"><?=$sealing["ProxyFatherName"] != NULL ? $sealing["ProxyFatherName"] : ""?></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="nms_proxy_mother_person_id_<?=$s_i?>">Proxy Mother:</label>
                                                            <select data-placeholder="Select Proxy Mother" class="form-control" id="nms_proxy_mother_person_id_<?=$s_i?>" name="nms_proxy_mother_person_id_<?=$s_i?>">
                                                                <option></option>
                                                                <option value="<?=$sealing["MotherProxyID"]?>" selected="selected"><?=$sealing["ProxyMotherName"] != NULL ? $sealing["ProxyMotherName"] : ""?></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="nms_name_id_<?=$s_i?>">Name as Sealed:</label>
                                                            <select data-placeholder="Select Name as Sealed" class="form-control" id="nms_name_id_<?=$s_i?>" name="nms_name_id_<?=$s_i?>">
                                                                <option value=""></option>
                                                                <option value="<?=$sealing["NameUsedID"]?>" selected="selected"><?=trim($sealing["NameUsed"])?></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="nms_notes_<?=$s_i?>">Notes:</label>
                                                            <textarea class="notes-field" id="nms_notes_<?=$s_i?>" name="nms_notes_<?=$s_i?>"><?=$sealing["PrivateNotes"]?></textarea>
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
                                                <button id="button-add-nonmarital" class="btn btn-success btn-save ie-fix"><span>Add New Sealed Child Information</span></button>
                                            </div><!-- row-area -->
                                        </div><!-- form-area -->
                                        </section><!-- section -->
                                        <section class="section">
                                        <div class="heading">
                                            <h2>Notes</h2>
                                        </div>
                                        <div class="notes">
                                            <textarea class="form-control" cols="1" rows="1" id="non_marital_notes" name="non_marital_notes"><?=$person["notes"]["nms"]?></textarea>
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
                                                        <button id="mar_delete_button_<?=$m_i?>" class="btn btn-warning ie-fix" onClick="deleteEntry('mar', <?=$m_i?>); return false;"><span><i class="fa fa-times"></i></span></button>
                                                        <input type="hidden" id="mar_deleted_<?=$m_i?>" name="mar_deleted_<?=$m_i?>" value="NO">
                                                    </div>
                                                    <div class="row-area">
                                                        <input type="hidden" name="mar_id_<?=$m_i?>" id="mar_id_<?=$m_i?>" value="<?=$marriage["ID"]?>">
                                                        <div class="frame">
                                                            <label class="fixed" for="mar_type_<?=$m_i?>">Type:</label>
                                                            <select class="form-selector" data-placeholder="Select Type" id="mar_type_<?=$m_i?>" name="mar_type_<?=$m_i?>">
                                                                <option></option>
                                                                <option value="eternity" <?php if ($marriage["Type"] == "eternity") echo "selected";?>>Sealed for Eternity</option>
                                                                <option value="time" <?php if ($marriage["Type"] == "time") echo "selected";?>>Sealed for Time</option>
                                                                <option value="civil" <?php if ($marriage["Type"] == "civil") echo "selected";?>>Civil Marriage</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="mar_spouse_person_id_<?=$m_i?>">Spouse:</label>
                                                            <select data-placeholder="Select Spouse" class="form-control" id="mar_spouse_person_id_<?=$m_i?>" name="mar_spouse_person_id_<?=$m_i?>">
                                                                <option></option>
                                                                <option value="<?=$marriage["SpouseID"]?>" selected="selected"><?php echo $marriage["SpouseName"];?></option>
                                                            </select>
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
                                                            <select data-placeholder="Select Place" class="form-control" id="mar_place_id_<?=$m_i?>" name="mar_place_id_<?=$m_i?>">
                                                                <option></option>
                                                                <option value="<?=$marriage["PlaceID"]?>" selected="selected"><?=$marriage["PlaceName"]?></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="mar_officiator_person_id_<?=$m_i?>">Officiator:</label>
                                                            <select data-placeholder="Select Officiator" class="form-control" id="mar_officiator_person_id_<?=$m_i?>" name="mar_officiator_person_id_<?=$m_i?>">
                                                                <option></option>
                                                                <option value="<?=$marriage["OfficiatorID"]?>" selected="selected"><?php echo $marriage["OfficiatorName"];?></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="mar_proxy_person_id_<?=$m_i?>">Proxy:</label>
                                                            <select data-placeholder="Select Proxy" class="form-control" id="mar_proxy_person_id_<?=$m_i?>" name="mar_proxy_person_id_<?=$m_i?>">
                                                                <option></option>
                                                                <option value="<?=$marriage["ProxyID"]?>" selected="selected"><?php echo $marriage["ProxyName"];?></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="mar_spouse_proxy_person_id_<?=$m_i?>">Spouse Proxy:</label>
                                                            <select data-placeholder="Select Spouse Proxy" class="form-control" id="mar_spouse_proxy_person_id_<?=$m_i?>" name="mar_spouse_proxy_person_id_<?=$m_i?>">
                                                                <option></option>
                                                                <option value="<?=$marriage["SpouseProxyID"]?>" selected="selected"><?php echo $marriage["SpouseProxyName"];?></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="mar_name_id_<?=$m_i?>">Name as Sealed:</label>
                                                            <select data-placeholder="Select Name as Sealed" class="form-control" id="mar_name_id_<?=$m_i?>" name="mar_name_id_<?=$m_i?>">
                                                                <option value=""></option>
                                                                <option value="<?=$marriage["NameUsedID"]?>" selected="selected"><?=trim($marriage["NameUsed"])?></option>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="mar_notes_<?=$m_i?>">Notes:</label>
                                                            <textarea class="notes-field" id="mar_notes_<?=$m_i?>" name="mar_notes_<?=$m_i?>"><?=$marriage["PrivateNotes"]?></textarea>
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
                                                <button id="button-add-marriage" class="btn btn-success btn-save ie-fix"><span>Add New Marriage</span></button>
                                            </div><!-- row-area -->
                                        </div><!-- form-area -->
                                        </section><!-- section -->
                                        <section class="section">
                                        <div class="heading">
                                            <h2>Notes</h2>
                                        </div>
                                        <div class="notes">
                                            <textarea class="form-control" cols="1" rows="1" id="notes_marriage" name="notes_marriage"><?=$person["notes"]["marriage"]?></textarea>
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
                                                        <button id="office_delete_button_<?=$o_i?>" class="btn btn-warning ie-fix" onClick="deleteEntry('office', <?=$o_i?>); return false;"><span><i class="fa fa-times"></i></span></button>
                                                        <input type="hidden" id="office_deleted_<?=$o_i?>" name="office_deleted_<?=$o_i?>" value="NO">
                                                    </div>
                                                    <input type="hidden" name="office_id_<?=$o_i?>" id="office_id_<?=$o_i?>" value="<?=$office["ID"]?>">
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="office_office_id_<?=$o_i?>">Office Name:</label>
                                                            <select data-placeholder="Select Office" class="form-control" id="office_office_id_<?=$o_i?>" name="office_office_id_<?=$o_i?>">
                                                                <option></option>
                                                                <option value="<?=$office["OfficeID"]?>" selected="selected"><?=$office["OfficeName"]?></option>
                                                            </select>
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
                                                            <select data-placeholder="Select Start Date Status" class="form-control" id="office_from_status_<?=$o_i?>" name="office_from_status_<?=$o_i?>">
                                                                <option value="exact" <?php if ($office["FromStatus"] == "exact") echo "selected";?>>Specific Known Date</option>
                                                                <option value="notBefore" <?php if ($office["FromStatus"] == "notBefore") echo "selected";?>>Not Before This Date</option>
                                                                <option value="atLeastBy" <?php if ($office["FromStatus"] == "atLeastBy") echo "selected";?>>At Least By This Date</option>
                                                                <option value="other" <?php if ($office["FromStatus"] == "other") echo "selected";?>>Other (Unusual)</option>
                                                            </select>
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
                                                            <select data-placeholder="Select End Date Status" class="form-control" id="office_to_status_<?=$o_i?>" name="office_to_status_<?=$o_i?>">
                                                                <option value="exact" <?php if ($office["ToStatus"] == "exact") echo "selected";?>>Specific Known Date</option>
                                                                <option value="notAfter" <?php if ($office["ToStatus"] == "notAfter") echo "selected";?>>Not After This Date</option>
                                                                <option value="atLeastUntil" <?php if ($office["ToStatus"] == "atLeastUntil") echo "selected";?>>At Least Until This Date</option>
                                                                <option value="other" <?php if ($office["ToStatus"] == "other") echo "selected";?>>Other (Unusual)</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="office_officiator1_person_id_<?=$o_i?>">Officiator:</label>
                                                            <select data-placeholder="Select Officiator" class="form-control" id="office_officiator1_person_id_<?=$o_i?>" name="office_officiator1_person_id_<?=$o_i?>">
                                                                <option></option>
                                                                <option value="<?=$office["OfficiatorID1"]?>" selected="selected"><?php echo $office["OfficiatorName1"];?></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="office_officiator2_person_id_<?=$o_i?>">Officiator:</label>
                                                            <select data-placeholder="Select Officiator" class="form-control" id="office_officiator2_person_id_<?=$o_i?>" name="office_officiator2_person_id_<?=$o_i?>">
                                                                <option></option>
                                                                <option value="<?=$office["OfficiatorID2"]?>" selected="selected"><?php echo $office["OfficiatorName2"];?></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="office_officiator3_person_id_<?=$o_i?>">Officiator:</label>
                                                            <select data-placeholder="Select Officiator" class="form-control" id="office_officiator3_person_id_<?=$o_i?>" name="office_officiator3_person_id_<?=$o_i?>">
                                                                <option></option>
                                                                <option value="<?=$office["OfficiatorID3"]?>" selected="selected"><?php echo $office["OfficiatorName3"];?></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="office_notes_<?=$o_i?>">Notes:</label>
                                                            <textarea class="notes-field" id="office_notes_<?=$o_i?>" name="office_notes_<?=$o_i?>"><?=$office["PrivateNotes"]?></textarea>
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
                                                <button id="button-add-office" class="btn btn-success btn-save ie-fix"><span>Add New Office</span></button>
                                            </div><!-- row-area -->
                                        </div><!-- form-area -->
                                        </section><!-- section -->
                                    </div><!-- tab-02 -->
                                    </section><!-- tabs -->
                                </fieldset>
<?php
    echo "<input type=\"hidden\" name=\"n_i\" id=\"n_i\" value=\"$n_i\">";
    echo "<input type=\"hidden\" name=\"r_i\" id=\"r_i\" value=\"$r_i\">";
    echo "<input type=\"hidden\" name=\"s_i\" id=\"s_i\" value=\"$s_i\">";
    echo "<input type=\"hidden\" name=\"m_i\" id=\"m_i\" value=\"$m_i\">";
    echo "<input type=\"hidden\" name=\"o_i\" id=\"o_i\" value=\"$o_i\">";
?>
                            </form>
                        </div>
                    </div><!-- main-area -->
                </div><!-- wrapper -->


                <!-- HIDDEN FORM ENTRIES TO COPY -->

                <div style="display:none;">
                    <div id="name-entry-hidden">
                        <div class="row-area" id="name_ZZ">
                            <div class="delete-area">
                                <button id="name_delete_button_ZZ" class="btn btn-warning ie-fix" onClick="deleteEntry('name', 'ZZ'); return false;"><span><i class="fa fa-times"></i></span></button>
                                <input type="hidden" id="name_deleted_ZZ" name="name_deleted_ZZ" value="NO">
                            </div>
                            <input type="hidden" class="form-control" value="NEW" id="name_id_ZZ" name="name_id_ZZ">
                            <input type="hidden" class="form-control" value="alternate" id="name_type_ZZ" name="name_type_ZZ">
                            <div class="frame">
                                <input type="text" class="form-control" value="" id="name_prefix_ZZ" name="name_prefix_ZZ" size="4">
                                <label for="name_prefix_ZZ">Prefix</label>
                            </div>
                            <div class="frame">
                                <input type="text" class="form-control" value="" id="name_first_ZZ" name="name_first_ZZ" size="14">
                                <label for="name_first_ZZ">First</label>
                            </div>
                            <div class="frame">
                                <input type="text" class="form-control" value="" id="name_middle_ZZ" name="name_middle_ZZ" size="10">
                                <label for="name_middle_ZZ">Middle</label>
                            </div>
                            <div class="frame">
                                <input type="text" class="form-control" value="" id="name_last_ZZ" name="name_last_ZZ" size="14">
                                <label for="name_last_ZZ">Last</label>
                            </div>
                            <div class="frame">
                                <input type="text" class="form-control" value="" id="name_suffix_ZZ" name="name_suffix_ZZ" size="4">
                                <label for="name_suffix_ZZ">Suffix</label>
                            </div>
                        </div><!-- row-area -->
                    </div>
                    <div id="rite-entry-hidden">
                        <div class="row-area form-area form-block" id="tr_ZZ">
                            <div class="delete-area">
                                <button id="tr_delete_button_ZZ" class="btn btn-warning ie-fix" onClick="deleteEntry('tr', 'ZZ'); return false;"><span><i class="fa fa-times"></i></span></button>
                                <input type="hidden" id="tr_deleted_ZZ" name="tr_deleted_ZZ" value="NO">
                            </div>
                            <div class="row-area">
                                <input type="hidden" name="tr_id_ZZ" id="tr_id_ZZ" value="NEW">
                                <div class="frame">
                                    <label class="fixed" for="tr_type_ZZ">Type:</label>
                                    <select class="form-selector" data-placeholder="Select Type" id="tr_type_ZZ" name="tr_type_ZZ">
                                        <option value=""></option>
                                        <option value="baptism">Baptism</option>
                                        <option value="endowment">Endowment</option>
                                        <option value="secondAnnointing">Second Anointing</option>
                                        <option value="secondAnnointingTime">Second Anointing (for time)</option>
                                        <option value="firstAnnointing">First Anointing</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row-area">
                                <div class="frame">
                                    <label class="fixed">Date:</label>
                                    <?php displayDate("", "tr_date_", "_ZZ"); ?>
                                </div>
                            </div>
                            <div class="row-area">
                                <div class="frame">
                                    <label class="fixed" for="tr_place_id_ZZ">Place:</label>
                                    <select data-placeholder="Select Place" class="form-control" id="tr_place_id_ZZ" name="tr_place_id_ZZ">
                                        <option></option>
                                    </select>
                                </div>
                            </div>
                            <div class="row-area">
                                <div class="frame">
                                    <label class="fixed" for="tr_officiator_person_id_ZZ">Officiator:</label>
                                    <select data-placeholder="Select Officiator" class="form-control" id="tr_officiator_person_id_ZZ" name="tr_officiator_person_id_ZZ">
                                        <option value=""></option>
                                    </select>
                                </div>
                            </div>
                            <!--
                            <div class="row-area">
                                <div class="frame">
                                    <label class="fixed" for="tr_officiator_role_ZZ">Officiator Role:</label>
                                    <input type="text" class="form-control" value="" id="tr_officiator_role_ZZ" name="tr_officiator_role_ZZ" size="25">
                                </div>
                            </div>
                            -->
                            <div class="row-area">
                                <div class="frame">
                                    <label class="fixed" for="tr_proxy_person_id_ZZ">Proxy:</label>
                                    <select data-placeholder="Select Proxy" class="form-control" id="tr_proxy_person_id_ZZ" name="tr_proxy_person_id_ZZ">
                                        <option></option>
                                    </select>
                                </div>
                            </div>
                            <div class="row-area">
                                <div class="frame">
                                    <label class="fixed" for="tr_annointed_to_person_id_ZZ">Anointed To:</label>
                                    <select data-placeholder="Select Anointed To" class="form-control" id="tr_annointed_to_person_id_ZZ" name="tr_annointed_to_person_id_ZZ">
                                        <option></option>
                                    </select>
                                </div>
                            </div>
                            <div class="row-area">
                                <div class="frame">
                                    <label class="fixed" for="tr_annointed_to_proxy_person_id_ZZ">Anointed To (Proxy):</label>
                                    <select data-placeholder="Select Anointed To (Proxy)" class="form-control" id="tr_annointed_to_proxy_person_id_ZZ" name="tr_annointed_to_proxy_person_id_ZZ">
                                        <option></option>
                                    </select>
                                </div>
                            </div>
                            <div class="row-area">
                                <div class="frame">
                                    <label class="fixed" for="tr_name_id_ZZ">Name as Performed:</label>
                                    <select data-placeholder="Select Name as Performed" class="form-control" id="tr_name_id_ZZ" name="tr_name_id_ZZ">
                                        <option value=""></option>
                                    </select>
                                </div>
                            </div>
                            <div class="row-area">
                                <div class="frame">
                                    <label class="fixed" for="tr_notes_ZZ">Notes:</label>
                                    <textarea class="notes-field" id="tr_notes_ZZ" name="tr_notes_ZZ"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="nonmarital-entry-hidden">
                        <div class="row-area form-area form-block" id="nms_ZZ">
                            <div class="delete-area">
                                <button id="nms_delete_button_ZZ" class="btn btn-warning ie-fix" onClick="deleteEntry('nms', 'ZZ'); return false;"><span><i class="fa fa-times"></i></span></button>
                                <input type="hidden" id="nms_deleted_ZZ" name="nms_deleted_ZZ" value="NO">
                            </div>
                            <div class="row-area">
                                <input type="hidden" name="nms_id_ZZ" id="nms_id_ZZ" value="NEW">
                                <div class="frame">
                                    <label class="fixed" for="nms_type_ZZ">Type:</label>
                                    <select class="form-selector" data-placeholder="Select Type" id="nms_type_ZZ" name="nms_type_ZZ">
                                        <option></option>
                                        <option value="adoption">Adoption</option>
                                        <option value="natural">Natural</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row-area">
                                <div class="frame">
                                    <label class="fixed">Date:</label>
                                    <?php displayDate("", "nms_date_", "_ZZ"); ?>
                                </div>
                            </div>
                            <div class="row-area">
                                <div class="frame">
                                    <label class="fixed" for="nms_place_id_ZZ">Place:</label>
                                    <select data-placeholder="Select Place" class="form-control" id="nms_place_id_ZZ" name="nms_place_id_ZZ">
                                        <option></option>
                                    </select>
                                </div>
                            </div>
                            <div class="row-area">
                                <div class="frame">
                                    <label class="fixed" for="nms_officiator_person_id_ZZ">Officiator:</label>
                                    <select data-placeholder="Select Officiator" class="form-control" id="nms_officiator_person_id_ZZ" name="nms_officiator_person_id_ZZ">
                                        <option></option>
                                    </select>
                                </div>
                            </div>
                            <div class="row-area">
                                <div class="frame">
                                    <label class="fixed" for="nms_proxy_person_id_ZZ">Proxy:</label>
                                    <select data-placeholder="Select Proxy" class="form-control" id="nms_proxy_person_id_ZZ" name="nms_proxy_person_id_ZZ">
                                        <option></option>
                                    </select>
                                </div>
                            </div>
                            <div class="row-area">
                                <div class="frame">
                                    <label class="fixed" for="nms_marriage_id_ZZ">Sealed to Marriage:</label>
                                    <select data-placeholder="Select Sealed to Marriage" class="form-control" id="nms_marriage_id_ZZ" name="nms_marriage_id_ZZ">
                                        <option></option>
                                    </select>
                                </div>
                            </div>
                            <div class="row-area">
                                <div class="frame">
                                    <label class="fixed" for="nms_proxy_father_person_id_ZZ">Proxy Father:</label>
                                    <select data-placeholder="Select Proxy Father" class="form-control" id="nms_proxy_father_person_id_ZZ" name="nms_proxy_father_person_id_ZZ">
                                        <option></option>
                                    </select>
                                </div>
                            </div>
                            <div class="row-area">
                                <div class="frame">
                                    <label class="fixed" for="nms_proxy_mother_person_id_ZZ">Proxy Mother:</label>
                                    <select data-placeholder="Select Proxy Mother" class="form-control" id="nms_proxy_mother_person_id_ZZ" name="nms_proxy_mother_person_id_ZZ">
                                        <option></option>
                                    </select>
                                </div>
                            </div>
                            <div class="row-area">
                                <div class="frame">
                                    <label class="fixed" for="nms_name_id_ZZ">Name as Sealed:</label>
                                    <select data-placeholder="Select Name as Sealed" class="form-control" id="nms_name_id_ZZ" name="nms_name_id_ZZ">
                                        <option value=""></option>
                                    </select>
                                </div>
                            </div>
                            <div class="row-area">
                                <div class="frame">
                                    <label class="fixed" for="nms_notes_ZZ">Notes:</label>
                                    <textarea class="notes-field" id="nms_notes_ZZ" name="nms_notes_ZZ"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="office-entry-hidden">
                                                <div class="row-area form-area form-block" id="office_ZZ">
                                                    <div class="delete-area">
                                                        <button id="office_delete_button_ZZ" class="btn btn-warning ie-fix" onClick="deleteEntry('office', ZZ); return false;"><span><i class="fa fa-times"></i></span></button>
                                                        <input type="hidden" id="office_deleted_ZZ" name="office_deleted_ZZ" value="NO">
                                                    </div>
                                                    <input type="hidden" name="office_id_ZZ" id="office_id_ZZ" value="NEW">
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="office_office_id_ZZ">Office Name:</label>
                                                            <select data-placeholder="Select Office" class="form-control" id="office_office_id_ZZ" name="office_office_id_ZZ">
                                                                <option></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed">Start Date:</label>
                                                            <?php displayDate("", "office_from_", "_ZZ"); ?>
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="office_from_status_ZZ">Start Date Status:</label>
                                                            <select data-placeholder="Select Start Date Status" class="form-control" id="office_from_status_ZZ" name="office_from_status_ZZ">
                                                                <option value="exact" >Specific Known Date</option>
                                                                <option value="notBefore" >Not Before This Date</option>
                                                                <option value="atLeastBy" >At Least By This Date</option>
                                                                <option value="other" >Other (Unusual)</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed">End Date:</label>
                                                            <?php displayDate("", "office_to_", "_ZZ"); ?>
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="office_to_status_ZZ">End Date Status:</label>
                                                            <select data-placeholder="Select End Date Status" class="form-control" id="office_to_status_ZZ" name="office_to_status_ZZ">
                                                                <option value="exact" >Specific Known Date</option>
                                                                <option value="notAfter" >Not After This Date</option>
                                                                <option value="atLeastUntil" >At Least Until This Date</option>
                                                                <option value="other" >Other (Unusual)</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="office_officiator1_person_id_ZZ">Officiator:</label>
                                                            <select data-placeholder="Select Officiator" class="form-control" id="office_officiator1_person_id_ZZ" name="office_officiator1_person_id_ZZ">
                                                                <option></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="office_officiator2_person_id_ZZ">Officiator:</label>
                                                            <select data-placeholder="Select Officiator" class="form-control" id="office_officiator2_person_id_ZZ" name="office_officiator2_person_id_ZZ">
                                                                <option></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="office_officiator3_person_id_ZZ">Officiator:</label>
                                                            <select data-placeholder="Select Officiator" class="form-control" id="office_officiator3_person_id_ZZ" name="office_officiator3_person_id_ZZ">
                                                                <option></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="office_notes_ZZ">Notes:</label>
                                                            <textarea class="notes-field" id="office_notes_ZZ" name="office_notes_ZZ"></textarea>
                                                        </div>
                                                    </div>
                                                </div>
                    </div>
                    <div id="marriage-entry-hidden">
                                                <div class="row-area form-area form-block" id="mar_ZZ">
                                                    <div class="delete-area">
                                                        <button id="mar_delete_button_ZZ" class="btn btn-warning ie-fix" onClick="deleteEntry('mar', 'ZZ'); return false;"><span><i class="fa fa-times"></i></span></button>
                                                        <input type="hidden" id="mar_deleted_ZZ" name="mar_deleted_ZZ" value="NO">
                                                    </div>
                                                    <div class="row-area">
                                                        <input type="hidden" name="mar_id_ZZ" id="mar_id_ZZ" value="NEW">
                                                        <div class="frame">
                                                            <label class="fixed" for="mar_type_ZZ">Type:</label>
                                                            <select class="form-selector" data-placeholder="Select Type" id="mar_type_ZZ" name="mar_type_ZZ">
                                                                <option></option>
                                                                <option value="eternity">Sealed for Eternity</option>
                                                                <option value="time">Sealed for Time</option>
                                                                <option value="civil">Civil Marriage</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="mar_spouse_person_id_ZZ">Spouse:</label>
                                                            <select data-placeholder="Select Spouse" class="form-control" id="mar_spouse_person_id_ZZ" name="mar_spouse_person_id_ZZ">
                                                                <option></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed">Date:</label>
                                                            <?php displayDate("", "mar_date_", "_ZZ"); ?>
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed">Divorce Date:</label>
                                                            <?php displayDate("", "mar_div_", "_ZZ"); ?>
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed">Cancelled Date:</label>
                                                            <?php displayDate("", "mar_cancel_", "_ZZ"); ?>
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="mar_place_id_ZZ">Place:</label>
                                                            <select data-placeholder="Select Place" class="form-control" id="mar_place_id_ZZ" name="mar_place_id_ZZ">
                                                                <option></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="mar_officiator_person_id_ZZ">Officiator:</label>
                                                            <select data-placeholder="Select Officiator" class="form-control" id="mar_officiator_person_id_ZZ" name="mar_officiator_person_id_ZZ">
                                                                <option></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="mar_proxy_person_id_ZZ">Proxy:</label>
                                                            <select data-placeholder="Select Proxy" class="form-control" id="mar_proxy_person_id_ZZ" name="mar_proxy_person_id_ZZ">
                                                                <option></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="mar_spouse_proxy_person_id_ZZ">Spouse Proxy:</label>
                                                            <select data-placeholder="Select Spouse Proxy" class="form-control" id="mar_spouse_proxy_person_id_ZZ" name="mar_spouse_proxy_person_id_ZZ">
                                                                <option></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="mar_name_id_ZZ">Name as Sealed:</label>
                                                            <select data-placeholder="Select Name as Sealed" class="form-control" id="mar_name_id_ZZ" name="mar_name_id_ZZ">
                                                                <option value=""></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="row-area">
                                                        <div class="frame">
                                                            <label class="fixed" for="mar_notes_ZZ">Notes:</label>
                                                            <textarea class="notes-field" id="mar_notes_ZZ" name="mar_notes_ZZ"></textarea>
                                                        </div>
                                                    </div>
                                                </div>
                    </div>

                </div>
                <!--<script type="text/javascript">
                    customForm.customForms.replaceAll();
                    </script>-->
                </body>
            </html>
