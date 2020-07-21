<html>
<style>

td {
   padding: 4px;
   margin: 0px;
   border: 1px solid black;
}

table {
   border: 1px solid black;
   border-spacing: 0px;
}

tr {
   border: 1px solid black;
}

th {
   color: #ffffff;
   background: #444444;
}

</style>
<body>
<?php

include("../database.php");
$db = pg_connect($db_conn_string);

$csvfile = fopen("conversion/aqdata.csv", "r");
if ($csvfile == NULL)
        die("Error reading file");
$head = fgetcsv($csvfile);
$data1 = fgetcsv($csvfile);
while ($data1 !== false) {
       $data = array();
       foreach ($data1 as $k => $v) 
               $data[$head[$k]] = $v;
       //print_r($data);
       if ($data["ID"] != '') { 
            $result = pg_query($db, "SELECT p.*, n.*, bp.\"OfficialName\" as \"BirthPlace\", dp.\"OfficialName\" as \"DeathPlace\", burp.\"OfficialName\" as \"BurialPlace\" FROM \"Person\" p LEFT OUTER JOIN \"Name\" n on (p.\"ID\" = n.\"PersonID\") LEFT OUTER JOIN \"Place\" bp on (p.\"BirthPlaceID\" = bp.\"ID\") LEFT OUTER JOIN \"Place\" dp on (p.\"DeathPlaceID\" = dp.\"ID\") LEFT OUTER JOIN \"Place\" burp on (p.\"BurialPlaceID\" = burp.\"ID\") WHERE p.\"BYUID\" = {$data["ID"]} ORDER BY p.\"ID\" ASC");
            if (!$result) {
                echo "An error occurred.\n";
                exit;
            }
           

            $row = pg_fetch_array($result);
            //print_r($row);

            echo "<table><tr><th>Key</th><th>Excel File</th><th>Database</th></tr>\n";
            echo "<tr><td>New ID</td><td></td><td>{$row["ID"]}</td></tr>\n";
            foreach ($data as $k => $v) {
                    $key = $k;
                    if (strpos($key, "Surname") !== false) $key = "Last";
                    if ($key == "First and Middle Name") $key = "First";
                    if ($key == "ID") $key = "BYUID";
                    echo "<tr><td width='20%'>$k</td><td width='40%'>$v</td><td width='40%'>";
                    if (isset($row[$key]))
                        echo $row[$key];
                    echo "</td></tr>\n";
            }
            echo "</table>\n";


       }
       $data1 = fgetcsv($csvfile);
}

fclose($csvfile);

function get_insert_statement($tableName, $arr) {
    $insert = "INSERT INTO public.\"$tableName\" ";
    $cols = "";
    $vals = "";
    foreach ($arr as $k => $v) {
            $cols .= "\"$k\",";
            if ($v == "") $v = "NULL";
            if ($k == "BYUID" || $k == "BYUChildOf" || $v == "NULL")
                $vals .= "$v,";
            else
                $vals .= "'$v',";
    }
    $cols = substr($cols, 0, -1);
    $vals = substr($vals, 0, -1);

    $insert .= "($cols) VALUES ($vals);";

    return $insert;
}
?>
</body>
</html>
