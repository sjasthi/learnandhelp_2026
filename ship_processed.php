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
 }?>
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
       $('#Blog_table thead tr').clone(true).appendTo( '#Blog_table thead' );
       $('#Blog_table thead tr:eq(1) th').each(function () {
       var title = $(this).text();
       $(this).html('<input type="text" placeholder="Search ' + title + '" />');
       });
 
       var table = $('#Blog_table').DataTable({
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
         <h2><span class="accent-text">Shipping Result</span></h2>
       </div>
     </header>
     <?php
 require 'db_configuration.php';

$conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
            // Check connection
            if ($conn->connect_error) 
            {
              die("Connection failed: " . $conn->connect_error);
            }

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$bookId = $_POST['bookId'];
$numberOfBooks = $_POST['numberOfBooks'];
$shippingAddress = $_POST['shippingAddress'];

$sql = "INSERT INTO books_shipped (id, numberOfBooks, address) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sis", $bookId, $numberOfBooks, $shippingAddress);
$stmt->execute();

if (isset($_POST['shippingAddress'])) {

    if ($stmt->affected_rows > 0) {
        echo "</br>";
        echo "Book was shipped successfully!";
        exit();
    } else {
        echo "Error inserting shipment information: " . $stmt->error;
   
    } 
}else {
    
    echo "Please provide the address.";
}
    $stmt->close();
    mysqli_close($conn);
?>