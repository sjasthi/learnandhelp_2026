<?php 
// Start the session if it is not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Block unauthorized users from accessing the page
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    http_response_code(403);
    die('Forbidden');
}

// Include database configuration and connect to the database
require 'db_configuration.php';
$conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Fetch suggested schools - ordered by latest created first
$sql = "SELECT * FROM schools WHERE status = 'Proposed' ORDER BY id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Suggested Schools | Admin Review</title>
    
    <!-- Main CSS -->
    <link href="css/main.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- jQuery and DataTables JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/dataTables.bootstrap4.min.js"></script>
    
    <!-- DataTables Buttons Extension for Excel Export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/buttons.html5.min.js"></script>
    
    <style>
        :root { --accent:#99D930; }
        .accent-text { color: var(--accent); }

        /* Header banner */
        .intro-banner { 
            background:#1a1a1a; 
            color:#fff; 
            text-align:center; 
            padding:24px 20px 20px; 
        }
        .intro-banner h1 { 
            font-family:'Montserrat',sans-serif; 
            font-size:3rem; 
            font-weight:900; 
            margin:0; 
        }
        .intro-banner h1 .accent-text { color:var(--accent); }

        body {
            margin: 0;
            font-family: 'Montserrat', sans-serif;
            background: #f8f8f8;
            color: #252525;
            overflow-x: hidden;
        }

        .container {
            width: 95%;
            max-width: 100%;
            margin: 40px auto;
            padding: 0 20px;
        }

        .table-container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,.08);
            padding: 30px;
            overflow-x: auto;
        }

        .export-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .btn-export {
            background: var(--accent);
            color: #252525;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-export:hover {
            background: #8cc428;
            transform: translateY(-2px);
        }

        .page-size-control {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }

        .page-size-control select {
            padding: 8px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
        }

        /* DataTable styling - Full width */
        .dataTables_wrapper {
            margin-top: 20px;
            width: 100%;
        }

        table.dataTable {
            width: 100% !important;
            border-collapse: collapse;
            table-layout: auto;
        }

        table.dataTable thead th {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            color: #252525;
            font-weight: 700;
            padding: 16px 12px;
            border-bottom: 2px solid #dee2e6;
            text-align: left;
            white-space: nowrap;
        }

        table.dataTable tbody td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }

        table.dataTable tbody tr:hover {
            background: #f8f9fa;
        }

        /* Action buttons styling */
        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            min-width: 70px;
            height: 32px;
        }

        .btn-move {
            background: var(--accent);
            color: #252525;
        }

        .btn-move:hover {
            background: #8cc428;
            transform: translateY(-1px);
            text-decoration: none;
            color: #252525;
        }

        .btn-update {
            background: #007bff;
            color: white;
        }

        .btn-update:hover {
            background: #0056b3;
            transform: translateY(-1px);
            text-decoration: none;
            color: white;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background: #c82333;
            transform: translateY(-1px);
        }

        /* Column widths */
        .actions-col { width: 140px; min-width: 140px; }
        .school-name-col { min-width: 180px; }
        .contact-name-col { min-width: 140px; }
        .contact-mobile-col { min-width: 120px; }
        .commitment-col { min-width: 200px; }

        /* Stack action buttons vertically */
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 5px;
            width: 130px;
        }
        .action-buttons form {
            display: block;
            width: 100%;
        }
        .action-btn {
            width: 100%;
            box-sizing: border-box;
            justify-content: center;
            text-align: center;
        }

        /* Pending count badge */
        .pending-badge {
            display: inline-block;
            background: #e65100;
            color: #fff;
            border-radius: 20px;
            padding: 2px 14px;
            font-size: 1rem;
            font-weight: 700;
            margin-left: 12px;
            vertical-align: middle;
        }

        /* Truncate long commitment text */
        .commitment-text {
            max-width: 260px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: pointer;
        }
        .commitment-text:hover {
            white-space: normal;
            overflow: visible;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,.15);
            position: relative;
            z-index: 10;
            padding: 4px 6px;
            border-radius: 4px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .export-controls {
                flex-direction: column;
                align-items: stretch;
            }
            
            .table-container {
                padding: 15px;
            }
            
            table.dataTable thead th,
            table.dataTable tbody td {
                padding: 8px;
                font-size: 14px;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 4px;
                min-width: 80px;
            }

            .action-btn {
                min-width: 60px;
                font-size: 11px;
            }
        }

        /* Custom DataTables styling */
        .dataTables_length select,
        .dataTables_filter input {
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .dataTables_info,
        .dataTables_paginate {
            margin-top: 20px;
        }

        .paginate_button {
            padding: 8px 12px !important;
            margin: 0 2px !important;
        }

        .paginate_button.current {
            background: var(--accent) !important;
            color: #252525 !important;
            border: 1px solid var(--accent) !important;
        }

        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #666;
            font-size: 1.2rem;
        }

        /* Remove table width constraints */
        .dataTables_scrollX {
            overflow-x: auto;
        }
    </style>
</head>

<body>
    <?php
    include 'show-navbar.php';
    show_navbar();
    ?>
    
    <section class="intro-banner">
        <h1>Suggested <span class="accent-text">Schools</span>
            <?php if ($result->num_rows > 0): ?>
                <span class="pending-badge"><?= $result->num_rows ?> pending</span>
            <?php endif; ?>
        </h1>
    </section>

    <div class="container">
        <div class="table-container">
            <div class="export-controls">
                <button id="exportExcel" class="btn-export">
                    <i class="fas fa-file-excel"></i>
                    Export to Excel
                </button>
            </div>

            <?php if ($result->num_rows > 0): ?>
                <table id="suggestionsTable" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th class="actions-col">Actions</th>
                            <th class="school-name-col">School Name</th>
                            <th class="contact-name-col">Contact Name</th>
                            <th class="contact-mobile-col">Contact Mobile</th>
                            <th class="commitment-col">Commitment Statement</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="action-buttons">
                                        <form action="move_to_schools.php" method="post" 
                                              onsubmit="return confirm('Mark this school as Completed?');">
                                            <input type="hidden" name="school_id" value="<?= $row['id'] ?>">
                                            <button type="submit" class="action-btn btn-move" title="Mark as Completed">
                                                <i class="fas fa-check"></i>
                                                Mark as Completed
                                            </button>
                                        </form>
                                        
                                        <a href="update_suggestion.php?id=<?= $row['id'] ?>" 
                                           class="action-btn btn-update" title="Update Suggestion">
                                            <i class="fas fa-edit"></i>
                                            Update
                                        </a>
                                        
                                        <form action="delete_suggestion.php" method="post" 
                                              onsubmit="return confirm('Are you sure you want to delete this suggestion?');">
                                            <input type="hidden" name="school_id" value="<?= $row['id'] ?>">
                                            <button type="submit" class="action-btn btn-delete" title="Delete Suggestion">
                                                <i class="fas fa-trash"></i>
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= htmlspecialchars($row['contact_name']) ?></td>
                                <td><?= htmlspecialchars($row['contact_phone']) ?></td>
                                <td><div class="commitment-text" title="<?= htmlspecialchars($row['commitment_statement'] ?? '') ?>"><?= htmlspecialchars($row['commitment_statement'] ?? '—') ?></div></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-inbox" style="font-size: 3rem; color: #ccc; margin-bottom: 20px; display: block;"></i>
                    No suggested schools found.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            var table = $('#suggestionsTable').DataTable({
                "pageLength": 25,
                "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                "autoWidth": false,
                "order": [],
                "columnDefs": [{ "orderable": false, "targets": 0 }],
                "language": {
                    "info": "Showing _START_ to _END_ of _TOTAL_ suggestions",
                    "infoEmpty": "No suggestions to show",
                    "infoFiltered": "(filtered from _MAX_ total)"
                }
            });

            $('#exportExcel').on('click', function() {
                var tableData = [['School Name', 'Contact Name', 'Contact Mobile', 'Commitment Statement']];
                table.rows().every(function() {
                    var d = this.data();
                    tableData.push([d[1], d[2], d[3], d[4]]);
                });
                var csv = tableData.map(function(row) {
                    return row.map(function(cell) {
                        var text = $('<div>').html(cell).text();
                        return (text.includes(',') || text.includes('"')) ? '"' + text.replace(/"/g, '""') + '"' : text;
                    }).join(',');
                }).join('\n');
                var link = document.createElement('a');
                link.setAttribute('href', 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv));
                link.setAttribute('download', 'suggested_schools_' + new Date().toISOString().split('T')[0] + '.csv');
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });
        });
    </script>

    <?php
    // Close the database connection
    $conn->close();
    include 'footer.php';
    ?>
</body>
</html>