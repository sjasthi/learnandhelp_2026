<?php
function fetchRegistrationDetails($connection, $userId) {
    // SQL query to see registration history
    $sql = <<<SQL
        SELECT r.reg_id, b.batch_name, b.start_date, b.end_date, c.Class_Name
        FROM registrations r
        JOIN classes c ON c.Class_Id = r.Class_Id
        JOIN batch b ON r.batch_name = b.batch_name
        JOIN preferences p ON 1=1
        WHERE r.User_Id = $userId
        AND b.batch_name != p.value
        AND p.Preference_Name = 'Active Registration'
        ORDER BY b.end_date DESC;
    SQL;
    
    // Execute the query
    $result = mysqli_query($connection, $sql);
    
    // Check for errors in the query execution
    if (!$result) {
        echo "Error description: " . mysqli_error($connection);
        return;
    }
    
    // Check if there are results
    if (mysqli_num_rows($result) > 0) {
        echo "
            <!---Registration History--->
            <h3>Past Registration Details</h3><br>";
        echo "<div id='container_3'>";
        echo "<div id='accordion-container'>";
    
        // Loop through the result and populate the HTML
        while ($row = mysqli_fetch_assoc($result)) {
            $batch = htmlspecialchars($row['batch_name']);
            $reg_id = htmlspecialchars($row['reg_id']);
            $class_name = htmlspecialchars($row['Class_Name']);
            $start_date = htmlspecialchars($row['start_date']);
            $end_date = htmlspecialchars($row['end_date']);
            
            echo "
                <button class='accordion'>$batch</button>
                <div class='panel' >
                    <p><strong>Registration ID:</strong> $reg_id </p>
                    <p><strong>Class:</strong> $class_name </p>
                    <p><strong>Start Date:</strong> $start_date </p>
                    <p><strong>End Date:</strong> $end_date </p>
                </div>
                <br>
            ";
        }
    
        echo "</div>"; // Close accordion-container
        echo "</div>"; // Close container_3
        
        
        // Accordion menu functionality
        echo "
            <script>
            var acc = document.getElementsByClassName('accordion');
            for (var i = 0; i < acc.length; i++) {
                acc[i].addEventListener('click', function() {
                    this.classList.toggle('active');
                    var panel = this.nextElementSibling;
                    if (panel.style.display === 'block') {
                        panel.style.display = 'none';
                    } else {
                        panel.style.display = 'block';
                    }
                });
            }
            </script>
        ";
    } else {
        echo "<div id='container_3'>
            <p style='display: none'>No past registrations found.</p>
            </div>";
    }
}
?>
