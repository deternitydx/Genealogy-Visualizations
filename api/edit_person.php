<?php
include("../database.php");
    $id = null;
    // Get the person ID
    if (isset($_GET["id"]) && is_numeric($_GET["id"]))
        $id = $_GET["id"];
    else if (isset($_GET["id"]) && $_GET["id"] == "NEW")
        $id = "NEW"; // creating a new person
    else
        die("Please provide a numeric UVA Person ID");
    
    header('Content-type: application/json');
    
    $db = pg_connect($db_conn_string);
    
    // Array to hold all information about the person
    $person = array();

    if ($id == "NEW") {
        $person["information"] = array("ID" => "NEW",
                                       "BirthDate" => "",
                                       "DeathDate" => "",
                                       "BirthPlaceName" => "",
                                       "BirthPlaceID" => "",
                                       "DeathPlaceName" => "",
                                       "DeathPlaceID" => "",
                                       "Gender" => "",
                                       "ParentMarriageString" => "",
                                       "BiologicalChildOfMarriage" => "");
        $person["notes"] = array(
            "personal" => "",
            "marriage" => "",
            "nms" => "",
            "rites" => "");
        $person["names"] = array( 0=>array("Type"=>"authoritative",
                                           "Prefix" => "",
                                           "First" => "",
                                           "Middle" => "",
                                           "Last" => "",
                                           "Suffix" => "",
                                           "ID" => "NEW"));
        $person["temple_rites"] = array();
        $person["non_marital_sealings"] = array();
        $person["marriages"] = array();
        $person["offices"] = array();
        echo json_encode($person);
        exit();
    }

    // Get Personal Information
    $result = pg_query($db, "SELECT p.*, bp.\"OfficialName\" as \"BirthPlaceName\", dp.\"OfficialName\" as \"DeathPlaceName\" FROM public.\"Person\" p
                                LEFT JOIN \"Place\" bp ON p.\"BirthPlaceID\" = bp.\"ID\"
                                LEFT JOIN \"Place\" dp ON p.\"DeathPlaceID\" = dp.\"ID\"
                             WHERE p.\"ID\"=$id");
    if (!$result) {
        exit;
    }
    foreach(pg_fetch_all($result) as $res) {
        $person["information"] = $res;
    }

    $person["notes"] = array(
        "personal" => "",
        "marriage" => "",
        "nms" => "",
        "rites" => "");
    
    // Split out the notes sections
    // They always appear as:
    //  ...personal notes... 
    //  ==MARRIAGE== 
    //  ...marriage notes...
    //  ==NON-MARITAL==
    //  ...nms notes...
    //  ==TEMPLE-RITES==
    //  ...temple rite notes ...
    if (isset($person["information"]) && isset($person["information"]["PrivateNotes"])) {
        $notes = $person["information"]["PrivateNotes"];
        $pieces = explode("\n==MARRIAGE==\n", $notes);
        // Personal first
        if (isset($pieces[0]))
            $person["notes"]["personal"] = $pieces[0];
        if (isset($pieces[1])) {
            $rest = $pieces[1];
            $pieces = explode("\n==NON-MARITAL==\n", $rest);
            // Marriage next
            if(isset($pieces[0]))
                $person["notes"]["marriage"] = $pieces[0];
            if (isset($pieces[1])) {
                $rest = $pieces[1];
                $pieces = explode("\n==TEMPLE-RITES==\n", $rest);
                // Non-Marital next
                if(isset($pieces[0]))
                    $person["notes"]["nms"] = $pieces[0];
                // Temple Rites last
                if(isset($pieces[1]))
                    $person["notes"]["rites"] = $pieces[1];
            }
        }
    }

    // Get the biological birth parent marriage, if it exists
    $person["information"]["ParentMarriageString"] = "";
    if (isset($person["information"]) && 
        isset($person["information"]["BiologicalChildOfMarriage"]) && 
        is_numeric($person["information"]["BiologicalChildOfMarriage"])) {
        
        $query = "
            SELECT DISTINCT m.*, pl.\"OfficialName\" as \"PlaceName\", hn.\"First\" as \"HusbandFirst\", hn.\"Last\" as \"HusbandLast\", wn.\"First\" as \"WifeFirst\", wn.\"Last\" as \"WifeLast\" 
            FROM public.\"Marriage\" m
            LEFT JOIN public.\"PersonMarriage\" hpm ON hpm.\"MarriageID\" = m.\"ID\" AND hpm.\"Role\" = 'Husband'
            LEFT JOIN public.\"PersonMarriage\" wpm ON wpm.\"MarriageID\" = m.\"ID\" AND wpm.\"Role\" = 'Wife'
            LEFT JOIN public.\"Name\" hn ON hpm.\"PersonID\" = hn.\"PersonID\" AND hn.\"Type\" = 'authoritative'
            LEFT JOIN public.\"Name\" wn ON wpm.\"PersonID\" = wn.\"PersonID\" AND wn.\"Type\" = 'authoritative' 
            LEFT JOIN public.\"Place\" pl ON m.\"PlaceID\" = pl.\"ID\"
            WHERE m.\"ID\" = {$person["information"]["BiologicalChildOfMarriage"]} 
            ORDER BY hn.\"Last\", hn.\"First\", wn.\"Last\", wn.\"First\" ASC LIMIT 1";
        $result = pg_query($db, $query);
        if (!$result) {
            exit;
        }
        $results = pg_fetch_all($result);
        foreach($results as $res) {
            if (!isset($person["information"])) $person["information"] = array();
            $person["information"]["ParentMarriageString"] = $res["HusbandLast"] . ", " . $res["HusbandFirst"] . " to " . $res["WifeLast"] . ", " . $res["WifeFirst"] . " (" . $res["MarriageDate"] . " : " . $res["Type"] . ") " . $res["ID"];
        }
    }

    // Get All Names
    $result = pg_query($db, "SELECT * FROM public.\"Name\" n WHERE n.\"PersonID\"=$id");
    if (!$result) {
        exit;
    }
    $person["names"] = pg_fetch_all($result);

    // Get Non-Marital Sealings
    $result = pg_query($db, "

        SELECT DISTINCT n.*, p.\"OfficialName\" as \"PlaceName\", 
                CONCAT(nas.\"Prefix\", ' ', nas.\"First\", ' ', nas.\"Middle\" , ' ', nas.\"Last\", ' ', nas.\"Suffix\") as \"NameUsed\",
                CONCAT(pn.\"Prefix\", ' ', pn.\"First\", ' ', pn.\"Middle\" , ' ', pn.\"Last\", ' ', pn.\"Suffix\", ' ', pn.\"PersonID\") as \"ProxyName\",
                CONCAT(offn.\"Prefix\", ' ', offn.\"First\", ' ', offn.\"Middle\" , ' ', offn.\"Last\", ' ', offn.\"Suffix\", ' ', offn.\"PersonID\") as \"OfficiatorName\",
                CONCAT(m.\"HusbandName\", ' to ',  m.\"WifeName\", ' (',m.\"MarriageDate\",' : ',m.\"Type\", ')') as \"MarriageString\",
                CONCAT(pfn.\"Prefix\", ' ', pfn.\"First\", ' ', pfn.\"Middle\" , ' ', pfn.\"Last\", ' ', pfn.\"Suffix\", ' ', pfn.\"PersonID\") as \"ProxyFatherName\",
                CONCAT(pmn.\"Prefix\", ' ', pmn.\"First\", ' ', pmn.\"Middle\" , ' ', pmn.\"Last\", ' ', pmn.\"Suffix\", ' ', pmn.\"PersonID\") as \"ProxyMotherName\"
        FROM public.\"NonMaritalSealings\" n
        LEFT JOIN public.\"Place\" p on p.\"ID\" = n.\"PlaceID\"
        LEFT JOIN public.\"Name\" nas on nas.\"ID\" = n.\"NameUsedID\"
        LEFT JOIN public.\"Name\" pn on pn.\"PersonID\" = n.\"AdopteeProxyID\" AND pn.\"Type\" = 'authoritative'
        LEFT JOIN public.\"Name\" offn on offn.\"PersonID\" = n.\"OfficiatorID\" AND offn.\"Type\" = 'authoritative'
        LEFT JOIN (
                SELECT DISTINCT m.\"ID\", m.\"MarriageDate\", m.\"DivorceDate\", m.\"CancelledDate\", m.\"Type\",
                        m.\"PublicNotes\", m.\"PrivateNotes\",
                        CONCAT(hn.\"Prefix\", ' ', hn.\"First\", ' ', hn.\"Middle\" , ' ', hn.\"Last\", ' ', hn.\"Suffix\") as \"HusbandName\",
                        CONCAT(wn.\"Prefix\", ' ', wn.\"First\", ' ', wn.\"Middle\" , ' ', wn.\"Last\", ' ', wn.\"Suffix\") as \"WifeName\"
                        FROM public.\"Marriage\" m
                        LEFT JOIN public.\"PersonMarriage\" hpm ON hpm.\"Role\" = 'Husband' AND hpm.\"MarriageID\" = m.\"ID\"
                        LEFT JOIN public.\"Name\" hn ON hn.\"PersonID\" = hpm.\"PersonID\" AND hn.\"Type\" = 'authoritative'
                        LEFT JOIN public.\"PersonMarriage\" wpm ON wpm.\"Role\" = 'Wife' AND wpm.\"MarriageID\" = m.\"ID\"
                        LEFT JOIN public.\"Name\" wn ON wn.\"PersonID\" = wpm.\"PersonID\" AND wn.\"Type\" = 'authoritative'
                    ) m ON m.\"ID\" = n.\"MarriageID\"
        LEFT JOIN public.\"Name\" pfn on pfn.\"PersonID\" = n.\"FatherProxyID\" AND pfn.\"Type\" = 'authoritative'
        LEFT JOIN public.\"Name\" pmn on pmn.\"PersonID\" = n.\"MotherProxyID\" AND pmn.\"Type\" = 'authoritative'
        WHERE n.\"AdopteeID\"=$id");
    if (!$result) {
        die("Problem getting non-marital sealings");
    }
    $person["non_marital_sealings"] = pg_fetch_all($result);

    // Get Temple Rites
    $result = pg_query($db, "
        SELECT n.*, off.\"PersonID\" as \"OfficiatorID\", off.\"Role\" as \"OfficiatorRole\", p.\"OfficialName\" as \"PlaceName\",
                CONCAT(nas.\"Prefix\", ' ', nas.\"First\", ' ', nas.\"Middle\" , ' ', nas.\"Last\", ' ', nas.\"Suffix\") as \"NameUsed\",
                CONCAT(offn.\"Prefix\", ' ', offn.\"First\", ' ', offn.\"Middle\" , ' ', offn.\"Last\", ' ', offn.\"Suffix\", ' ', offn.\"PersonID\") as \"OfficiatorName\",
                CONCAT(pn.\"Prefix\", ' ', pn.\"First\", ' ', pn.\"Middle\" , ' ', pn.\"Last\", ' ', pn.\"Suffix\", ' ', pn.\"PersonID\") as \"ProxyName\",
                CONCAT(atn.\"Prefix\", ' ', atn.\"First\", ' ', atn.\"Middle\" , ' ', atn.\"Last\", ' ', atn.\"Suffix\", ' ', atn.\"PersonID\") as \"AnnointedToName\",
                CONCAT(atpn.\"Prefix\", ' ', atpn.\"First\", ' ', atpn.\"Middle\" , ' ', atpn.\"Last\", ' ', atpn.\"Suffix\", ' ', atpn.\"PersonID\") as \"AnnointedToProxyName\"
        FROM public.\"NonMaritalTempleRites\" n 
        LEFT JOIN public.\"Place\" p on p.\"ID\" = n.\"PlaceID\"
        LEFT JOIN public.\"Name\" nas on nas.\"ID\" = n.\"NameUsedID\"
        LEFT JOIN public.\"Name\" pn on pn.\"PersonID\" = n.\"ProxyID\" AND pn.\"Type\" = 'authoritative'
        LEFT JOIN public.\"Name\" atn on atn.\"PersonID\" = n.\"AnnointedToID\" AND atn.\"Type\" = 'authoritative'
        LEFT JOIN public.\"Name\" atpn on atpn.\"PersonID\" = n.\"AnnointedToProxyID\" AND atpn.\"Type\" = 'authoritative'
        LEFT JOIN public.\"TempleRiteOfficiators\" off ON off.\"NonMaritalTempleRitesID\" = n.\"ID\"
        LEFT JOIN public.\"Name\" offn on off.\"PersonID\" = offn.\"PersonID\" AND offn.\"Type\" = 'authoritative'
        WHERE n.\"PersonID\"=$id");
    if (!$result) {
        exit;
    }
    $person["temple_rites"] = pg_fetch_all($result);

    // Get All Marriages
    $result = null;
    if ($person["information"]["Gender"] == "Male") 
        $result = pg_query($db, "
                        SELECT DISTINCT m.\"ID\", m.\"PlaceID\", p.\"OfficialName\" as \"PlaceName\", m.\"MarriageDate\", m.\"DivorceDate\",
                                    m.\"CancelledDate\", m.\"Type\", m.\"PrivateNotes\", w.\"PersonID\" as \"SpouseID\", 
                                    wn.\"First\", wn.\"Middle\", wn.\"Last\",
                                    CONCAT(wn.\"Prefix\", ' ', wn.\"First\", ' ', wn.\"Middle\" , ' ', wn.\"Last\", ' ', wn.\"Suffix\", ' ', wn.\"PersonID\") as \"SpouseName\",
                                    h.\"PersonID\" as \"HusbandID\", h.\"NameUsedID\", m.\"Root\",
                                    h.\"OfficeWhenPerformed\", w.\"OfficeWhenPerformed\" as \"SpouseOfficeWhenPerformed\",
                                    CONCAT(nas.\"Prefix\", ' ', nas.\"First\", ' ', nas.\"Middle\" , ' ', nas.\"Last\", ' ', nas.\"Suffix\") as \"NameUsed\",
                                    off.\"PersonID\" as \"OfficiatorID\", CONCAT(offn.\"Prefix\", ' ', offn.\"First\", ' ', offn.\"Middle\" , ' ', offn.\"Last\", ' ', offn.\"Suffix\", ' ', offn.\"PersonID\") as \"OfficiatorName\",
                                    wp.\"PersonID\" as \"SpouseProxyID\", CONCAT(wpn.\"Prefix\", ' ', wpn.\"First\", ' ', wpn.\"Middle\" , ' ', wpn.\"Last\", ' ', wpn.\"Suffix\", ' ', wpn.\"PersonID\") as \"SpouseProxyName\",
                                    hp.\"PersonID\" as \"ProxyID\", CONCAT(hpn.\"Prefix\", ' ', hpn.\"First\", ' ', hpn.\"Middle\" , ' ', hpn.\"Last\", ' ', hpn.\"Suffix\", ' ', hpn.\"PersonID\") as \"ProxyName\"
                            FROM public.\"Marriage\" m
                            LEFT JOIN public.\"PersonMarriage\" h ON h.\"MarriageID\" = m.\"ID\" AND h.\"Role\" = 'Husband'
                            LEFT JOIN public.\"PersonMarriage\" w ON w.\"MarriageID\" = m.\"ID\" AND w.\"Role\" = 'Wife'
                            LEFT JOIN public.\"Name\" nas on nas.\"ID\" = h.\"NameUsedID\"
                            LEFT JOIN public.\"Place\" p ON m.\"PlaceID\" = p.\"ID\"
                            LEFT OUTER JOIN public.\"Name\" wn 
                                        ON w.\"PersonID\" = wn.\"PersonID\" AND wn.\"Type\" = 'authoritative'
                            LEFT OUTER JOIN public.\"PersonMarriage\" off ON off.\"MarriageID\" = m.\"ID\" AND off.\"Role\" = 'Officiator'
                            LEFT OUTER JOIN public.\"Name\" offn 
                                        ON off.\"PersonID\" = offn.\"PersonID\" AND offn.\"Type\" = 'authoritative'
                            LEFT OUTER JOIN public.\"PersonMarriage\" hp ON hp.\"MarriageID\" = m.\"ID\" AND hp.\"Role\" = 'ProxyHusband'
                            LEFT OUTER JOIN public.\"Name\" hpn 
                                        ON hp.\"PersonID\" = hpn.\"PersonID\" AND hpn.\"Type\" = 'authoritative'
                            LEFT OUTER JOIN public.\"PersonMarriage\" wp ON wp.\"MarriageID\" = m.\"ID\" AND wp.\"Role\" = 'ProxyWife'
                            LEFT OUTER JOIN public.\"Name\" wpn 
                                        ON wp.\"PersonID\" = wpn.\"PersonID\" AND wpn.\"Type\" = 'authoritative'
                                    WHERE h.\"PersonID\"=$id ORDER BY m.\"MarriageDate\" ASC");
    else
        $result = pg_query($db, "
                        SELECT DISTINCT m.\"ID\", m.\"PlaceID\", p.\"OfficialName\" as \"PlaceName\", m.\"MarriageDate\", m.\"DivorceDate\",
                                    m.\"CancelledDate\", m.\"Type\", m.\"PrivateNotes\",  w.\"PersonID\" as \"WifeID\", w.\"NameUsedID\",
                                    hn.\"First\", hn.\"Middle\", hn.\"Last\",
                                    CONCAT(hn.\"Prefix\", ' ', hn.\"First\", ' ', hn.\"Middle\" , ' ', hn.\"Last\", ' ', hn.\"Suffix\", ' ', hn.\"PersonID\") as \"SpouseName\",

                                    h.\"PersonID\" as \"SpouseID\", m.\"Root\",
                                    w.\"OfficeWhenPerformed\", h.\"OfficeWhenPerformed\" as \"SpouseOfficeWhenPerformed\",
                                    CONCAT(nas.\"Prefix\", ' ', nas.\"First\", ' ', nas.\"Middle\" , ' ', nas.\"Last\", ' ', nas.\"Suffix\") as \"NameUsed\",
                                    off.\"PersonID\" as \"OfficiatorID\", CONCAT(offn.\"Prefix\", ' ', offn.\"First\", ' ', offn.\"Middle\" , ' ', offn.\"Last\", ' ', offn.\"Suffix\", ' ', offn.\"PersonID\") as \"OfficiatorName\",
                                    hp.\"PersonID\" as \"SpouseProxyID\", CONCAT(hpn.\"Prefix\", ' ', hpn.\"First\", ' ', hpn.\"Middle\" , ' ', hpn.\"Last\", ' ', hpn.\"Suffix\", ' ', hpn.\"PersonID\") as \"SpouseProxyName\",
                                    wp.\"PersonID\" as \"ProxyID\", CONCAT(wpn.\"Prefix\", ' ', wpn.\"First\", ' ', wpn.\"Middle\" , ' ', wpn.\"Last\", ' ', wpn.\"Suffix\", ' ', wpn.\"PersonID\") as \"ProxyName\"
                            FROM public.\"Marriage\" m 
                            LEFT JOIN public.\"PersonMarriage\" h ON h.\"MarriageID\" = m.\"ID\" AND h.\"Role\" = 'Husband'
                            LEFT JOIN public.\"PersonMarriage\" w ON w.\"MarriageID\" = m.\"ID\" AND w.\"Role\" = 'Wife'
                            LEFT JOIN public.\"Name\" nas on nas.\"ID\" = w.\"NameUsedID\"
                            LEFT JOIN public.\"Place\" p ON m.\"PlaceID\" = p.\"ID\"
                            LEFT OUTER JOIN public.\"Name\" hn 
                                        ON h.\"PersonID\" = hn.\"PersonID\" AND hn.\"Type\" = 'authoritative'
                            LEFT OUTER JOIN public.\"PersonMarriage\" off ON off.\"MarriageID\" = m.\"ID\" AND off.\"Role\" = 'Officiator'
                            LEFT OUTER JOIN public.\"Name\" offn 
                                        ON off.\"PersonID\" = offn.\"PersonID\" AND offn.\"Type\" = 'authoritative'
                            LEFT OUTER JOIN public.\"PersonMarriage\" hp ON hp.\"MarriageID\" = m.\"ID\" AND hp.\"Role\" = 'ProxyHusband'
                            LEFT OUTER JOIN public.\"Name\" hpn 
                                        ON hp.\"PersonID\" = hpn.\"PersonID\" AND hpn.\"Type\" = 'authoritative'
                            LEFT OUTER JOIN public.\"PersonMarriage\" wp ON wp.\"MarriageID\" = m.\"ID\" AND wp.\"Role\" = 'ProxyWife'
                            LEFT OUTER JOIN public.\"Name\" wpn 
                                        ON wp.\"PersonID\" = wpn.\"PersonID\" AND wpn.\"Type\" = 'authoritative'
                                    WHERE w.\"PersonID\"=$id ORDER BY m.\"MarriageDate\" ASC");
    
    if (!$result) {
        die("Error finding marriages.");
        exit;
    }

    $person["marriages"] = pg_fetch_all($result);

    // Get the number of children for each marriage
    foreach ($person["marriages"] as $i => $marriage) {
        $result = pg_query("SELECT count(*) from public.\"Person\" where \"BiologicalChildOfMarriage\"={$marriage["ID"]};");

        $arr = pg_fetch_array($result);
        if ($arr != null && !empty($arr))
            $person["marriages"][$i]["children"] = $arr["count"];
        
        $result = pg_query("SELECT count(*) from public.\"NonMaritalSealings\" where \"MarriageID\"={$marriage["ID"]};");

        $arr = pg_fetch_array($result);
        if ($arr != null && !empty($arr))
            $person["marriages"][$i]["adoptees"] = $arr["count"];
    }

    // Get the Offices for this person
    $result = pg_query("SELECT DISTINCT po.\"ID\", po.\"OfficeID\", o.\"Name\" as \"OfficeName\", po.\"From\", po.\"FromStatus\", po.\"To\", 
                            po.\"ToStatus\", po.\"PrivateNotes\", po.\"OfficiatorID1\", po.\"OfficiatorID2\", po.\"OfficiatorID3\",
                            CONCAT(on1.\"Prefix\", ' ', on1.\"First\", ' ', on1.\"Middle\" , ' ', on1.\"Last\", ' ', on1.\"Suffix\", ' ', on1.\"PersonID\") as \"OfficiatorName1\",
                            CONCAT(on2.\"Prefix\", ' ', on2.\"First\", ' ', on2.\"Middle\" , ' ', on2.\"Last\", ' ', on2.\"Suffix\", ' ', on2.\"PersonID\") as \"OfficiatorName2\",
                            CONCAT(on3.\"Prefix\", ' ', on3.\"First\", ' ', on3.\"Middle\" , ' ', on3.\"Last\", ' ', on3.\"Suffix\", ' ', on3.\"PersonID\") as \"OfficiatorName3\"
                            FROM public.\"PersonOffice\" po
                            LEFT OUTER JOIN public.\"Name\" on1 
                                        ON po.\"OfficiatorID1\" = on1.\"PersonID\" AND on1.\"Type\" = 'authoritative'
                            LEFT OUTER JOIN public.\"Name\" on2 
                                        ON po.\"OfficiatorID2\" = on2.\"PersonID\" AND on2.\"Type\" = 'authoritative'
                            LEFT OUTER JOIN public.\"Name\" on3 
                                        ON po.\"OfficiatorID3\" = on3.\"PersonID\" AND on3.\"Type\" = 'authoritative'
                            LEFT OUTER JOIN public.\"Office\" o 
                                        ON po.\"OfficeID\" = o.\"ID\"
                            WHERE po.\"PersonID\" = $id;");

    if (!$result) {
        die("Error finding offices.");
        exit;
    }

    $person["offices"] = pg_fetch_all($result);

    // Get the list of Brown IDs for this person
    $result = pg_query($db, "SELECT DISTINCT \"id\" FROM \"Brown\" WHERE \"PersonID\" = $id;");

    if (!$result) {
        die("Error finding brown ids.");
        exit;
    }
    $person["brown_ids"] = array();
    foreach (pg_fetch_all($result) as $res)
        array_push($person["brown_ids"], $res["id"]);


    // Return the person array as json to be used by the editor:
    echo json_encode($person);
?>
