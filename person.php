<?php
date_default_timezone_set('America/New_York');
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

    function display($kind, $val, $label, $isdate=false, $ismarriage=false) {
        if (isset($kind[$val])) {
            $trimmed = trim($kind[$val]);
            if (!empty($trimmed)) {
                echo "<p class='card-text'><span style='width: 150px; font-weight: bold;'>$label: </span> ";
                if ($isdate)
                    displayDate($kind[$val], "", "");
                else {
                    $parts = explode(' ', $kind[$val]);
                    $last = array_pop($parts);
                    if (!$ismarriage && is_numeric($last)) 
                        echo "<a href='?id=$last'>".implode(" ", $parts)."</a>";
                    else
                        echo $kind[$val];
                }
                echo "</p>\n";
            }
        }
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

?>
<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">

    <link rel="stylesheet" href="css/font-awesome.min.css">
    <style>
        body {
            overflow-y: scroll;
        }
        h1 {
            margin-top: 30px;
            margin-bottom: 20px;
        }
        .card {
            margin-bottom: 20px;
        }
        .marriage-type {
            font-style: italic;
        }
        .marinfo {
            text-align: center;
        }
        .datetype {
            margin-top: 0px;
            padding-top: 0px;
            margin-left: 20px;
        }
        #upper-container{
          display: grid;
          grid-template-columns: 95% 5%;
          vertical-align: center;
        }
        #bc{
          grid-column: 1;
        }
        #edit-icon{
          font-weight: bold;
          display:block;
          margin-bottom: 5px;
        }
        #name-header{
          margin-bottom: 0;
        }
        .marriage-divider{
          background-color: black;
          border-radius: 5px;
          text-align:center;
          font-weight: bold;
          color: white;
          margin-bottom: 20px;
        }
    </style>
    <title>View Person</title>
    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>
  </head>
  <body>
    <div class="container">
    <?php
    $n_i = 1;
    $fullname = "";
        foreach ($person["names"] as $name) {
            if ($name["Type"] == 'authoritative') {
                $fullname = "{$name["Prefix"]} {$name["First"]} {$name["Middle"]} {$name["Last"]} {$name["Suffix"]}";
                echo "<h1 id='name-header'>
                    <span>{$name["Prefix"]}</span>
                    <span>{$name["First"]}</span>
                    <span>{$name["Middle"]}</span>
                    <span>{$name["Last"]}</span>
                    <span>{$name["Suffix"]}</span>";
                $db_link = "http://nauvoo.iath.virginia.edu/viz/data_entry/individual.php?id=" . $_GET["id"];
                if ($person["information"]["Gender"] == "Male")
                    echo "<i class='fa fa-male' aria-hidden='true'></i>";
                else
                    echo "<i class='fa fa-female' aria-hidden='true'></i>";
                echo "</h1>";
                $n_i++;
            } // endif
        } // end foreach
    ?>
    
    <!-- <a id="edit-icon" href='./data_entry/individual.php?id=<?=$_GET["id"]?>'><i class='fa fa-pencil' aria-hidden='true'></i>  Edit</a> -->
    
    <nav aria-label="breadcrumb" id="bc">
    <div id="upper-container">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item"><a href="data_view/people.php">All People</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?=$fullname?></li>
      </ol>
      </div>
      
    </nav>
    
    

    <div class="row mb-3">
        <div class="col-md-12 themed-grid-col">
            <div class="card text-center">
              <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs">
                  <li class="nav-item" role="presentation">
                    <a class="nav-link active" id="home-tab" data-toggle="tab" href="#home" role="tab" aria-controls="home" aria-selected="true">Personal Info</a>
                  </li>
                  <li class="nav-item" role="presentation">
                    <a class="nav-link" id="nso-tab" data-toggle="tab" href="#nso" role="tab" aria-controls="profile" aria-selected="false">Non-Sealing Ordinances (NSO)</a>
                  </li>
                  <li class="nav-item" role="presentation">
                    <a class="nav-link" id="m-tab" data-toggle="tab" href="#m" role="tab" aria-controls="profile" aria-selected="false">Marriages (M)</a>
                  </li>
                  <li class="nav-item" role="presentation">
                    <a class="nav-link" id="c-tab" data-toggle="tab" href="#c" role="tab" aria-controls="profile" aria-selected="false">Children (C)</a>
                  </li>
                  <li class="nav-item" role="presentation">
                    <a class="nav-link" id="a-tab" data-toggle="tab" href="#a" role="tab" aria-controls="profile" aria-selected="false">Adoptions (A)</a>
                  </li>
                  <li class="nav-item" role="presentation">
                    <a class="nav-link" id="o-tab" data-toggle="tab" href="#o" role="tab" aria-controls="profile" aria-selected="false">Offices (O)</a>
                  </li>
                  <li class="nav-item" role="presentation">
                    <a class="nav-link" id="sc-tab" data-toggle="tab" href="#sc" role="tab" aria-controls="profile" aria-selected="false">Sealings as Child (SC)</a>
                  </li>
                </ul>
              </div>
              <div class="card-body text-left">
                <div class="tab-content" id="myTabContent">
                  <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="card text-center">
                              <div class="card-header">
                                Birth
                              </div>
                              <div class="card-body">
                                <h5 class="card-title"><?php displayDate($person["information"]["BirthDate"], "birth", ""); ?></h5>
                                <h6 class="card-subtitle"><?=$person["information"]["BirthPlaceName"]?></h6>
                              </div>
                            </div>
                        </div>
                        <div class="col-md-2 text-center align-middle">
                            <p class="align-middle" style="margin-top: 30px;">&mdash;</p>
                        </div>
                        <div class="col-md-5">
                            <div class="card text-center">
                              <div class="card-header">
                                Death
                              </div>
                              <div class="card-body">
                                <h5 class="card-title"><?php displayDate($person["information"]["DeathDate"], "birth", ""); ?></h5>
                                <h6 class="card-subtitle"><?=$person["information"]["DeathPlaceName"]?></h6>
                              </div>
                            </div>
                        </div>
                    </div>
                    <?php
                    $hasAltName = false;
                    if(count($person["names"]) > 1) $hasAltName = true;
                    if($hasAltName){
                    ?>
                    <div class="card">
                      <div class="card-header">
                        Alternative Names (Also Known As) 
                      </div>
                      <div class="card-body">
                        <?php
                            foreach ($person["names"] as $name) {
                                if ($name["Type"] == 'alternate') {
                                    echo "
                                            <p class='card-text'>
                                                    <span>{$name["Prefix"]}</span>
                                                    <span>{$name["First"]}</span>
                                                    <span>{$name["Middle"]}</span>
                                                    <span>{$name["Last"]}</span>
                                                    <span>{$name["Suffix"]}</span>
                                            </p>
                                        ";
                                    $n_i++;
                                } // endif
                            } // end foreach
                        ?>
                      </div>
                    </div>
                          <?php }?>
                    <div class="card">
                      <div class="card-header">
                        Parent Marriage 
                      </div>
                      <div class="card-body text-center">
                        <!-- <p class="card-text"><?=$person["information"]["ParentMarriageString"]?></p> -->
                        <?php if(array_key_exists("MotherName", $person["information"]) && array_key_exists("MotherName", $person["information"])){?>
                        <p class="card-text"><a href='person.php?id=<?=$person["information"]["FatherID"]?>'><?=$person["information"]["FatherName"]?></a> to <a href='person.php?id=<?=$person["information"]["MotherID"]?>'><?=$person["information"]["MotherName"]?></a></p>
                        <?php display($person["information"], "ParentMarriageDate", "Date", true)?>
                        <?php 
                        if($person["information"]["ParentMarriageType"] == "byu"){
                          $person["information"]["ParentMarriageType"] = "unknown";
                        }
                        display($person["information"], "ParentMarriageType", "Marriage Type")
                        ?>
                        <?php }?>
                      </div>
                    </div>
                    <div class="card">
                      <div class="card-header">
                        Notes 
                      </div>
                      <div class="card-body">
                        <p class="card-text"><?=$person["notes"]["personal"]?></p>
                      </div>
                    </div>
                    <div class="card">
                      <div class="card-header">
                        Visualizations
                      </div>
                      <div class="card-body">
                        <p class="card-text"><a href='http://nauvoo.iath.virginia.edu/viz/chord.html?id=<?=$_GET["id"]?>'>Marriage Chord Diagram</a><br>
                        <a href="http://nauvoo.iath.virginia.edu/viz/marriageflow.html?id=<?=$_GET["id"]?>&levels=1">Lineage Flow Network</a>
                        </p>
                      </div>
                    </div>
                    <?php echo "<a href='$db_link' target= '_blank' ><i class='fas fa-edit' aria-hidden='true'></i></a>" ; ?>
                  </div>
                  <div class="tab-pane fade" id="nso" role="tabpanel" aria-labelledby="nso-tab">
                        <?php
                            $r_i = 1;
                        if ($person["temple_rites"] != null && $person["temple_rites"] != false) {
                            foreach ($person["temple_rites"] as $rite) {
                                
                                // Kathleen asked that second anointings not show up in the public view
                                // so if this rite is one of those, then ignore it
                                if ($rite["Type"] == "secondAnnointing" || $rite["Type"] == "secondAnnointingTime")
                                    continue;

                                if ($rite["ProxyID"] == null)
                                    $rite["ProxyName"] = "";
                                if ($rite["AnnointedToID"] == null)
                                    $rite["AnnointedToName"] = "";
                                if ($rite["AnnointedToProxyID"] == null)
                                    $rite["AnnointedToProxyName"] = "";
                        ?>
                            <div class="card">
                              <div class="card-header">
                                        <?php if ($rite["Type"] == "baptism")  echo "Baptism";?>
                                        <?php if ($rite["Type"] == "endowment")  echo "Endowment";?>
                                        <?php if ($rite["Type"] == "secondAnnointing")  echo "Second Anointing";?>
                                        <?php if ($rite["Type"] == "secondAnnointingTime")  echo "Second Anointing (for time)";?>
                                        <?php if ($rite["Type"] == "firstAnnointing")  echo "First Anointing";?>
                                        - <?php displayDate($rite["Date"], "tr_date_", "_".$r_i); ?>
                              </div>
                              <div class="card-body">
                                <?php display($rite, "PlaceName", "Place"); ?>
                                <?php display($rite, "OfficiatorName", "Officiator"); ?>
                                <?php display($rite, "ProxyName", "Proxy"); ?>
                                <?php display($rite, "AnnointedToName", "Anoinged To"); ?>
                                <?php display($rite, "AnnointedToProxyName", "Anointed To (Proxy)"); ?>
                                <?php display($rite, "NameUsed", "Name as Performed"); ?>
                              </div>
                                <?php
                                    if (isset($rite["PrivateNotes"])) {
                                        $trimmed = trim($rite["PrivateNotes"]);
                                        if (!empty($trimmed)) {
                                            echo "<div class=\"card-footer text-muted\">$trimmed</div>";
                                        }
                                    }
                                ?>
                            </div>
                        <?php
                            $r_i++;
                            } // Temple Rites for loop
                        }
                        ?>
                    <div class="card">
                      <div class="card-header">
                        Notes 
                      </div>
                      <div class="card-body">
                        <p class="card-text"><?=$person["notes"]["rites"]?></p>
                      </div>
                    </div>
                  </div>
                  <div class="tab-pane fade" id="m" role="tabpanel" aria-labelledby="m-tab">
                        <?php
                            $m_i = 1;
                            $reachedPosthumous = false;
                        if ($person["marriages"] != null && $person["marriages"] != false) {
                            foreach ($person["marriages"] as $marriage) {
                              if(cmpDates($marriage["MarriageDate"], $person["information"]["DeathDate"]) > 0 && !$reachedPosthumous){
                                $reachedPosthumous = true;
                                echo "<div class='marriage-divider'>".$fullname." dies, ";
                                displayDate($person["information"]["DeathDate"], "", "");
                                echo "</div>";
                              }
                                $spouse_name = substr($marriage["SpouseName"],0,strrpos($marriage["SpouseName"], " "));
                                $spouse_id = substr($marriage["SpouseName"],strrpos($marriage["SpouseName"], " ")+1);
                        ?>
                            <div class="card">
                              <div class="card-header">
                              <a href='person.php?id=<?=$spouse_id?>'><?=$spouse_name?></a> - 
                                <span class="marriage-type">
                                        <?php if ($marriage["Type"] == "eternity")  echo "Sealed for Eternity";?>
                                        <?php if ($marriage["Type"] == "time")  echo "Sealed for Time";?>
                                        <?php if ($marriage["Type"] == "civil")  echo "Civil Marriage";?>
                                </span> - 
                                <?php displayDate($marriage["MarriageDate"], "mar_date_", "_".$m_i); ?>
                                <span class="pull-right"><button class="btn btn-sm btn-primary" data-toggle="collapse" data-target="#marriage<?=$m_i?>" aria-expanded="false" aria-controls="marriage<?=$m_i?>">More</button></span>
                              </div>
                              <div class="card-body collapse" id="marriage<?=$m_i?>">
                                <?php
                                    if (is_numeric($marriage["children"]) && $marriage["children"] > 0)
                                        echo '<p class="card-text marinfo">This marriage has '.$marriage["children"]. ' child(ren) in the database</p>';
                                    if (is_numeric($marriage["adoptees"]) && $marriage["adoptees"] > 0)
                                        echo '<p class="card-text marinfo">This marriage has '.$marriage["adoptees"]. ' adoptee(s) in the database</p>';
                                ?>
                                <?php if ($marriage["DivorceDate"]) { ?>
                                <p class="card-text">Divorce Date: <?php displayDate($marriage["DivorceDate"], "mar_date_", "_".$m_i); ?></p>
                                <?php } ?>
                                <?php if ($marriage["CancelledDate"]) { ?>
                                <p class="card-text">Canceled Date: <?php displayDate($marriage["CancelledDate"], "mar_date_", "_".$m_i); ?></p>
                                <?php } ?>
                                <?php display($marriage, "DivorceDate", "Divorce Date, true"); ?>
                                <?php display($marriage, "CancelledDate", "Canceled Date, true"); ?>
                                <?php display($marriage, "PlaceName", "Place"); ?>
                                <?php display($marriage, "OfficiatorName", "Officiator"); ?>
                                <?php display($marriage, "ProxyName", "Proxy"); ?>
                                <?php display($marriage, "SpouseProxyName", "Spouse Proxy"); ?>
                                <?php display($marriage, "NameUsed", "Name as Sealed"); ?>
                              </div>
                                <?php
                                    if (isset($marriage["PrivateNotes"])) {
                                        $trimmed = trim($marriage["PrivateNotes"]);
                                        if (!empty($trimmed)) {
                                            echo "<div class=\"card-footer text-muted\">$trimmed</div>";
                                        }
                                    }
                                ?>
                            </div>
                        <?php
                                $m_i++;
                            } // foreach marriage
                        }
                        ?>
                    <div class="card">
                      <div class="card-header">
                        Notes 
                      </div>
                      <div class="card-body">
                        <p class="card-text"><?=$person["notes"]["marriage"]?></p>
                      </div>
                    </div>
                  </div>
                  <div class="tab-pane fade" id="c" role="tabpanel" aria-labelledby="c-tab">
                    <?php
                          $c_i = 1;
                          if ($person["natural_children"] != null && $person["natural_children"] != false) {
                              foreach ($person["natural_children"] as $natchild) {
                                $child_name = $natchild["First"]." ".$natchild["Middle"]." ".$natchild["Last"];
                                $child_id = $natchild["ID"];
                          ?>
                              <div class="card">
                                <div class="card-header">
                                  <a href='person.php?id=<?=$child_id?>'><?=$child_name?></a>
                                </div>
                                <div class="card-body">
                                  <?php display($natchild, "BirthDate", "Birth Date", true); ?>
                                  <?php display($natchild, "DeathDate", "Death Date", true); ?>
                                </div>
                              </div>
                          <?php
                              $c_i++;
                              } // Natural children for loop
                          }
                          ?>
                  </div>
                  <div class="tab-pane fade" id="a" role="tabpanel" aria-labelledby="a-tab">
                    <?php
                          $ac_i = 1;
                          if ($person["adopted_children"] != null && $person["adopted_children"] != false) {
                              foreach ($person["adopted_children"] as $adchild) {
                                $child_name = $adchild["First"]." ".$adchild["Middle"]." ".$adchild["Last"];
                                $child_id = $adchild["ID"];
                          ?>
                              <div class="card">
                                <div class="card-header">
                                  <a href='person.php?id=<?=$child_id?>'><?=$child_name?></a>
                                </div>
                                <div class="card-body">
                                  <?php display($adchild, "BirthDate", "Birth Date", true); ?>
                                  <?php display($adchild, "DeathDate", "Death Date", true); ?>
                                  <?php display($adchild, "AdoptionType", "Type"); ?>
                                </div>
                              </div>
                          <?php
                              $ac_i++;
                              } // Adopted children for loop
                          }
                          ?>
                  </div>
                  <div class="tab-pane fade" id="o" role="tabpanel" aria-labelledby="o-tab">
                        <?php
                        $o_i = 1;
                        if ($person["offices"] != null && $person["offices"] != false) {
                            foreach ($person["offices"] as $office) {
                        ?>
                            <div class="card">
                              <div class="card-header">
                                    <?=$office["OfficeName"]?>
                              </div>
                              <div class="card-body">
                                <?php display($office, "From", "Start Date", true); ?>
                                    <?php if($office["From"] != null) { ?>
                                        <?php if ($office["FromStatus"] == "exact")  echo "<p class='card-text datetype'>Specific Known Date</p>";?>
                                        <?php if ($office["FromStatus"] == "notBefore")  echo "<p class='card-text datetype'>Not Before This Date</p>";?>
                                        <?php if ($office["FromStatus"] == "atLeastBy")  echo "<p class='card-text datetype'>At Least By This Date</p>";?>
                                        <?php if ($office["FromStatus"] == "other")  echo "<p class='card-text datetype'>Other (Unusual)</p>";?>
                                    <?php } ?>
                                <?php display($office, "To", "End Date", true); ?>
                                    <?php if($office["To"] != null) { ?>
                                        <?php if ($office["ToStatus"] == "exact")  echo "<p class='card-text datetype'>Specific Known Date</p>";?>
                                        <?php if ($office["ToStatus"] == "notBefore")  echo "<p class='card-text datetype'>Not Before This Date</p>";?>
                                        <?php if ($office["ToStatus"] == "atLeastBy")  echo "<p class='card-text datetype'>At Least By This Date</p>";?>
                                        <?php if ($office["ToStatus"] == "other")  echo "<p class='card-text datetype'>Other (Unusual)</p>";?>
                                    <?php } ?>
                                <?php display($office, "OfficiatorName1", "Officiator"); ?>
                                <?php display($office, "OfficiatorName2", "Officiator"); ?>
                                <?php display($office, "OfficiatorName3", "Officiator"); ?>
                                <?php display($office, "PrivateNotes", "Notes"); ?>
                              </div>
                            </div>
                        <?php
                            $o_i++;
                            } // Offices for loop
                        }
                        ?>
                        
                  </div>
                  <div class="tab-pane fade" id="sc" role="tabpanel" aria-labelledby="sc-tab">
                        <?php
                            $s_i = 1;
                        if ($person["non_marital_sealings"] != null && $person["non_marital_sealings"] != false) {
                            foreach ($person["non_marital_sealings"] as $sealing) {

                                if ($sealing["AdopteeProxyID"] == null)
                                    $sealing["ProxyName"] = "";
                                if ($sealing["MarriageProxyID"] == null)
                                    $sealing["ProxyMarriageString"] = "";
                        ?>
                            <div class="card">
                              <div class="card-header">
                                    <?php if ($sealing["Type"] == "adoption")  echo "Adoption";?>
                                    <?php if ($sealing["Type"] == "natural")  echo "Natural";?>
                                    - <?php displayDate($sealing["Date"], "nms_date_", "_".$r_i); ?>
                              </div>
                              <div class="card-body">
                                <?php display($sealing, "PlaceName", "Place"); ?>
                                <?php display($sealing, "ProxyName", "Proxy"); ?>
                                <?php display($sealing, "OfficiatorName", "Officiator"); ?>
                                <?php display($sealing, "MarriageString", "Sealed to Marriage"); ?>
                                <?php display($sealing, "ProxyFatherName", "Proxy Father"); ?>
                                <?php display($sealing, "ProxyMotherName", "Proxy Mother"); ?>
                                <?php display($sealing, "NameUsed", "Name as Sealed"); ?>
                              </div>
                                <?php
                                    if (isset($sealing["PrivateNotes"])) {
                                        $trimmed = trim($sealing["PrivateNotes"]);
                                        if (!empty($trimmed)) {
                                            echo "<div class=\"card-footer text-muted\">$trimmed</div>";
                                        }
                                    }
                                ?>
                            </div>
                        <?php
                            $s_i++;
                            } // Non marital Sealing for loop
                        }
                        ?>
                    <div class="card">
                      <div class="card-header">
                        Notes 
                      </div>
                      <div class="card-body">
                        <p class="card-text"><?=$person["notes"]["nms"]?></p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>


        </div>
<!--
        <div class="col-md-2 themed-grid-col">
            <div class="card">
              <div class="card-header">
                Visualizations
              </div>
                  <div class="list-group list-group-flush">
                    <a class="list-group-item">Family Unit</a>
                    <a class="list-group-item">Temporal Family Unit</a>
                    <a class="list-group-item">Lineage Flow</a>
                    <a class="list-group-item">Temporal Lineage Flow</a>
                  </div>
            </div>
      </div>
-->
      </div>
  </body>
</html>
