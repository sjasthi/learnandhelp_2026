<?php
// Check if form is submitted
if(isset($_POST['submit'])) {
    // Check if all required fields are filled
    if(isset($_POST['instructor_ID']) && isset($_POST['first_name']) && isset($_POST['last_name']) && isset($_POST['bio_data'])) {
        // Assign form values to variables
        $instructor_ID = $_POST['instructor_ID'];
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $bio_data = $_POST['bio_data'];

        // Database connection
        require 'db_configuration.php'; // Include database configuration file
        $conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Check if new image file is uploaded
        if(isset($_FILES['new_image']) && $_FILES['new_image']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['new_image']['tmp_name'];
            $file_name = $_FILES['new_image']['name'];
            $file_type = $_FILES['new_image']['type'];

            // Upload directory
            $upload_dir = "uploads/";

            // Move uploaded file to the upload directory
            if(move_uploaded_file($file_tmp, $upload_dir . $file_name)) {
                // File uploaded successfully, update image path in the database
                $image_path = $upload_dir . $file_name;
                $sql = "UPDATE instructor SET First_name='$first_name', Last_name='$last_name', Bio_data='$bio_data', Image='$image_path' WHERE instructor_ID='$instructor_ID'";
            } else {
                // Error uploading file
                echo "Error uploading file.";
                exit();
            }
        } else {
            // No new image uploaded, update other fields except the image path
            $sql = "UPDATE instructor SET First_name='$first_name', Last_name='$last_name', Bio_data='$bio_data' WHERE instructor_ID='$instructor_ID'";
        }

        // Execute update query
        if ($conn->query($sql) === TRUE) {
            $conn->close();
            // Redirect back to instructors.php
            header("Location: instructors.php");
            exit();
        } else {
            echo "Error updating record: " . $conn->error;
        }

        // Close database connection
        $conn->close();
    } else {
        // Required fields are not filled
        echo "All fields are required.";
    }
} else {
    // Form not submitted
    echo "Form not submitted.";
}
?>
