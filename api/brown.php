<?php
include("../database.php");
header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');

$db = pg_connect($db_conn_string);

$result = pg_query($db, "SELECT \"id\", \"Name\", \"BD\", \"Status\", \"context\", \"PersonID\", \"Progress\" FROM public.\"Brown\" ORDER BY \"Status\", \"Name\" ASC");
if (!$result) {
    exit;
}

$brown = pg_fetch_all($result);
echo "{ \"data\": [";
$print = array();
$progress = array("unseen" => "Unseen", "inProgress" => "In Progress", "done" => "Done");
foreach ($brown as $k=>$v) {
    array_push($print, "[ \"<a href='individual.php?brown={$v["id"]}&id={$v["PersonID"]}'>{$v["Name"]}</a>\", \"{$v["BD"]}\", \"{$v["context"]}\", \"{$v["Status"]}\", \"".$progress[$v["Progress"]] ."\" ]");
    //$brown[$k]["PersonID"] = "<a href='individual.php?id={$brown[$k]["PersonID"]}>Edit</a>";
    
}
echo implode(",", $print);
echo "]}"

?>
