<html>
<head>
<title>Genealogy Visualizations</title>

<link rel="stylesheet" href="../css/style.css">
<!-- jQuery -->
<script type="text/javascript" charset="utf8" src="../js/jquery-2.1.1.js"></script>
  
<script type="text/javascript">
function goSankey() {
    var vals = new Array();
    var add = "";
    var levels = "&levels=" + $('#levels').val();
    add = $('#personid').val();
    var link = "../marriageflow.html?id=" + add + levels;
    if ($('#temporal').prop('checked'))
        link = "../marriageflow_temporal.html?id=" + add + levels;
    console.log(link);
    window.location.href = link;
    return false;
}
function goChord() {
    var vals = new Array();
    var add = "";
    add = $('#personid').val();
    var link = "../chord.html?id=" + add;
    if ($('#temporal').prop('checked'))
        link += "&temporal=1";
    console.log(link);
    window.location.href = link;
    return false;
}
</script>
</head>

<body>
<h1>Nauvoo Dataviews</h1>
<h4>Robbie Hott, <a href="http://www.cs.virginia.edu/~jh2jf/">www.cs.virginia.edu/~jh2jf</a></h4>
<h4>Notes <a href="http://www.cs.virginia.edu/~jh2jf/notes/">www.cs.virginia.edu/~jh2jf/notes/</a></h4>

<div class="callout-box">
<h2>Run Visualizations</h2>
    <p>Type a person's UVA ID in the box below and select options to view their chord diagram or lineage flow.</p>
    <p>UVA ID: <input type='text' size="8" id="personid">  Degrees of separation: <select id='levels'><option selected value='0'>0</option><option value='1'>1</option><option value='2'>2</option></select>  Temporal: <input type='checkbox' id='temporal' checked>  View: <button onClick='goSankey();'>Lineage Flow</button> <button onClick='goChord();'>Family Unit Chord</button></p>
</div>

<h2>Visualization Links</h2>
<ul>
    <li><a href="../">Visualizations Homepage</a>: The home of UVA visualizations that defines the kinds of visualizations available.</li>
    <li><a href="sealings_pre1846_viz2.php">Sealings Before 1846 Visualization</a>: Timeline layout of sealings, color-coded by first sealing, additional sealing, and re-sealing.  Attempts to show when that sealing happens with a civil wife.</li>
</ul>

<h2>UVA's Database Views</h2>
<p>Overall Data Access</p>
<ul>
	<li><a href="aq.php">AQ Members</a>: List of Annointed Quorum members currently in our database.</li>
    <li><a href="people.php">All People</a>: Searchable list of all people currently in our database with links to edit.</li>
    <!--<li><form action="search.php" method="get">Search People: Search for all people in the database by name.  Uses a fuzzy search mechanism.  Results may be clicked to find a list of children. <br/>Name <input type="text" name="q" width="25"/> <input type="Submit" value="Search"></form>
    </li>-->
	<li><a href="marriages.php">All Marriages</a>: Searchable list of all marriages currently in our database (using Authoritative First/Last Names of people and Official Name of places).</li>
	<li><a href="places.php">All Places</a>: Searchable list of all places currently in our database (using Official Name).</li>
</ul>
<p>Information and Special Functions</p>
<ul>
    <li><a href="../data_entry/individual.php?id=NEW">New Person</a>: Create a new person in the UVA database.</li>
	<li><a href="documentdb.php">Database Organization</a>: HTML version of the database organization document.  Use pandoc to create a PDF version.</li>
	<!--<li><a href="compare_excel.php">Excel Sheet Comparison</a>: Compares the Excel spreadsheet from Joseph side-by-side with our current database.</li>-->
</ul>

<h2>Special Queries of UVA's Database</h2>
<p>The following are special queries generated to give specific views of our database.</p>
<ul>
	<li><a href="aq_age_at_first_marriage2.php">Age at First Marriage (AQ Members)</a>: Age of each person in the AQ at their first marriage</li>
	<li><a href="age_at_first_marriage2.php">Age at First Marriage</a>: Age of each person at their first marriage</li>
	<li><a href="query1.php">First Long Query</a>: "Display list of (A, C, set of B)s for which (Marriage between A and B for time or eternity) and (Marriage between A and C, civil) and not (Marriage between A and C for time or eternity) and C's death after A+B's sealing"</li>
	<li><a href="sealings_pre1844.php">Sealings Before 1844</a>: List of all time and eternity sealings before 7/1/1844.</li>
	<li><a href="sealings_pre1845.php">Sealings Before 1845</a>: List of all time and eternity sealings before 12/10/1845.</li>
	<li><a href="sealings_pre1846.php">Sealings Before 1846</a>: List of all time and eternity sealings before 3/1/1846.</li>
	<li><a href="sealings_44-45.php">Sealings Between 1844-1845</a>: List of all time and eternity sealings after 7/1/1844 but before 12/10/1845.</li>
	<li><a href="sealings_45-46.php">Sealings Between 1845-1846</a>: List of all time and eternity sealings after 12/10/1845 but before 3/1/1846.</li>
	<li><a href="sealings_46-52.php">Sealings Between 1846-1852</a>: List of all time and eternity sealings after 3/1/1846 but before 8/31/1852.</li>
	<li><a href="plural_before_1846.php">Plural (Male) Marriages Before Dec 10, 1845</a>: List of all men and their plural wives before Dec 10, 1845.</li>
	<li><a href="plural_before_1852.php">Plural (Male) Marriages Before 1852</a>: List of all men and their plural wives before 1852.</li>
	<li><a href="plural_1852.php">Plural (Male) Marriages Before 1852 (No Duplicates)</a>: List of all men and their plural wives before 1852. <em>All duplicate sealings and marriages to the same wife have been removed.</em></li>
	<li><a href="plural_women_before_1852.php">Plural (Female) Marriages Before 1852</a>: List of all women and their plural husbands before 1852.</li>
	<li><a href="plural_women_1852.php">Plural (Female) Marriages Before 1852 (No Duplicates)</a>: List of all women and their plural husbands before 1852. <em>All duplicate sealings and marriages to the same wife have been removed.</em></li>
</ul>

<h2>Static Views of Marriage Graphs</h2>
<p>These PDFs show the state of the Nauvoo marriage/lineage graph at a specific point in time.</p>
<ul>
	<li><a href="pdf/1844-1-19-pat.pdf">Patriarchal, before July 1844</a>: Patriarchal marriages before July 1844.  This graph as one degree of separation from the AQ, and has 19 connected components.</li>
	<li><a href="pdf/1845-1-15-pat.pdf">Patriarchal, before Dec 10, 1845</a>: Patriarchal marriages before December 10, 1845.  This graph as one degree of separation from the AQ, and has 15 connected components.</li>
	<li><a href="pdf/1846-1-13-pat.pdf">Patriarchal, before March 1846</a>: Patriarchal marriages before March 1846.  This graph as one degree of separation from the AQ, and has 13 connected components.</li>
</ul>



<h2>REST API Service</h2>
<p>There is an api service available for accessing the data needed for visualizations.  It always creates a JSON response.</p>
<ul>
	<li><a href="../api/marriages_by_man.php">Marriages by Man</a>: Takes a husband's id and returns a JSON object containing all his wives, their children, and relationships between wives and children.</li>
	<li><a href="../api/marriages_by_woman.php">Marriages by Woman</a>: Takes a wife's id and returns a JSON object containing all her husbands, their children, and relationships between husbands and children.</li>
	<li><a href="../api/marriages.php">Marriages</a>: Returns all marriages in the database as a JSON data object.</li>
	<li><a href="../api/people.php">People</a>: Returns all people in the database as a JSON data object.</li>
</ul>


<p>We have expanded the initial set of data from BYU's <a href="http://nauvoo.byu.edu">Nauvoo Community Project</a>, to include a richer set of data on the polygamous marriages of the Annointed Quorum and individuals linked to those members.  Below are access points to the original BYU data.</p>
<hr style="width:100%;text-align:center;border:1px solid black;"/>

</body>
</html>
