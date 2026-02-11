<?php
$status = session_status();
if ($status == PHP_SESSION_NONE) {
   session_start();
}
require 'db_configuration.php';

$conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);

if ($conn->connect_error) {
   die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT * FROM `schools`;";
$result = $conn->query($sql);

$schools = [];
if ($result->num_rows > 0) {
   while ($row = $result->fetch_assoc()) {
       $schools[] = $row;
   }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <link rel="icon" href="images/icon_logo.png" type="image/icon type">
   <title>School Location</title>
   <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&display=swap" rel="stylesheet">
   <link href="css/main.css" rel="stylesheet">
   <style>
       .google-map {
           width: 100%;
           height: 100vh;
           position: relative;
           overflow: hidden;
       }

       .google-map iframe {
           position: absolute;
           top: -100px;
           left: 0;
           width: 100%;
           height: calc(100% + 200px);
           border: 0;
       }
   </style>
</head>
<body>
   <?php include 'show-navbar.php'; ?>
   <?php show_navbar(); ?>
   <header class="inverse">
       <div class="container">
           <h1><span class="accent-text">School location</span></h1>
       </div>
   </header>
   <div class="google-map">
       <iframe id="mapFrame" src="https://www.google.com/maps/d/u/0/embed?mid=1D7mwzxGUaJE1oQ1PUV_UK01dS1ljpuU&ehbc=2E312F" width="100%" height="100%" frameborder="0" allowfullscreen="" aria-hidden="false" tabindex="0"></iframe>
   </div>
  
   <script>
       let schools = <?php echo json_encode($schools); ?>;
       
       function initCustomMarkers() {
           let mapFrame = document.getElementById('mapFrame').contentWindow.document;
           let pinCount = {};
           let offsets = [
               {lat: 0.0001, lng: 0.0001},
               {lat: -0.0001, lng: 0.0001},
               {lat: 0.0001, lng: -0.0001},
               {lat: -0.0001, lng: -0.0001},
               {lat: 0.0000, lng: 0.0000}
           ];
           
           schools.forEach((school, index) => {
               const position = { 
                   lat: parseFloat(school.latitude) + offsets[index % offsets.length].lat, 
                   lng: parseFloat(school.longitude) + offsets[index % offsets.length].lng 
               };
               const pinCode = school.pin_code;

               if (pinCount[pinCode]) {
                   pinCount[pinCode].count++;
               } else {
                   pinCount[pinCode] = { count: 1, position };
               }

               let marker = document.createElement('div');
               marker.className = 'custom-marker';
               marker.style.position = 'absolute';
               marker.style.width = '10px';
               marker.style.height = '10px';
               marker.style.backgroundColor = getColorForSupportedBy(school.supported_by);
               marker.title = `PIN: ${pinCode}\nCount: ${pinCount[pinCode].count}`;
               marker.style.top = `${position.lat}px`; // Simplified for the example
               marker.style.left = `${position.lng}px`; // Simplified for the example

               mapFrame.body.appendChild(marker);
           });
       }

       

       window.addEventListener('load', () => {
           setTimeout(initCustomMarkers, 2000); // Allow some time for the iframe to load
       });
   </script>
</body>
</html>