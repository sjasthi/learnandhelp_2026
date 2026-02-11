<?php
  $status = session_status();
  if ($status == PHP_SESSION_NONE) {
    session_start();
  }

  // Block unauthorized users from accessing the page
  if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] != 'admin') {
      http_response_code(403);
      die('Forbidden');
    }
  } else {
    http_response_code(403);
    die('Forbidden');
  }
 ?>

<!DOCTYPE html>
<script>
</script>
<html>
  <head>
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Administration</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&display=swap" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-1.11.3.min.js"></script>
    <link href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script>
    $(document).ready(function () {
      $('#classes_table thead tr').clone(true).appendTo( '#classes_table thead' );
      $('#classes_table thead tr:eq(1) th').each(function () {
      var title = $(this).text();
      $(this).html('<input type="text" placeholder="Search ' + title + '" />');
      });

      var table = $('#classes_table').DataTable({
         initComplete: function () {
             // Apply the search
             this.api()
                 .columns()
                 .every(function () {
                     var that = this;

                     $('input', this.header()).on('keyup change clear', function () {
                         if (that.search() !== this.value) {
                             that.search(this.value).draw();
                         }
                     });
                 });
             },
         });

      $('a.toggle-vis').on('click', function (e) {
      e.preventDefault();

      // Get the column API object
      var column = table.column($(this).attr('data-column'));

      // Toggle the visibility
      column.visible(!column.visible());
      });
     });
    </script>
  </head>
  <body>
  <?php include 'show-navbar.php'; ?>
  <?php show_navbar(); ?>
    <header class="inverse">
      <div class="container">
        <h1><span class="accent-text">Classes</span></h1>
      </div>
    </header>
      <h4>Add Class</h4>
    	<form action="update_classes.php" method="post" id="add_class">
       		<label>
           		<input type="text" name="name" placeholder="Class Name" required>
       		</label>
       		<br>
       		<label>
           		<textarea rows=5 cols=90 name="description" placeholder="Class Description" required></textarea>
       		</label>
       		<br><br>
          <label for="status">Status:</label><br>
            <select name="status" id="status" required>
              <option value="Proposed">Proposed</option>
              <option value="Approved">Approved</option>
              <option value="Inactive">Inactive</option>
            </select>
          <br>
       		<input type="hidden" name="action" value="add">
       		<input type="submit" value="Add" style="width: 15%">
    	</form>
	<!-- Jquery Data Table -->
    <div class="toggle_columns">
      Toggle column: <a class="toggle-vis" data-column="0">Class ID</a>
        - <a class="toggle-vis" data-column="1">Class Name</a>
        - <a class="toggle-vis" data-column="2">Description</a>
        - <a class="toggle-vis" data-column="3">Status</a>        
        - <a class="toggle-vis" data-column="4">Options</a>
    </div>
    <div style="padding-top: 10px; padding-bottom: 30px; width:90%; margin:auto; overflow:auto">
      <table id="classes_table" class="display compact">
        <thead>
          <tr>
            <th>Class ID</th>
            <th>Class Name</th>
            <th>Description</th>
            <th>Status</th>  <!-- Added Status -->
            <th>Options</th>
          </tr>
        </thead>
        <tbody>
          <!-- Populating table with data from the database-->
          <!-- Added re-direct on hitting 'Delete' - warning user they will be deleting record from database --> 
          <?php
            require 'db_configuration.php';
            // Create connection
            $conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
            // Check connection
            if ($conn->connect_error) {
              die("Connection failed: " . $conn->connect_error);
            }

            $sql = "SELECT * FROM classes";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
              // Create table with data from each row
              // Added "Status"
              while($row = $result->fetch_assoc()) {
				  echo "<tr><td>" . $row["Class_Id"]. "</td>
					    <td>" . $row["Class_Name"] . "</td>
					    <td>" . $row["Description"]. "</td>
              <td>" . $row["Status"]. "</td>
                <td>
                  <form action='admin_edit_class.php' method='POST'>
                    <input type='hidden' name='Class_Id' value='". $row["Class_Id"] . "'>
                    <input type='submit' id='admin_buttons' name='edit' value='Edit'/>
                  </form>
                  <form action='admin_delete_record_warning.php' method='POST'>
                    <input type='hidden' name='Class_Id' value='". $row["Class_Id"] . "'>
                    <input type='submit' id='admin_buttons' name='delete' value='Delete'/>
                  </form>
                </td>
                </tr>";
              }
            } else {
              echo "0 results";
            }
            $conn->close();
		?>
        </tbody>
      </table>
</div>
  </body>
</html>
