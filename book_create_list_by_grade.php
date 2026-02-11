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
?>

  <script type="text/javascript">
  	// select the contenct of the table cell for book quantity when the cell gets focus 
  	function select_all(el) {
        var body = document.body, range, sel;
        if (document.createRange && window.getSelection) {
            range = document.createRange();
            sel = window.getSelection();
            sel.removeAllRanges();
            try {
                range.selectNodeContents(el);
                sel.addRange(range);
            } catch (e) {
                range.selectNode(el);
                sel.addRange(range);
            }
        } else if (body.createTextRange) {
            range = body.createTextRange();
            range.moveToElementText(el);
            range.select();
		}
	}

    // convert the table into a JSON object that can be passed to the receipt page
	function get_table_rows(receipt_type) {
        var table = document.getElementById("selection_table");
        var header = [];
        var rows = [];
 
		// create the keys based on the header
		for (var i = 0; i < table.rows[0].cells.length; i++) {
            header.push(table.rows[0].cells[i].innerHTML);
		}

 		// create the data based on the rows
        for (var i = 1; i < table.rows.length; i++) {
            var row = {};
            for (var j = 0; j < table.rows[i].cells.length; j++) {
                row[header[j]] = table.rows[i].cells[j].innerHTML;
			}
            rows.push(row);
		}
		// set the value of our hidden input field in the form to the JSON data
		if(receipt_type == 1) document.getElementById("selected_books1").value = JSON.stringify(rows);
		else if (receipt_type == 2) document.getElementById("selected_books2").value = JSON.stringify(rows);
		else if (receipt_type == 3) document.getElementById("selected_books3").value = JSON.stringify(rows);
		else document.getElementById("selected_books4").value = JSON.stringify(rows);
    }  
</script>	  

<!DOCTYPE html>
<html>
  <head>
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Learn and Help</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&display=swap" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">
     <script src="https://code.jquery.com/jquery-1.11.3.min.js"></script>
	 <style>
        .form-container {
          display: inline-block;
          margin-right: 10px;
        }
    </style>
  </head>
  <body>
  <?php include 'show-navbar.php'; ?>

  <?php show_navbar(); ?>
  <header class="inverse">
      <div class="container">
        <h1><span class="accent-text">Books Selected</span></h1>
      </div>
  </header>
  <div id="selection">
  	<form method="POST" action="books.php">
    	<input type="submit" value="Return to Books">
	</form>
	<?php if (isset($_SESSION['role']) AND $_SESSION['role'] == 'admin') { ?> 
  	<h3><span class="accent-text">Generate An Invoice</span></h3>
    <?php } else { ?>
  	<h3><span class="accent-text">Create a Book Request</span></h3>
    <?php } ?>
  <table id="selection_table">
      <thead>
        <tr style="font-weight:bold; font-size:15px">
          <th style='display:none'>Image</th>
          <th align='left'>Line #</th>
          <th align='left'>Book ID</th>
          <th align='left'>Title</th>
          <th align='left'>Grade Level</th>
          <th align='left'>Publisher</th>
  	<?php if (isset($_SESSION['role']) AND $_SESSION['role'] == 'admin') { ?>
          <th class='item_price' align='right'>Price</th>
	<?php } ?>
	      <th class='item_quantity' align='right'>Quantity</th>
        </tr>
      </thead>
      <tbody>
        <?php
          // Get all books matching
          require 'db_configuration.php';
          // Create connection
          $conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
          // Check connection
          if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
          }

          $sql = "SELECT * FROM books WHERE (LOWER(grade_level) LIKE ";

          if (isset($_POST['high_school'])) 
          {
            $sql .= '"high school"';
            if (isset($_POST['primary_school_upper'])) {
              $sql .= ' OR LOWER(grade_level) LIKE "upper primary school"';
            }
            if (isset($_POST['primary_school'])) {
              $sql .= ' OR LOWER(grade_level) LIKE "primary school"';
            }
          } elseif (isset($_POST['primary_school_upper'])) {
            $sql .= '"upper primary school"';
            if (isset($_POST['primary_school'])) {
              $sql .= ' OR LOWER(grade_level) LIKE "primary school"';
            }
          } elseif (isset($_POST['primary_school'])) {
            $sql .= '"primary school"';
          }
          $sql .= ") AND available = 1";
          //echo $sql;
          $result = $conn->query($sql);
          if ($result->num_rows > 0) {
            // Create table with data from each row
			$count = 0;  
			while($row = $result->fetch_assoc()) {
				// the quantity column in the table is editable it is not an input field, parsing innerHTML to get
				// the table data when creating the JSON data does not work if there is an INPUT type of element
				// in the table
				$count += 1;
				echo "<tr style='font-size:12px; font-weight:bold'>
					  <td style='display:none'>".$row['image']."</td>
					  <td align='left'>".$count."</td>
                      <td align='left'>".$row['id']."</td>
                      <td align='left'>".$row['title']."</td>
                      <td align='left'>".$row['grade_level']."</td>
					  <td align='left'>".$row['publisher']."</td>";
  					  if (isset($_SESSION['role']) AND $_SESSION['role'] == 'admin') {
						echo "<td align='right'>".$row['price']."</td>";
					  }
                	echo "<td align='right' contentEditable='true' tabindex='0' onfocusin='select_all(this);'>1</td>
                      </tr>";
			}
		  }
       ?>
       </tbody>
  	<?php if (isset($_SESSION['role']) AND $_SESSION['role'] == 'admin') { ?>
	    <form action="books_receipt_with_price.php" method="post" onsubmit="get_table_rows(1);" target="_blank">
          <input type="hidden" id="selected_books1" name="selected_books" value=""> <!-- value is set by the javascript -->
          <input type="submit" name="receipt_with_price" value="Billing Invoice: Text">
		</form>
		&nbsp;&nbsp;
   		<form action="books_receipt_without_price.php" method="post" onsubmit="get_table_rows(2);" target="_blank">
          <input type="hidden" id="selected_books2" name="selected_books" value=""> <!-- value is set by the javascript -->
          <input type="submit" name="receipt_without_price" value="Customer Invoice: Text">
	  	</form>
		&nbsp;&nbsp;
   	<?php } else { ?>
   		<form action="books_receipt_without_price.php" method="post" onsubmit="get_table_rows(2);" target="_blank">
          <input type="hidden" id="selected_books2" name="selected_books" value=""> <!-- value is set by the javascript -->
          <input type="submit" name="receipt_without_price" value="Book Request: Text">
	  	</form>
		&nbsp;&nbsp;
     <?php } ?>
  	<?php if (isset($_SESSION['role']) AND $_SESSION['role'] == 'admin') { ?>
      	<form action="books_visual_receipt_w_prices.php" method="post" onsubmit="get_table_rows(3);" target="_blank">
          <input type="hidden" id="selected_books3" name="selected_books" value=""> <!-- value is set by the javascript -->
          <input type="submit" name="visual_report" value="Billing Invoice: Visual">
	  	</form>
		&nbsp;&nbsp;
      	<form action="books_visual_receipt_no_prices.php" method="post" onsubmit="get_table_rows(4);" target="_blank">
          <input type="hidden" id="selected_books4" name="selected_books" value=""> <!-- value is set by the javascript -->
          <input type="submit" name="visual_report" value="Customer Invoice: Visual">
	  	</form>
	<?php } else { ?>
      	<form action="books_visual_receipt_no_prices.php" method="post" onsubmit="get_table_rows(4);" target="_blank">
          <input type="hidden" id="selected_books4" name="selected_books" value=""> <!-- value is set by the javascript -->
          <input type="submit" name="visual_report" value="Book Request: Visual">
	  	</form>
     <?php } ?>
		<br>Click on quantity to change.  Set to 0 to remove that book from selection.	
</div>
</body>
</html>
