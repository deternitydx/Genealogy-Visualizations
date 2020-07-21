<html>
    <head>
        <title>Nauvoo - Brown Listing</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="css/bootstrap.css" rel="stylesheet" media="screen">
        <link href="css/form.css" rel="stylesheet" media="screen">
        <link href="css/jquery.fancybox.css" rel="stylesheet" media="screen">
        <!-- DataTables CSS -->
        <link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.2/css/jquery.dataTables.css"/>
        <link rel="stylesheet" type="text/css" href="css/styles.css" media="all">
  
        <!-- jQuery -->
        <script type="text/javascript" charset="utf8" src="../js/jquery-2.1.1.js"></script>
          
        <!-- DataTables -->
        <script type="text/javascript" charset="utf8" src="//cdn.datatables.net/1.10.2/js/jquery.dataTables.js"></script>

<script>
var QueryString = function () {
  // This function is anonymous, is executed immediately and 
  // the return value is assigned to QueryString!
  var query_string = {};
  var query = window.location.search.substring(1);
  var vars = query.split("&");
  for (var i=0;i<vars.length;i++) {
    var pair = vars[i].split("=");
    	// If first entry with this name
    if (typeof query_string[pair[0]] === "undefined") {
      query_string[pair[0]] = pair[1];
    	// If second entry with this name
    } else if (typeof query_string[pair[0]] === "string") {
      var arr = [ query_string[pair[0]], pair[1] ];
      query_string[pair[0]] = arr;
    	// If third or later entry with this name
    } else {
      query_string[pair[0]].push(pair[1]);
    }
  } 
    return query_string;
} ();

$(document).ready( function () {
    var dt = $('#datatable').DataTable( {
        paging: true, 
        ajax: "../api/brown.php", 
        deferRender: true, 
        saveState: true, 
        order: [[ 0, "asc" ]],
        "createdRow": function (row, data, index) {
            if (data[4] == "In Progress")
                $(row).addClass('inProgress');
            if (data[4] == "Done")
                $(row).addClass('done');
        }
    });
} );
</script>
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
                    <h1>Brown Entries</h1>
                </div><!-- page-header -->

<?php
echo "<table id='datatable' class='display listing'>";
echo "<thead><tr><th>Name</th><th>Birth Date</th><th>Brown Context</th><th><abbr title=\"Whether we think the UVA Person attached to this particular Brown ID is an exact match (exact), a probable match but should be double-checked (partial), or didn't have a match so we created a new person (unmatched)\">UVA ID Match</abbr></th><th>Progress</th></tr></thead>";
echo "</table>";
?>
</body>
</html>
