<?php
    include_once("common_functions.php");

    setup_db();
    
    $sealings = array();
    $marriages = array();
    $rites = array();
    $personal = array();
    $names = array();
    $offices = array();
    $updates = array();

    $errors = array();

    // Break apart the POST values into their respective parts
    foreach ($_POST as $key => $val) {
        $pieces = explode("_", $key);
        $i = end($pieces);
        switch ($pieces[0]) {
            case "name":
                if (!isset($names[$i]))
                    $names[$i] = array();
                unset($pieces[0]);
                unset($pieces[count($pieces)]);
                $names[$i][implode("_", $pieces)] = $val;
                break;
            case "mar":
                if (!isset($marriages[$i]))
                    $marriages[$i] = array();
                unset($pieces[0]);
                unset($pieces[count($pieces)]);
                $marriages[$i][implode("_", $pieces)] = $val;
                break;
            case "tr":
                if (!isset($rites[$i]))
                    $rites[$i] = array();
                unset($pieces[0]);
                unset($pieces[count($pieces)]);
                $rites[$i][implode("_", $pieces)] = $val;
                break;
            case "office":
                if (!isset($offices[$i]))
                    $offices[$i] = array();
                unset($pieces[0]);
                unset($pieces[count($pieces)]);
                $offices[$i][implode("_", $pieces)] = $val;
                break;
            case "nms":
                if (!isset($sealings[$i]))
                    $sealings[$i] = array();
                unset($pieces[0]);
                unset($pieces[count($pieces)]);
                $sealings[$i][implode("_", $pieces)] = $val;
                break;
            default:
                $personal[$key] = $val;
        }
    }
    
    // Logging
    //
    $output = fopen("submissions.txt", "a+");
    fwrite($output, "\n");
    fwrite($output, "-- " . date(DATE_RFC2822, time()) . "\n");
    fwrite($output, "-- ---------------------------------------\n");

    // Some constants (marital roles)
    $mrole = "Wife";
    $srole = "Husband";
    if (isset($personal["gender"]) && $personal["gender"] == "Male") {
        $mrole = "Husband";
        $srole = "Wife";
    }


    // Handle creating a new person, if needed
    if ($personal["ID"] == "NEW") {
        $personal["ID"] = insert("Person", array("PrivateNotes" => "Created by Edit Page"));
        $updates["UVAPersonID"] = $personal["ID"];
    }


    // Handle each part of the submit to insert into the database
    foreach ($marriages as $index => $marriage) {
        $vals = array();
        /**
            [1] => Array
                (
                    [id] => 353
                    [type] => civil
                    [spouse_person_id] => 617
                    [date_month] => 03
                    [date_day] => 07
                    [date_year] => 1841
                    [div_month] => MM
                    [div_day] => DD
                    [div_year] => YYYY
                    [cancel_month] => MM
                    [cancel_day] => DD
                    [cancel_year] => YYYY
                    [place_id] => 73
                    [officiator_person_id] => 
                    [proxy_person_id] => 
                    [spouse_proxy_person_id] => 
                    [name_id] => 
                    [notes] => 
                )
                **/

        if (isset($marriage["deleted"]) && $marriage["deleted"] == "YES") {
            // Do the delete if it's not NEW
            if ($marriage["id"] != "NEW") {
                // Delete
                dbdelete("PersonMarriage", "\"MarriageID\" = " . $marriage["id"]);
                dbdelete("Marriage", "\"ID\" = " . $marriage["id"]);
            }
        } else {
            // Insert or Update

            // Marriage table first
            if (isset($marriage["type"]))
                $vals["Type"] = $marriage["type"];
            else
                $vals["Type"] = "unknown";
            if (isset($marriage["place_id"]))
                $vals["PlaceID"] = $marriage["place_id"];
            if (isset($marriage["date_year"]) && isset($marriage["date_month"]) && isset($marriage["date_day"]))
                $vals["MarriageDate"] = combine_date($marriage["date_year"] , $marriage["date_month"], $marriage["date_day"]);
            if (isset($marriage["div_year"]) && isset($marriage["div_month"]) && isset($marriage["div_day"]))
                $vals["DivorceDate"] = combine_date($marriage["div_year"] , $marriage["div_month"], $marriage["div_day"]);
            if (isset($marriage["cancel_year"]) && isset($marriage["cancel_month"]) && isset($marriage["cancel_day"]))
                $vals["CancelledDate"] = combine_date($marriage["cancel_year"] , $marriage["cancel_month"], $marriage["cancel_day"]);
            if (isset($marriage["notes"]))
                $vals["PrivateNotes"] = $marriage["notes"];
        
            if ($marriage["id"] == "NEW") {
                $marriage["id"] = insert("Marriage", $vals);
                $updates["mar_id_".$index] = $marriage["id"];
            } else
                update("Marriage", $vals, "\"ID\" = " . $marriage["id"]);

            // Handle each of the Participants
            // This person
            $vals = array();
            $vals["MarriageID"] = $marriage["id"];
            $vals["PersonID"] = $personal["ID"];
            $vals["Role"] = $mrole;
            if (isset($marriage["name_id"]))
                $vals["NameUsedID"] = $marriage["name_id"];

            updateInsertPM($vals);
            // Spouse
            if (isset($marriage["spouse_person_id"])) {
                $vals = array();
                $vals["MarriageID"] = $marriage["id"];
                $vals["PersonID"] = $marriage["spouse_person_id"];
                $vals["Role"] = $srole;
                updateInsertPM($vals);
            }
            // Proxy
            if (isset($marriage["proxy_person_id"])) {
                $vals = array();
                $vals["MarriageID"] = $marriage["id"];
                $vals["PersonID"] = $marriage["proxy_person_id"];
                $vals["Role"] = "Proxy".$mrole;
                updateInsertPM($vals);
            }
            // Spouse Proxy
            if (isset($marriage["spouse_proxy_person_id"])) {
                $vals = array();
                $vals["MarriageID"] = $marriage["id"];
                $vals["PersonID"] = $marriage["spouse_proxy_person_id"];
                $vals["Role"] = "Proxy".$srole;
                updateInsertPM($vals);
            }
            // Officiator
            if (isset($marriage["officiator_person_id"]) && $marriage["officiator_person_id"] != "") {
                $vals = array();
                $vals["MarriageID"] = $marriage["id"];
                $vals["PersonID"] = $marriage["officiator_person_id"];
                $vals["Role"] = "Officiator";
                // Temporary: only select or insert for Officiators, to avoid messing up the data!
                //if (!update("PersonMarriage", $vals, "\"MarriageID\" = " . $vals["MarriageID"] . 
                //    " AND \"Role\" = '" . $vals["Role"] . "'"))
                if (!search("PersonMarriage", "\"MarriageID\" = " . $vals["MarriageID"] . 
                    " AND \"Role\" = '" . $vals["Role"] . "' AND \"PersonID\" = " . $vals["PersonID"]))
                    insert("PersonMarriage", $vals);
            }
        }
    }
    foreach ($rites as $index => $rite) {
        $vals = array();
        /**
            [1] => Array
                (
                    [id] => NEW
                    [type] => endowment
                    [date_month] => MM
                    [date_day] => DD
                    [date_year] => YYYY
                    [place_id] => 7882
                    [officiator_person_id] => 4194
                    [officiator_role] => asdf
                    [proxy_person_id] => 55173
                    [annointed_to_person_id] => 9422
                    [annointed_to_proxy_person_id] => 4194
                    [name_id] => 
                    [notes] => 
                )
                **/
        if (isset($rite["deleted"]) && $rite["deleted"] == "YES") {
            // Do the delete if it's not NEW
            if ($rite["id"] != "NEW") {
                // Delete 
                dbdelete("TempleRiteOfficiators", "\"NonMaritalTempleRitesID\" = " . $rite["id"]);
                dbdelete("NonMaritalTempleRites", "\"ID\" = " . $rite["id"]);
            }
        } else {
            // Insert or Update
            $vals["PersonID"] = $personal["ID"];
            if (isset($rite["type"]))
                $vals["Type"] = $rite["type"];
            if (isset($rite["proxy_person_id"]))
                $vals["ProxyID"] = $rite["proxy_person_id"];
            if (isset($rite["annointed_to_person_id"]))
                $vals["AnnointedToID"] = $rite["annointed_to_person_id"];
            if (isset($rite["annointed_to_proxy_person_id"]))
                $vals["AnnointedToProxyID"] = $rite["annointed_to_proxy_person_id"];
            if (isset($rite["place_id"]))
                $vals["PlaceID"] = $rite["place_id"];
            if (isset($rite["name_id"]))
                $vals["NameUsedID"] = $rite["name_id"];
            if (isset($rite["date_year"]) && isset($rite["date_month"]) && isset($rite["date_day"]))
                $vals["Date"] = combine_date($rite["date_year"], $rite["date_month"], $rite["date_day"]);
            if (isset($rite["notes"]))
                $vals["PrivateNotes"] = $rite["notes"];

            if ($rite["id"] == "NEW") {
                $rite["id"] = insert("NonMaritalTempleRites", $vals);
                $updates["tr_id_".$index] = $rite["id"];
            } else {
                update("NonMaritalTempleRites", $vals, "\"ID\" = " . $rite["id"]);
            }

            // Add the officiator, if not already set:
            $vals = array();
            if (isset($rite["officiator_person_id"]))
                $vals["PersonID"] = $rite["officiator_person_id"];
            if (isset($rite["officiator_role"]))
                $vals["Role"] = $rite["officiator_role"];
            $vals["NonMaritalTempleRitesID"] = $rite["id"];
            // Assumption right now: there is only ONE officiator (only one is allowed on the data entry screen right now)
            if (search("TempleRiteOfficiators", "\"NonMaritalTempleRitesID\" = " . $vals["NonMaritalTempleRitesID"])) {
                // found something
                if ($vals["PersonID"] != null && $vals["PersonID"] != "") {
                    // Need to overwrite it with the new values
                    update("TempleRiteOfficiators", $vals, "\"NonMaritalTempleRitesID\" = " . $vals["NonMaritalTempleRitesID"]);
                } else {
                    // Either person is null or empty, so delete the record in the db
                    dbdelete("TempleRiteOfficiators", "\"NonMaritalTempleRitesID\" = " . $vals["NonMaritalTempleRitesID"]);
                }
            } else {
                // Didn't find one, so insert if needed
                if ($vals["PersonID"] != null && $vals["PersonID"] != "") {
                    insert("TempleRiteOfficiators", $vals);
                }
            } 

        }
    }

    foreach ($sealings as $index => $sealing) {
        $vals = array();
        /**
            [1] => Array
                (
                    [id] => NEW
                    [type] => adoption
                    [date_month] => MM
                    [date_day] => DD
                    [date_year] => YYYY
                    [place_id] => 16976
                    [officiator_person_id] => 4194
                    [proxy_person_id] => 31062
                    [marriage_id] => 13016
                    [proxy_marriage_id] => 914
                    [name_id] => 
                    [notes] => 
                )
                **/
        if (isset($sealing["deleted"]) && $sealing["deleted"] == "YES") {
            // Do the delete if it's not NEW
            if ($sealing["id"] != "NEW") {
                // Delete 
                dbdelete("NonMaritalSealings", "\"ID\" = " . $sealing["id"]);
            }
        } else {
            // Insert or Update

            $vals["AdopteeID"] = $personal["ID"];
            if (isset($sealing["type"]))
                $vals["Type"] = $sealing["type"];
            if (isset($sealing["proxy_person_id"]))
                $vals["AdopteeProxyID"] = $sealing["proxy_person_id"];
            if (isset($sealing["marriage_id"]))
                $vals["MarriageID"] = $sealing["marriage_id"];
            if (isset($sealing["proxy_father_person_id"]))
                $vals["FatherProxyID"] = $sealing["proxy_father_person_id"];
            if (isset($sealing["proxy_mother_person_id"]))
                $vals["MotherProxyID"] = $sealing["proxy_mother_person_id"];
            if (isset($sealing["proxy_marriage_id"]))
                $vals["MarriageProxyID"] = $sealing["proxy_marriage_id"];
            if (isset($sealing["officiator_person_id"]))
                $vals["OfficiatorID"] = $sealing["officiator_person_id"];
            if (isset($sealing["place_id"]))
                $vals["PlaceID"] = $sealing["place_id"];
            if (isset($sealing["name_id"]))
                $vals["NameUsedID"] = $sealing["name_id"];
            if (isset($sealing["notes"]))
                $vals["PrivateNotes"] = $sealing["notes"];

            // need to add Name as Sealed to the DB
            // $vals["NameID"] = $sealing["name_id"];

            if (isset($sealing["date_year"]) && isset($sealing["date_month"]) && isset($sealing["date_day"]))
                $vals["Date"] = combine_date($sealing["date_year"], $sealing["date_month"], $sealing["date_day"]);

            if ($sealing["id"] == "NEW") {
                // do insert
                $sealid = insert("NonMaritalSealings", $vals);
                $updates["nms_id_".$index] = $sealid;
            } else {
                // do update
                update("NonMaritalSealings", $vals, "\"ID\" = " . $sealing["id"]);
            }
        }
    }

    foreach ($offices as $index => $office) {
        $vals = array();
        /**
            Array
            (
                [deleted] => NO
                [id] => 1
                [office_id] => 1
                [from_day] => 
                [from_month] => 
                [from_year] => 
                [from_status] => exact
                [to_day] => 
                [to_month] => 
                [to_year] => 
                [to_status] => exact
                [officiator1_person_id] => 
                [officiator2_person_id] => 
                [officiator3_person_id] => 
                [notes] => 
            )
        **/

        if (isset($office["deleted"]) && $office["deleted"] == "YES") {
            // Do the delete if it's not NEW
            if ($office["id"] != "NEW") {
                // Delete 
                dbdelete("PersonOffice", "\"ID\" = " . $office["id"]);
            }
        } else {
            // Insert or Update

            $vals["PersonID"] = $personal["ID"];
            if (isset($office["office_id"]))
                $vals["OfficeID"] = $office["office_id"];
            if (isset($office["officiator1_person_id"]))
                $vals["OfficiatorID1"] = $office["officiator1_person_id"];
            if (isset($office["officiator2_person_id"]))
                $vals["OfficiatorID2"] = $office["officiator2_person_id"];
            if (isset($office["officiator3_person_id"]))
                $vals["OfficiatorID3"] = $office["officiator3_person_id"];
            if (isset($office["notes"]))
                $vals["PrivateNotes"] = $office["notes"];

            if (isset($office["from_year"]) && isset($office["from_month"]) && isset($office["from_day"]))
                $vals["From"] = combine_date($office["from_year"], $office["from_month"], $office["from_day"]);
            if (isset($office["from_status"]))
                $vals["FromStatus"] = $office["from_status"];
            
            if (isset($office["to_year"]) && isset($office["to_month"]) && isset($office["to_day"]))
                $vals["To"] = combine_date($office["to_year"], $office["to_month"], $office["to_day"]);
            if (isset($office["to_status"]))
                $vals["ToStatus"] = $office["to_status"];

            if ($office["id"] == "NEW") {
                // do insert
                $offid = insert("PersonOffice", $vals);
                $updates["office_id_".$index] = $offid;
            } else {
                // do update
                update("PersonOffice", $vals, "\"ID\" = " . $office["id"]);
            }
        }
    }

    foreach ($names as $index => $name) {
        $vals = array();
        /**
            [1] => Array
                (
                    [id] => 52338
                    [type] => authoritative
                    [prefix] => 
                    [first] => Zina
                    [middle] => Diantha
                    [last] => Huntington
                    [suffix] => 
                )
                **/
        if (isset($name["deleted"]) && $name["deleted"] == "YES") {
            // Do the delete if it's not NEW
            if ($name["id"] != "NEW") {
                // Delete
                if(!dbdelete("Name", "\"ID\" = " . $name["id"]))
                    array_push($errors, "Unable to delete name \"" . implode(" ", array($name["prefix"], $name["first"], $name["middle"], $name["last"], $name["suffix"])) . ",\" likely used elsewhere");
            }
        } else {
            // Insert or Update
            if (isset($name["type"]))
                $vals["Type"] = $name["type"];
            if (isset($name["prefix"]))
                $vals["Prefix"] = $name["prefix"];
            if (isset($name["first"]))
                $vals["First"] = $name["first"];
            if (isset($name["middle"]))
                $vals["Middle"] = $name["middle"];
            if (isset($name["last"]))
                $vals["Last"] = $name["last"];
            if (isset($name["suffix"]))
                $vals["Suffix"] = $name["suffix"];

            // Add the person id from the main page
            $vals["PersonID"] = $personal["ID"];

            if ($name["id"] == "NEW") {
                // do insert
                $nameid = insert("Name", $vals);
                $updates["name_id_".$index] = $nameid;
            } else {
                // do update
                update("Name", $vals, "\"ID\" = " . $name["id"]);
            }
        }
    }

    // Insert all the personal data back in

    /**
        Array
        (
            [ID] => 1907
            [BrownID] => 2971
            [birthmonth] => 01
            [birthday] => 31
            [birthyear] => 1821
            [b_place_id] => 262
            [b_marriage_id] => 156
            [deathmonth] => 08
            [deathday] => 27
            [deathyear] => 1901
            [d_place_id] => 37
            [n_i] => 8
            [r_i] => 1
            [s_i] => 1
            [m_i] => 6
        )
     **/

    $vals = array();
    if(isset($personal["gender"]))
        $vals["Gender"] = $personal["gender"];
    if(isset($personal["b_marriage_id"]))
        $vals["BiologicalChildOfMarriage"] = $personal["b_marriage_id"];
    if(isset($personal["b_place_id"]))
        $vals["BirthPlaceID"] = $personal["b_place_id"];
    if(isset($personal["d_place_id"]))
        $vals["DeathPlaceID"] = $personal["d_place_id"];
    if(isset($personal["birthyear"]) && isset($personal["birthmonth"]) && isset($personal["birthday"]))
        $vals["BirthDate"] = combine_date($personal["birthyear"], $personal["birthmonth"], $personal["birthday"]);
    if(isset($personal["deathyear"]) && isset($personal["deathmonth"]) && isset($personal["deathday"]))
        $vals["DeathDate"] = combine_date($personal["deathyear"], $personal["deathmonth"], $personal["deathday"]);
    $notes = "";
    if(isset($personal["personal_notes"]))
        $notes .= $personal["personal_notes"];
    $notes .= "\n==MARRIAGE==\n";
    if(isset($personal["notes_marriage"]))
        $notes .= $personal["notes_marriage"];
    $notes .= "\n==NON-MARITAL==\n";
    if(isset($personal["non_marital_notes"]))
        $notes .= $personal["non_marital_notes"];
    $notes .= "\n==TEMPLE-RITES==\n";
    if(isset($personal["temple_rite_notes"]))
        $notes .= $personal["temple_rite_notes"];

    $vals["PrivateNotes"] = $notes;

    if ($personal["ID"] == "NEW")
        // do insert
        insert("Person", $vals);
    else {
        // do update
        update("Person", $vals, "\"ID\" = " . $personal["ID"]);
    }

    // Handle brown status update, if available
    if (isset($personal["BrownID"]) && $personal["BrownID"] != "" && is_numeric($personal["BrownID"])
        && isset($personal["brown_state"]) && $personal["brown_state"] != "") {
        update("Brown", array("Progress"=>$personal["brown_state"]), "\"id\" = " . $personal["BrownID"]);
    }


    // Output the results as a paper trail, just in case something bad happens

    fwrite($output, "\n\n");
    fclose($output);
    close_db();

    $returnval = array();
    if (empty($errors))
        $returnval["retval"] = "success";
    else {
        $returnval["retval"] = "failure";
        $returnval["messages"] = $errors;
    }
    $returnval["updates"] = $updates;

    header('Content-type: application/json');
    echo json_encode($returnval);

?>
