<?php
require 'db_configuration.php';

  $status = session_status();
  if ($status == PHP_SESSION_NONE) {
    session_start();

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
  }
  
  function fill_book_form($id){
	if ($id != null) {   	
		$connection = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);

  		if ($connection === false) {
    		die("Failed to connect to database: " . mysqli_connect_error());
  		}
  		$sql = "SELECT * FROM books WHERE id = '$id'";
  		$row = mysqli_fetch_array(mysqli_query($connection, $sql));
  		$author = $row["author"];
  		$available = $row["available"];
		$gradelevel = $row["grade_level"];
		$id = $row["id"];
  		$image = $row["image"];
  		$numpages = $row["numPages"];
  		$price = $row["price"];
  		$publisher = $row["publisher"];
  		$publishyear = $row["publishYear"];
  		$title = $row["title"];
	} else {
  		$author = "";
  		$available = "";
  		$gradelevel = "";
  		$id = "";
  		$image = "";
  		$numpages = "";
  		$price = "";
  		$publisher = "";
  		$publishyear = "";
  		$title = "";
	}


	echo "<img id=\"book_image\" src='" . $image . "' onerror=\"src='images/books/default.png'\" loading='lazy'><br>
    <div id=\"container_2\">
  	<form id=\"survey-form\" action=\"book_submit_form.php\" method = \"post\" enctype=\"multipart/form-data\">
    <input type='hidden' name='id' value=$id>
	<label id=\"title-label\">Title</label><br>
	<input type=\"text\" id=\"title\" name=\"title\" class=\"form\" value=\"$title\" required><br><!--title-->
	<label id=\"author-label\">Author</label><br>
	<input type=\"text\" id=\"author\" name=\"author\" class=\"form\" value=\"$author\" required><br><!--author-->
    <label id=\"grade-level-label\">Grade Level</label><br> <!--make this multi select to format the comma separators etc-->
	<select id=\"gradelevel-dropdown\" name=\"gradelevel[]\" multiple required><!--gradelevel-->
   	<option disabled value>Select Grade Level</option>
   	<option value='High School' ";
   	if (str_contains(strtolower($gradelevel), "high school"))
       	echo "selected";
      	echo  ">High School</option>
   	<option value='Primary School Upper' ";
   	if (str_contains(strtolower($gradelevel), "primary school upper"))
       	echo "selected";
      	echo ">Primary School Upper</option>
  	<option value='Primary School Lower' ";
       	if (str_contains(strtolower($gradelevel), "primary school lower"))
       	echo "selected";
      	echo ">Primary School Lower</option>
  	<option value='Other' ";
       	if (str_contains(strtolower($gradelevel), "other"))
       	echo "selected";
      	echo ">Other</option>
	</select>
    <label id=\"price-label\">Price</label><br>
	<input type=\"text\" id=\"price\" name=\"price\" class=\"form\" value=\"$price\" required><!--price-->
    </div>
	
	<div id=\"right\">
	<label id=\"publisher-label\">Publisher</label><br>
    <input type=\"text\" id=\"publisher\" name=\"publisher\" class=\"form\" value=\"$publisher\" required><br><!--publisher-->
	<label id=\"publishyear-label\">Published Year</label><br>
	<input type=\"text\" id=\"publishyear\" name=\"publishyear\" class=\"form\" value=\"$publishyear\"><br><!--publishyear-->
    <label id=\"numpages-label\">Page Count</label><br>
    <input type=\"text\" id=\"numpages\" name=\"numpages\" class=\"form\" value=\"$numpages\"><br><!--numpages-->
	<label id=\"available-label\">Available</label><br>
	<select id=\"available-dropdown\" name=\"available\" required><!--available-->
   	<option disabled value>Select 1 for availble</option>
   	<option value='1' ";
   	if ($available == '1')
       	echo "selected";
      	echo  ">1</option>
   	<option value='0' ";
   	if ($available == '0')
       	echo "selected";
      	echo ">0</option>
	</select></br>
	<input type=\"hidden\" id=\"image\" name=\"image\" class=\"form\" value=\"$image\">
    </div>";
}
?>


