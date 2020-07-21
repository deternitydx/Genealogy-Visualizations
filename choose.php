<?php

$name = "Brigham Young";
$id = 615;
$wife = 0;
$levels = 1;
if (isset($_GET["id"])) {
    $id = $_GET["id"];


    if ($id == "495") {
        $levels = 0;
        $name = "Joseph Smith";
    } else if ($id == "1907") {
        $name = "Zina Huntington";
        $wife = 1;
    } 
}
?>

<html>
<head>
<title>Sample Genealogy Visualizations</title>

<link rel="stylesheet" href="css/style.css">
</head>

<body>
<h1>Genealogy Visualizations</h1>
<h4>Robbie Hott, <a href="http://www.cs.virginia.edu/~jh2jf/">www.cs.virginia.edu/~jh2jf</a></h4>
<h4>Notes <a href="http://www.cs.virginia.edu/~jh2jf/notes/">www.cs.virginia.edu/~jh2jf/notes/</a></h4>
<h2><?=$name?></h2>
<ul>
<li><a href="chord.html?id=<?=$id?>&temporal=1">Chord Diagram over Time</a>: Displays a chord diagram of the marriage, allowing the user to choose a time within
	the marriage to view or use a slider to step through the marriage.  Given a husband's id (by argument "id") from the real database, it shows 
	that man's marriages and children in chord form.  It also allows for a "time" argument of the form "YYYY-MM-DD" as the date of the marriage
	status to show  Links are created from women to their children in the marriage
    </li>
<?php
    $wifestr = "";
    if ($wife == 1)
        $wifestr = "&wife=1";
?>
    <li><a href="marriageflow_temporal.html?id=<?=$id?>&levels=<?=$levels?><?=$wifestr?>">Temporal Lineage Flow Network</a>: 
    Displays a sankey-like flow diagram of marriages, where the
	marriage units are represented by circles in the diagram.  
    People are hyperedges between marriages, utilizing a square node to define the person in the network. This version is <em>temporal</em>,
    meaning that it provides a time slider to visualize which portions of the lineage are alive at any point in time.  Any marriages or
    individuals which have no dates available will be shown "ghosted" for all time. 
    On clicking a marriage unit, this will open up a popup frame with a chord diagram of the marriage.
    </li>
</ul>
</body>
</html>

