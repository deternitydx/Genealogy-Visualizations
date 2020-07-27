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

    function display($kind, $val, $label, $isdate=false) {
        if (isset($kind[$val]) && !empty(trim($kind[$val]))) {
            echo "<p class='card-text'>$label: ";
            if ($isdate)
                displayDate($kind[$val], "", "");
            else
                echo $kind[$val];
            echo "</p>\n";
        }
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

    <style>
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

    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item"><a href="data_view/people.php">All People</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?=$fullname?></li>
      </ol>
    </nav>

    <div class="row mb-3">
        <div class="col-md-12 themed-grid-col">
            <div class="card text-center">
              <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs">
                  <li class="nav-item" role="presentation">
                    <a class="nav-link active" id="home-tab" data-toggle="tab" href="#home" role="tab" aria-controls="home" aria-selected="true">Personal Information</a>
                  </li>
                  <li class="nav-item" role="presentation">
                    <a class="nav-link" id="nso-tab" data-toggle="tab" href="#nso" role="tab" aria-controls="profile" aria-selected="false">Non-Sealing Ordinances (NSO)</a>
                  </li>
                  <li class="nav-item" role="presentation">
                    <a class="nav-link" id="sc-tab" data-toggle="tab" href="#sc" role="tab" aria-controls="profile" aria-selected="false">Sealed Child (SC)</a>
                  </li>
                  <li class="nav-item" role="presentation">
                    <a class="nav-link" id="m-tab" data-toggle="tab" href="#m" role="tab" aria-controls="profile" aria-selected="false">Marriages (M)</a>
                  </li>
                  <li class="nav-item" role="presentation">
                    <a class="nav-link" id="c-tab" data-toggle="tab" href="#c" role="tab" aria-controls="profile" aria-selected="false">Children (C)</a>
                  </li>
                  <li class="nav-item" role="presentation">
                    <a class="nav-link" id="o-tab" data-toggle="tab" href="#o" role="tab" aria-controls="profile" aria-selected="false">Offices (O)</a>
                  </li>
                </ul>
              </div>
              <div class="card-body text-left">
                <div class="tab-content" id="myTabContent">
                  <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
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
                    <div class="card">
                      <div class="card-header">
                        Birth Information
                      </div>
                      <div class="card-body">
                        <p class="card-text">Gender: <?=$person["information"]["Gender"]?></p>
                        <p class="card-text">Birth Date: <?php displayDate($person["information"]["BirthDate"], "birth", ""); ?></p>
                        <p class="card-text">Birth Place: <?=$person["information"]["BirthPlaceName"]?></p>
                        <p class="card-text">Birth Parent Marriage: <?=$person["information"]["ParentMarriageString"]?></p>
                      </div>
                    </div>
                    <div class="card">
                      <div class="card-header">
                        Death Information
                      </div>
                      <div class="card-body">
                        <p class="card-text">Death Date: <?php displayDate($person["information"]["DeathDate"], "death", ""); ?></p>
                        <p class="card-text">Death Place: <?=$person["information"]["DeathPlaceName"]?></p>
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
                  </div>
                  <div class="tab-pane fade" id="nso" role="tabpanel" aria-labelledby="nso-tab">
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
                            <div class="card">
                              <div class="card-header">
                                        <?php if ($rite["Type"] == "baptism")  echo "Baptism";?>
                                        <?php if ($rite["Type"] == "endowment")  echo "Endowment";?>
                                        <?php if ($rite["Type"] == "secondAnnointing")  echo "Second Anointing";?>
                                        <?php if ($rite["Type"] == "secondAnnointingTime")  echo "Second Anointing (for time)";?>
                                        <?php if ($rite["Type"] == "firstAnnointing")  echo "First Anointing";?>
                              </div>
                              <div class="card-body">
                                <p class="card-text">Date: <?php displayDate($rite["Date"], "tr_date_", "_".$r_i); ?></p>
                                <p class="card-text">Place: <?=$rite["PlaceName"]?></p>
                                <p class="card-text">Officiator: <?=$rite["OfficiatorName"]?></p>
                                <p class="card-text">Proxy: <?=$rite["ProxyName"]?></p>
                                <p class="card-text">Anointed To: <?=$rite["AnnointedToName"]?></p>
                                <p class="card-text">Anointed To (Proxy): <?=$rite["AnnointedToProxyName"]?></p>
                                <p class="card-text">Name as Performed: <?=$rite["NameUsed"]?></p>
                                <p class="card-text">Notes: <?=$rite["PrivateNotes"]?></p>
                              </div>
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
                              </div>
                              <div class="card-body">
                                <p class="card-text">Date: <?php displayDate($sealing["Date"], "nms_date_", "_".$r_i); ?></p>
                                <p class="card-text">Place: <?=$sealing["PlaceName"]?></p>
                                <p class="card-text">Proxy: <?=$sealing["ProxyName"]?></p>
                                <p class="card-text">Officiator: <?=$sealing["OfficiatorName"]?></p>
                                <p class="card-text">Sealed to Marriage: <?=$sealing["MarriageString"]?></p>
                                <p class="card-text">Proxy Father: <?=$sealing["ProxyFatherName"]?></p>
                                <p class="card-text">Proxy Mother: <?=$sealing["ProxyMotherName"]?></p>
                                <p class="card-text">Name as Sealed: <?=$sealing["NameUsed"]?></p>
                                <p class="card-text">Notes: <?=$sealing["PrivateNotes"]?></p>
                              </div>
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
                  <div class="tab-pane fade" id="m" role="tabpanel" aria-labelledby="m-tab">
                        <?php
                            $m_i = 1;
                        if ($person["marriages"] != null && $person["marriages"] != false) {
                            foreach ($person["marriages"] as $marriage) {
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
                              </div>
                              <div class="card-body">
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
                                <?php if ($marriage["PlaceName"]) { ?>
                                <p class="card-text">Place: <?=$marriage["PlaceName"]?></p>
                                <?php } ?>
                                <?php if ($marriage["OfficiatorName"]) { ?>
                                <p class="card-text">Officiator: <?=$marriage["OfficiatorName"]?></p>
                                <?php } ?>
                                <?php if (!empty(trim($marriage["ProxyName"]))) { ?>
                                <p class="card-text">Proxy: <?=$marriage["ProxyName"]?></p>
                                <?php } ?>
                                <?php if (!empty(trim($marriage["SpouseProxyName"]))) { ?>
                                <p class="card-text">Spouse Proxy: <?=$marriage["SpouseProxyName"]?></p>
                                <?php } ?>
                                <?php if (!empty(trim($marriage["NameUsed"]))) { ?>
                                <p class="card-text">Name as Sealed: <?=$marriage["NameUsed"]?></p>
                                <?php } ?>
                                <?php if ($marriage["PrivateNotes"]) { ?>
                                <p class="card-text">Notes: <?=$marriage["PrivateNotes"]?></p>
                                <?php } ?>
                              </div>
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
                    Coming Soon!
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
                                        <?php if ($office["FromStatus"] == "exact")  echo "<p class='card-text datetype'>Specific Known Date</p>";?>
                                        <?php if ($office["FromStatus"] == "notBefore")  echo "<p class='card-text datetype'>Not Before This Date</p>";?>
                                        <?php if ($office["FromStatus"] == "atLeastBy")  echo "<p class='card-text datetype'>At Least By This Date</p>";?>
                                        <?php if ($office["FromStatus"] == "other")  echo "<p class='card-text datetype'>Other (Unusual)</p>";?>
                                <?php display($office, "To", "End Date", true); ?>
                                        <?php if ($office["ToStatus"] == "exact")  echo "<p class='card-text datetype'>Specific Known Date</p>";?>
                                        <?php if ($office["ToStatus"] == "notBefore")  echo "<p class='card-text datetype'>Not Before This Date</p>";?>
                                        <?php if ($office["ToStatus"] == "atLeastBy")  echo "<p class='card-text datetype'>At Least By This Date</p>";?>
                                        <?php if ($office["ToStatus"] == "other")  echo "<p class='card-text datetype'>Other (Unusual)</p>";?>
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
