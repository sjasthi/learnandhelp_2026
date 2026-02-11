<?php
// Connect to your database
$db_host = "localhost";
$db_name = "learn_and_help_db";
$db_user = "root";
$db_pass = "";

$conn = mysqli_connect ($db_host, $db_user, $db_pass, $db_name);
if(mysqli_connect_error()){
    echo mysqli_connect_error();
    exit;
}
echo "connected successfuly. ";

$sql = "SELECT Email FROM users";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Output data in the email row 
    echo "<form>";
    while($row = $result->fetch_assoc()) {
        echo "Email: " . $row["Email"] . "<br>";
    }
    echo "</form";
} else {
    echo "0 results";
}

$conn->close();
?>