<html>
<head>
<title>Places</title>
<!-- DataTables CSS -->
<link rel="stylesheet" type="text/css" href="../css/style.css"/>
<link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.2/css/jquery.dataTables.css"/>
  
<!-- jQuery -->
<script type="text/javascript" charset="utf8" src="http://code.jquery.com/jquery-1.11.1.min.js"></script>
  
<!-- DataTables -->
<script type="text/javascript" charset="utf8" src="//cdn.datatables.net/1.10.2/js/jquery.dataTables.js"></script>

</head>
<body>
<script>
$(document).ready( function () {
    $('#datatable').DataTable( {paging: true, ajax: "../api/places.php", deferRender: true});
} );
</script>
<h1>Places</h1>
<?php
echo "<table id='datatable' class='display'>";
echo "<thead><tr><th>ID</th><th>Display Name</th><th>Official Name</th></thead>";
echo "</table>";
?>
</body>
</html>
