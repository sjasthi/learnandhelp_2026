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
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Administration</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&display=swap" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">
    
    <!-- jQuery and DataTables Core -->
    <script src="https://code.jquery.com/jquery-1.11.3.min.js"></script>
    <link href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css" rel="stylesheet" type="text/css"/>
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    
    <!-- DataTables Buttons Extension -->
    <link href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.dataTables.min.css" rel="stylesheet" type="text/css"/>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>
    
    <script>
        $(document).ready(function () {
            $('#registration_table thead tr').clone(true).appendTo('#registration_table thead');
            $('#registration_table thead tr:eq(1) th').each(function () {
                var title = $(this).text();
                $(this).html('<input type="text" placeholder="Search ' + title + '" />');
            });

            var table = $('#registration_table').DataTable({
                "lengthMenu": [[10,25,50,100,-1],[10,25,50,100,"All"]],
                "pageLength": 50,
                "dom": 'Blfrtip', // This adds buttons to the layout
                "buttons": [
                    {
                        extend: 'csvHtml5',
                        text: 'Export CSV',
                        title: 'Registrations_Export',
                        exportOptions: {
                            columns: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11] // Exclude the Options column (index 0)
                        }
                    },
                    {
                        extend: 'excelHtml5',
                        text: 'Export Excel',
                        title: 'Registrations_Export',
                        exportOptions: {
                            columns: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11] // Exclude the Options column (index 0)
                        }
                    }
                ],
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

            // In-cell editing functionality
            $('#registration_table tbody').on('click', 'td.editable', function () {
                var cell = $(this);
                var originalValue = cell.text();
                var columnIndex = cell.index();
                
                // Create input element based on column type
                var inputElement;
                if (columnIndex === 9) { // Current Grade column
                    inputElement = $('<select class="cell-edit-input">' +
                        '<option value="">Select Grade</option>' +
                        '<option value="1">1</option>' +
                        '<option value="2">2</option>' +
                        '<option value="3">3</option>' +
                        '<option value="4">4</option>' +
                        '<option value="5">5</option>' +
                        '<option value="6">6</option>' +
                        '<option value="7">7</option>' +
                        '<option value="8">8</option>' +
                        '<option value="9">9</option>' +
                        '<option value="10">10</option>' +
                        '<option value="11">11</option>' +
                        '<option value="12">12</option>' +
                        '<option value="13">13</option>' +
                        '</select>');
                    inputElement.val(originalValue);
                } else if (columnIndex === 10) { // Payment Status column
                    inputElement = $('<select class="cell-edit-input">' +
                        '<option value="pending">pending</option>' +
                        '<option value="paid">paid</option>' +
                        '<option value="free">free</option>' +
                        '<option value="partial">partial</option>' +
                        '<option value="partial">void</option>' +
                        '<option value="partial">withdrawn</option>' +
                        '</select>');
                    inputElement.val(originalValue);
                } else { // Payment Amount column
                    inputElement = $('<input type="number" step="0.01" class="cell-edit-input" value="' + originalValue + '">');
                }
                
                cell.html(inputElement);
                inputElement.focus();
                
                // Handle save on blur or enter
                inputElement.on('blur keypress', function (e) {
                    if (e.type === 'blur' || e.which === 13) {
                        var newValue = $(this).val();
                        var regId = cell.closest('tr').find('td').eq(1).text(); // Reg_Id is in column 1
                        
                        // Update the cell display
                        cell.html(newValue);
                        
                        // Send AJAX request to update database
                        var columnName;
                        if (columnIndex === 9) {
                            columnName = 'current_grade';
                        } else if (columnIndex === 10) {
                            columnName = 'payment_status';
                        } else if (columnIndex === 11) {
                            columnName = 'payment_amount';
                        }
                        
                        $.ajax({
                            url: 'admin_registrations_in_cell_update.php',
                            method: 'POST',
                            data: {
                                reg_id: regId,
                                column: columnName,
                                value: newValue
                            },
                            success: function (response) {
                                console.log('Updated successfully');
                                // Optionally show a success message
                                cell.addClass('updated');
                                setTimeout(function() {
                                    cell.removeClass('updated');
                                }, 2000);
                            },
                            error: function () {
                                console.log('Update failed');
                                cell.html(originalValue); // Revert on error
                                alert('Failed to update. Please try again.');
                            }
                        });
                    }
                });
            });
        });
    </script>
    
    <style>
        /* Style the export buttons */
        .dt-buttons {
            margin-bottom: 10px;
        }
        
        .dt-button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 8px 16px;
            margin-right: 5px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .dt-button:hover {
            background-color: #0056b3;
        }

        /* Editable cell styles */
        .editable {
            cursor: pointer;
            background-color: #f8f9fa;
            border: 1px solid transparent;
            padding: 8px;
        }
        
        .editable:hover {
            background-color: #e9ecef;
            border: 1px solid #007bff;
        }
        
        .cell-edit-input {
            width: 100%;
            border: 2px solid #007bff;
            padding: 4px;
            border-radius: 3px;
            font-size: 14px;
        }
        
        .updated {
            background-color: #d4edda !important;
            animation: highlight 2s ease-in-out;
        }
        
        @keyframes highlight {
            0% { background-color: #d4edda; }
            100% { background-color: #f8f9fa; }
        }
    </style>
</head>
<body>
<?php include 'show-navbar.php'; ?>
<?php show_navbar(); ?>
<header class="inverse">
    <div class="container">
        <h1><span class="accent-text">Registrations</span></h1>
    </div>
</header>
<!-- Jquery Data Table -->
<!--<div class="toggle_columns">-->
<!--    Toggle column:-->
<!--    <a class="toggle-vis" data-column="0">Reg_Id</a> --->
<!--    <a class="toggle-vis" data-column="1">Sponsor1 Name</a> --->
<!--    <a class="toggle-vis" data-column="2">Sponsor1 Email</a> --->
<!--    <a class="toggle-vis" data-column="3">Sponsor1 Phone Number</a> --->
<!--    <a class="toggle-vis" data-column="4">Sponsor2 Name</a> --->
<!--    <a class="toggle-vis" data-column="5">Sponsor2 Email</a> --->
<!--    <a class="toggle-vis" data-column="6">Sponsor2 Phone Number</a> --->
<!--    <a class="toggle-vis" data-column="7">Student Name</a> --->
<!--    <a class="toggle-vis" data-column="8">Student Email</a> --->
<!--    <a class="toggle-vis" data-column="9">Student Phone Number</a> --->
<!--    <a class="toggle-vis" data-column="10">Class</a> --->
<!--    <a class="toggle-vis" data-column="11">Date Modified</a> --->
<!--    <a class="toggle-vis" data-column="12">Date Created</a> --->
<!--    <a class="toggle-vis" data-column="13">Payment ID</a> --->
<!--</div>-->
<div style="padding-top: 10px; padding-bottom: 30px; width:90%; margin:auto; overflow:auto">
    <table id="registration_table" class="display compact">
        <thead>
        <tr>
            <th>Options</th>
            <th>Reg Id</th>
            <th>Sponsor1 Name</th>
            <th>Sponsor1 Email</th>
            <th>Sponsor1 Phone Number</th>
            <th>Student Name</th>
            <th>Student Email</th>
            <th>Student Phone Number</th>
            <th>Class</th>
            <th>Current Grade</th>
            <th>Payment Status</th>
            <th>Payment Amount</th>
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

        $sql = "SELECT * FROM registrations Natural Join classes order by Reg_Id DESC";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            // Create table with data from each row
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                <td>
                  <form action='admin_registrations_edit.php' method='POST'>
                    <input type='hidden' name='Reg_Id' value='" . $row["Reg_Id"] . "'>
                    <input type='submit' id='admin_buttons' name='edit' value='Edit'/>
                  </form>
                  <form action='admin_registrations_delete.php' method='POST'>
                    <input type='hidden' name='Reg_Id' value='" . $row["Reg_Id"] . "'>
                    <input type='submit' id='admin_buttons' name='delete' value='Delete'/>
                  </form>
                </td>
                <td>" . $row["Reg_Id"] . "</td>
                <td>" . $row["Sponsor1_Name"] . "</td>
                <td>" . $row["Sponsor1_Email"] . "</td>
                <td>" . $row["Sponsor1_Phone_Number"] . "</td>
                <td>" . $row["Student_Name"] . "</td>
                <td>" . $row["Student_Email"] . "</td>
                <td>" . $row["Student_Phone_Number"] . "</td>
                <td>" . $row["Class_Name"] . "</td>
                <td class='editable'>" . ($row["current_grade"] ?? '') . "</td>
                <td class='editable'>" . $row["payment_status"] . "</td>
                <td class='editable'>" . $row["payment_amount"] . "</td>
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