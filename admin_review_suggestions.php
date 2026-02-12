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
        }

        .container {
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
        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: flex-start;
            min-width: 150px;
        }

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
        .actions-col { width: 180px; min-width: 180px; }
        .school-name-col { width: 30%; min-width: 200px; }
        .contact-name-col { width: 25%; min-width: 150px; }
        .contact-mobile-col { width: 20%; min-width: 120px; }

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
        <h1>Suggested <span class="accent-text">Schools</span></h1>
    </section>

    <div class="container">
        <div class="table-container">
            <div class="export-controls">
                <button id="exportExcel" class="btn-export">
                    <i class="fas fa-file-excel"></i>
                    Export to Excel
                </button>
                
                <div class="page-size-control">
                    <label for="pageSize">Show:</label>
                    <select id="pageSize">
                        <option value="10">10</option>
                        <option value="25" selected>25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span>entries</span>
                </div>
            </div>

            <?php if ($result->num_rows > 0): ?>
                <table id="suggestionsTable" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th class="actions-col">Actions</th>
                            <th class="school-name-col">School Name</th>
                            <th class="contact-name-col">Contact Name</th>
                            <th class="contact-mobile-col">Contact Mobile</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="action-buttons">
                                        <form style="display: inline;" action="move_to_schools.php" method="post" 
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
                                        
                                        <form style="display: inline;" action="delete_suggestion.php" method="post" 
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
                "lengthMenu": [[10, 25, 50, 100], [10, 25, 50, 100]],
                "dom": 'rt<"d-flex justify-content-between"<"dataTables_info"i><"dataTables_paginate"p>>',
                "responsive": false,
                "scrollX": true,
                "autoWidth": false,
                "order": [], // No default sorting since SQL already orders by latest
                "columnDefs": [
                    {
                        "orderable": false,
                        "targets": 0 // Actions column not sortable
                    },
                    {
                        "width": "120px",
                        "targets": 0 // Actions column width
                    },
                    {
                        "width": "30%",
                        "targets": 1 // School Name column width
                    },
                    {
                        "width": "25%",
                        "targets": 2 // Contact Name column width
                    },
                    {
                        "width": "20%",
                        "targets": 3 // Contact Mobile column width
                    }
                ],
                "language": {
                    "emptyTable": "No suggested schools found",
                    "info": "Showing _START_ to _END_ of _TOTAL_ suggestions",
                    "infoEmpty": "Showing 0 to 0 of 0 suggestions",
                    "infoFiltered": "(filtered from _MAX_ total suggestions)"
                }
            });

            // Custom page size control
            $('#pageSize').on('change', function() {
                var pageSize = $(this).val();
                table.page.len(pageSize).draw();
            });

            // Export to Excel functionality
            $('#exportExcel').on('click', function() {
                // Get table data
                var tableData = [];
                
                // Add headers (excluding Actions column)
                var headers = ['School Name', 'Contact Name', 'Contact Mobile'];
                tableData.push(headers);
                
                // Add data rows
                table.rows().every(function() {
                    var data = this.data();
                    var row = [data[1], data[2], data[3]]; // Skip Actions column (index 0)
                    tableData.push(row);
                });
                
                // Convert to CSV format
                var csvContent = tableData.map(function(row) {
                    return row.map(function(cell) {
                        // Escape quotes and wrap in quotes if contains comma or quote
                        if (typeof cell === 'string' && (cell.includes(',') || cell.includes('"') || cell.includes('\n'))) {
                            return '"' + cell.replace(/"/g, '""') + '"';
                        }
                        return cell;
                    }).join(',');
                }).join('\n');
                
                // Create and download file
                var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                var link = document.createElement("a");
                
                if (link.download !== undefined) {
                    var url = URL.createObjectURL(blob);
                    link.setAttribute("href", url);
                    link.setAttribute("download", "suggested_schools_" + new Date().toISOString().split('T')[0] + ".csv");
                    link.style.visibility = 'hidden';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }
            });

            // Add custom search functionality
            var searchInput = $('<div class="dataTables_filter" style="margin-bottom: 20px;"><label>Search: <input type="search" class="form-control form-control-sm" placeholder="Search suggestions..." style="margin-left: 10px; display: inline-block; width: auto;"></label></div>');
            searchInput.insertBefore('#suggestionsTable');
            
            searchInput.find('input').on('keyup', function() {
                table.search(this.value).draw();
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