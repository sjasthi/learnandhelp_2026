<?php
  $status = session_status();
  if ($status == PHP_SESSION_NONE) {
    session_start();
  }
/*
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
 */
  require 'db_configuration.php';
  // Create connection
  $conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
  // Check connection
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }

  $sql = "SELECT * FROM books ORDER BY id;";
  $result = $conn->query($sql);
  $resultString = "";
  if ($result->num_rows > 0) {
    // Create table with data from each row
    while($row = $result->fetch_assoc()) {
      $Status = $row["available"]=='0'?'Not Available':'Available';
		$resultString .= "<tr>
               <td>" . $row["grade_level"] . "</td>
               <td id='book_image'><img src='" . $row["image"] . "' onerror=\"src='images/books/default.png'\" loading='lazy'></td>
               <td>" . $row["title"]. "</td>
               <td>" . $row["author"]. "</td>
               <td>" . $row["publisher"]. "</td>
               <td>" . $row["publishYear"]. "</td>
			   <td>" . $row["numPages"]. "</td>";
      			if (isset($_SESSION['role']) AND $_SESSION['role'] == 'admin') { 
			    $resultString .= "<td>" . $row["price"]. "</td>";
				}
			   $resultString .= "<td>" . $Status. "</td>";

      		if (isset($_SESSION['role']) AND $_SESSION['role'] == 'admin') 
      		{

               $resultString .= "<td style='min-width: 120px;'>
                 <form action='book_edit.php' method='post' enctype='multipart/form-data'>
                    <input type='hidden' name='book_image' value='".$row["image"]. "'>
                   	<input type='hidden' name='book_id' value='".$row['id']."'>
                	<input type='submit' id='admin_buttons' name='edit_book' value='Edit Book'>
				</form>
				<form action='book_delete.php' method='POST'>
                    <input type='hidden' name='book_image' value='".$row["image"]."'>
                    <input type='hidden' name='book_id' value='".$row["id"]."'>
                    <input type='submit' id='admin_buttons' name='delete_book' value='Delete Book'/>
                </form>
				</td>";
			}
	        $resultString .= "</tr>";
      }
  	}

  echo json_encode(["data" => $resultString]);

  $conn->close();
  ?>
