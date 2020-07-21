<?php


function sname($s) {
    list($sur, $for) = explode(", ", $s);
    return "l$sur\tp$for";
}

$data = json_decode(file_get_contents("api/marriages_by_man.php?id=615"), true);

$f = true;
$i = 0;
foreach ($data["parents"] as $p) {
    if ($i == 0) {
         $i = $p["id"];
        echo "i{$p["id"]}\t".sname($p["name"])."\tgm";
        foreach ($data["parents"] as $q) {
            if ($f) {
                $f = false;
                continue;
            }
            echo "\te{$q["id"]}";
        }
        echo "\n";

    } else {
    
        echo "i{$p["id"]}\t".sname($p["name"])."\tgf\te$i\n";
    }
}
$i = 0;
foreach ($data["parents"] as $p) {
    if ($i == 0) {
         $i = $p["id"];
    } else {
    
        echo "p$i {$p["id"]}\te1\tgm\n";
    }
}

foreach ($data["children"] as $c) {
    $g = $c["gender"] == "Male" ? "gm" : "gf";
    $m = 0;
    foreach ($data["relationships"] as $r) {
        if ($r["from"] == $c["id"])
            $m = $r["to"];
    }
    echo "i{$c["id"]}\t".sname($c["name"])."\t$g\tf$i\tm$m\n";
}
