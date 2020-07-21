<html>
<head>
<title>Search People</title>
<!-- DataTables CSS -->
<link rel="stylesheet" type="text/css" href="../css/style.css"/>
<link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.2/css/jquery.dataTables.css"/>
<link rel="stylesheet" href="../css/jquery-ui.css" />
  
<!-- jQuery -->
<script type="text/javascript" src="../js/jquery-2.1.1.js"></script>
<script src="../js/jquery-ui.js"></script>
  
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
    
$(function() {
    $( "#dialog" ).dialog({
      autoOpen: false,
      width: 1024,
      height: 700,
      modal: true,
      show: {
        effect: "fade",
        duration: 500
      },
      hide: {
        effect: "fade",
        duration: 500
      }
    });
 
  });

var cdt = null;

function show_children(id, name){
    if (cdt != null) cdt.destroy();
    cdt = $('#children').DataTable( {paging: false, ajax: "../api/search.php?type=children&q=" + id, deferRender: true, saveState: true});
	// open the dialog window with jQuery
	$("#dialog").dialog("option", "title", "Children of " + name.replace(/&nbsp;/gi,''));
    $("#dialog").dialog("open");

}

$(document).ready( function () {
    var searchQuery = "?";
    if (QueryString.q) {
        searchQuery += "q=" + QueryString.q + "&type=name";
    }
    var dt = $('#datatable').DataTable( {paging: true, ajax: "../api/search.php" + searchQuery, deferRender: true, saveState: true});
    if (QueryString.parentSearch) {
        dt.column(8).search("^" + QueryString.parentSearch + "$", true).draw();
    }    
    if (QueryString.idSearch) {
        dt.column(0).search("^" + QueryString.idSearch + "$", true).draw();
    }    
    $('#datatable tbody').on( 'click', 'tr', function () {
        var data = dt.row($(this)).data();
        console.log(data);
        show_children(data[0], data[1] + " " + data[2] + " " + data[3]);
    });

} );
</script>
</head>
<body>
<h1>Search People</h1>
<p>Click on a name below to view a list of their children.</p>
<?php
echo "<table id='datatable' class='display'>";
echo "<thead><tr><th>ID</th><th>First</th><th>Middle</th><th>Last</th><th>Gender</th><th>Birth Date</th><th>Death Date</th><th>Private Notes</th><th>Public Notes</th><th>Chord Viz</th><th>Links</th></tr></thead>";
echo "</table>";
?>
<div id="dialog" class="dialog" style="overflow: auto">
<?php
echo "<table id='children' class='display'>";
echo "<thead><tr><th>ID</th><th>First</th><th>Middle</th><th>Last</th><th>Gender</th><th>Birth Date</th><th>Death Date</th><th>Private Notes</th><th>Public Notes</th><th>Chord Viz</th><th>Links</th></tr></thead>";
echo "</table>";
?>
</div>
</body>
</html>
