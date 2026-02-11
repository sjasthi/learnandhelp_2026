<?php

// Print errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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
<html>
  <head>
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Administration</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&display=swap" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet" type="text/css" />
    <!-- DataTables Buttons CSS -->
    <link href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css" rel="stylesheet" type="text/css" />
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <!-- DataTables Buttons JS -->
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <!-- JSZip for Excel export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <!-- Buttons HTML5 export -->
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
      /* Action buttons styling */
      .action-btn {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        border: none;
        cursor: pointer;
        margin: 2px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        transition: all 0.3s ease;
      }
      
      .btn-update {
        background-color: #007bff;
        color: white;
      }
      
      .btn-update:hover {
        background-color: #0056b3;
        transform: scale(1.1);
      }
      
      .btn-delete {
        background-color: #dc3545;
        color: white;
      }
      
      .btn-delete:hover {
        background-color: #c82333;
        transform: scale(1.1);
      }
      
      .btn-password {
        background-color: #28a745;
        color: white;
      }
      
      .btn-password:hover {
        background-color: #1e7e34;
        transform: scale(1.1);
      }
      
      .btn-register {
        background-color: #ff6b35;
        color: white;
      }
      
      .btn-register:hover {
        background-color: #e55a2b;
        transform: scale(1.1);
      }
      
      /* Tooltip styling */
      .action-btn {
        position: relative;
      }
      
      .action-btn::after {
        content: attr(title);
        position: absolute;
        bottom: -25px;
        left: 50%;
        transform: translateX(-50%);
        background-color: #333;
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        white-space: nowrap;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s;
        z-index: 1000;
      }
      
      .action-btn:hover::after {
        opacity: 1;
      }

      /* Custom styling for DataTables buttons */
      .dt-buttons {
        margin-bottom: 10px;
        float: right;
        margin-left: 10px;
      }
      
      .dt-button {
        background-color: #28a745 !important;
        color: white !important;
        border: 1px solid #28a745 !important;
        padding: 8px 16px !important;
        border-radius: 4px !important;
        font-weight: 500 !important;
        margin-right: 5px !important;
        font-size: 14px !important;
      }
      
      .dt-button:hover {
        background-color: #1e7e34 !important;
        border-color: #1e7e34 !important;
      }

      /* DataTables wrapper styling */
      .dataTables_wrapper .dataTables_length {
        float: left;
      }
      
      .dataTables_wrapper .dataTables_filter {
        float: right;
        clear: none;
      }
      
      .dataTables_wrapper .dataTables_info {
        clear: both;
        float: left;
        padding-top: 8px;
      }
      
      .dataTables_wrapper .dataTables_paginate {
        float: right;
        padding-top: 8px;
      }
    </style>

    <script>
      function deleteUser(userId){
        var confirmation = confirm("Are you sure you want to delete this user?");
        if(confirmation){
          window.location.href = 'admin_deleteuser.php?id=' + userId;
        }
      }

      function changePassword(userId){
        window.location.href = 'admin_change_user_password.php?id=' + userId;
      }

      function registerStudent(userId){
        window.location.href = 'admin_register_student_form.php?id=' + userId;
      }

      $(document).ready(function () {
        // Clone header for search inputs
        $('#Blog_table thead tr').clone(true).appendTo('#Blog_table thead');
        $('#Blog_table thead tr:eq(1) th').each(function () {
          var title = $(this).text();
          if (title !== 'Actions') { // Don't add search to Actions column
            $(this).html('<input type="text" placeholder="Search ' + title + '" style="width:100%; box-sizing:border-box; padding:3px;" />');
          } else {
            $(this).html(''); // Empty for Actions column
          }
        });

        // Initialize DataTable
        var table = $('#Blog_table').DataTable({
          "lengthMenu": [[10,25,50,100,-1],[10,25,50,100,"All"]],
          "pageLength": 50,
          "order": [[ 0, "desc" ]], // Order by User_Id descending
          "dom": 'Blfrtip', // B = Buttons, l = length, f = filter, r = processing, t = table, i = info, p = pagination
          "buttons": [
            {
              extend: 'excel',
              text: '<i class="fas fa-file-excel"></i> Export to Excel',
              title: 'Users List - ' + new Date().toLocaleDateString(),
              filename: function() {
                return 'users_list_' + new Date().toISOString().slice(0,10);
              },
              exportOptions: {
                columns: [0, 1, 2, 3, 4, 5, 6, 7, 8] // Exclude Actions column (index 9)
              }
            }
          ],
          "columnDefs": [
            { "orderable": false, "targets": 9 } // Disable sorting on Actions column
          ],
          initComplete: function () {
            // Apply search functionality to each column
            this.api().columns().every(function () {
              var that = this;
              $('input', this.header()).on('keyup change clear', function () {
                if (that.search() !== this.value) {
                  that.search(this.value).draw();
                }
              });
            });
          }
        });

        // Column visibility toggle functionality
        $('a.toggle-vis').on('click', function (e) {
          e.preventDefault();
          var column = table.column($(this).attr('data-column'));
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
        <h1><span class="accent-text">Users List</span></h1>
      </div>
    </header>

    <?php if (!empty($_SESSION['flash'])): ?>
      <?php $flash = $_SESSION['flash']; unset($_SESSION['flash']); ?>
      <div class="flash <?= $flash['type'] === 'success' ? 'flash-success' : 'flash-error' ?>" style="margin:16px auto;max-width:90%;padding:12px 14px;border-radius:10px;border:1px solid #ddd;font-weight:600;<?= $flash['type'] === 'success' ? 'background:#f0fff0;border-color:#b2e2b2;' : 'background:#fff5f5;border-color:#f5b5b5;' ?>">
        <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8'); ?>
      </div>
    <?php endif; ?>
	<!-- Jquery Data Table -->

    <div class="toggle_columns">
      Toggle column: 
          <a class="toggle-vis" data-column="0">User_Id</a>
        - <a class="toggle-vis" data-column="1">First_Name</a>
        - <a class="toggle-vis" data-column="2">Last_Name</a>
        - <a class="toggle-vis" data-column="3">Email</a>
        - <a class="toggle-vis" data-column="4">Phone</a>
        - <a class="toggle-vis" data-column="5">Active</a>
        - <a class="toggle-vis" data-column="6">Role</a>
        - <a class="toggle-vis" data-column="7">Created_Time</a>
        - <a class="toggle-vis" data-column="8">Modified DateTime </a>
        
    </div>
    <!-- Create and Update User Buttons -->
    <div style="padding-top: 10px; padding-bottom: 30px; width:90%; margin:auto; overflow:auto">
    <button type="button" style="width:fit-content; height:44px; background-color:#99D930; color:white; border:solid 0px; border-radius:5px; padding:0 20px; margin-right:0;" onclick="location.href='admin_createuser.php'">Create User</button>
    </div>
    <div style="padding-top: 10px; padding-bottom: 30px; width:90%; margin:auto; overflow:auto">
      <table id="Blog_table" class="display compact" style="width:100%">
        <thead>
          <tr>
            <th>User_Id </th>
            <th>First_Name</th>
            <th>Last Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Active</th>
            <th>Role</th>
            <th>Created Date</th>
            <th>Last Modified</th>
            <!-- action buttons -->
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <!-- Populating table with data from the database-->
          <?php
            require 'db_configuration.php';
            // Create connection
            $conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
            // Check connection
            if ($conn->connect_error) 
            {
              die("Connection failed: " . $conn->connect_error);
            }
            $sql = "SELECT * FROM `users` ORDER BY User_Id DESC";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) 
            {
              // Create table with data from each row
              while($row = $result->fetch_assoc()) 
              {
                  echo "<tr>
                    <td>". $row["User_Id"]."</td>
                    <td>". $row["First_Name"]."</td> 
                    <td>". $row["Last_Name"]."</td> 
                    <td>". $row["Email"]."</td> 
                    <td>". $row["Phone"]."</td> 
                    <td>". $row["Active"]."</td> 
                    <td>". $row["Role"]."</td> 
                    <td>". $row["Modified_Time"]."</td> 
                    <td>". $row["Created_Time"]."</td> 
                    <td style='text-align: center;'>
                      <button class=\"action-btn btn-update\" title=\"Update User\" onclick=\"location.href='admin_updateuser.php?id=" . $row['User_Id'] . "'\">
                        <i class=\"fas fa-edit\"></i>
                      </button>
                      <button class=\"action-btn btn-password\" title=\"Change Password\" onclick=\"changePassword(" . $row['User_Id'] . ")\">
                        <i class=\"fas fa-key\"></i>
                      </button>
                      <button class=\"action-btn btn-register\" title=\"Register Student\" onclick=\"registerStudent(" . $row['User_Id'] . ")\">
                        <i class=\"fas fa-user-plus\"></i>
                      </button>
                      <button class=\"action-btn btn-delete\" title=\"Delete User\" onclick=\"deleteUser(" . $row['User_Id'] . ")\">
                        <i class=\"fas fa-trash\"></i>
                      </button>
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