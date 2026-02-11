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

//Function to get profile image
function get_profile_image($id)
{
  $image_name = glob('schools/' . $id . '/profile_image.*');
  // should only be one file found, if there are two profile_image files
  // with different extensions something is wrong.  If there is no profile
  // image or more than one default to the admin_icons school icon.
  if (count($image_name) == 1) {
    return $image_name[0];
  } else {
    return "images/admin_icons/school.png";
  }
}

//$School_Id = $_GET['id']


?>

<!DOCTYPE html>
<html>

<head>
  <link rel="icon" href="images/icon_logo.png" type="image/icon type">
  <title>Admin Schools</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&display=swap" rel="stylesheet">
  <link href="css/main.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css" rel="stylesheet" type="text/css" />
  <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
  <script>
    $(document).ready(function() {
      $('#schools_table thead tr').clone(true).appendTo('#schools_table thead');
      $('#schools_table thead tr:eq(1) th').each(function() {
        var title = $(this).text();
        $(this).html('<input type="text" placeholder="Search ' + title + '" />');
      });

      var table = $('#schools_table').DataTable({
        initComplete: function() {
          // Apply the search
          this.api()
            .columns()
            .every(function() {
              var that = this;

              $('input', this.header()).on('keyup change clear', function() {
                if (that.search() !== this.value) {
                  that.search(this.value).draw();
                }
              });
            });
        },
      });

      $('a.toggle-vis').on('click', function(e) {
        e.preventDefault();

        // Get the column API object
        var column = table.column($(this).attr('data-column'));

        // Toggle the visibility
        column.visible(!column.visible());
      });
    });
  </script>
  <script>
  function updateValue(element,column,id){
        var value = element.innerText;
        $.ajax({
            url:'admin_edit_school_in_place.php',
            type: "POST",
            data:{
                value: value,
                column: column,
                id: id
            },
            success:function(php_result){
				console.log(php_result);	
            }
        });
    }
  </script>
</head>

<body>
  <?php include 'show-navbar.php'; ?>
  <?php show_navbar(); ?>
  <header class="inverse">
    <div class="container">
      <h1><span class="accent-text">Schools</span></h1>
    </div>
  </header>
  <h3>Add New School</h3>
  <form action="admin_edit_school.php" method="POST" id="add_school">
    <input type="hidden" name="id" value="">
    <input type="hidden" name="action" value="Add_School">
    <input type="submit" value="Add School" style="width: 15%">
  </form>
  <!-- Jquery Data Table -->
  <div class="toggle_columns">
    Toggle column: <a class="toggle-vis" data-column="0">Options</a>
    - <a class="toggle-vis" data-column="1">Profile</a>
    - <a class="toggle-vis" data-column="2">Id</a>
    - <a class="toggle-vis" data-column="3">Name</a>
    - <a class="toggle-vis" data-column="4">Type</a>
    - <a class="toggle-vis" data-column="5">Category</a>
    - <a class="toggle-vis" data-column="6">Grade Level Start</a>
    - <a class="toggle-vis" data-column="7">Grade Level End</a>
    - <a class="toggle-vis" data-column="8">Current Enrollment</a>
    - <a class="toggle-vis" data-column="9">Address Text</a>
    - <a class="toggle-vis" data-column="10">State Name</a>
    - <a class="toggle-vis" data-column="11">State Code</a>
    - <a class="toggle-vis" data-column="12">Pin Code</a>
    - <a class="toggle-vis" data-column="13">Contact Name</a>
    - <a class="toggle-vis" data-column="14">Contact Designation</a>
    - <a class="toggle-vis" data-column="15">Contact Phone</a>
    - <a class="toggle-vis" data-column="16">Contact Email</a>
    - <a class="toggle-vis" data-column="17">Status</a>
    - <a class="toggle-vis" data-column="18">Notes</a>
    - <a class="toggle-vis" data-column="19">Referenced By</a>
    - <a class="toggle-vis" data-column="20">Supported By</a>
    <!-- - <a class="toggle-vis" data-column="19">Options</a> -->
  </div>
  <div style="padding-top: 10px; padding-bottom: 30px; width:95%; margin:auto; overflow:auto">
    <table id="schools_table" class="display compact">
      <thead>
        <tr>
          <th>Options</th>
          <th>Profile</th>
          <th>Id</th>
          <th>Name</th>
          <th>Type</th>
          <th>Category</th>
          <th>Grade Level Start</th>
          <th>Grade Level End</th>
          <th>Current Enrollment</th>
          <th>Address Text</th>
          <th>State Name</th>
          <th>State Code</th>
          <th>Pin Code</th>
          <th>Contact Name</th>
          <th>Contact Designation</th>
          <th>Contact Phone</th>
          <th>Contact Email</th>
          <th>Status</th>
          <th>Notes</th>
          <th>Referenced By</th>
          <th>Supported By</th>
        </tr>
      </thead>
      <tbody>
        <!-- Populating table with data from the database-->
        <?php
        require 'db_configuration.php';
        // Create connection
        $conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
        // Check connection
        if ($conn->connect_error) {
          die("Connection failed: " . $conn->connect_error);
        }

        $sql = "SELECT * FROM schools";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
          $time = time();
          // Create table with data from each row
          while ($row = $result->fetch_assoc()) {
            // $time = time();
            $profile_image = get_profile_image($row["id"]);
			$id = $row["id"];
			echo "
            <tr>
              <td>
                <form action='admin_edit_school.php' method='POST'>
                    <input type='hidden' name='id' value='$id'>
                    <input type='submit' id='admin_buttons' name='edit' value='Edit'/>
                </form>
                <form action='admin_delete_school.php' method='POST'>
                    <input type='hidden' name='id' value='$id'>
                    <input type='submit' id='admin_buttons' name='delete' value='Delete'/>
                </form>
              </td>
              <td id=\"school_icons\"class=\"school_icon\"><img src=\"$profile_image?v=$time\" alt=\"school image\"></td>
			";
			// Allow for "in-cell" editing of the table
			foreach ($row as $key=>$value) {
			  echo "
			  <td><div contenteditable=\"true\" onBlur=\"updateValue(this,'$key','$id')\">$value</div></td>
			  ";
			}
            echo "</tr>";
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