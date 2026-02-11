<?php

// Print errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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

  // Handle AJAX requests for inline editing
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_field') {
    require 'db_configuration.php';
    
    $org_id = intval($_POST['org_id']);
    $field = $_POST['field'];
    $value = $_POST['value'];
    
    // Define allowed fields for security
    $allowed_fields = [
      'org_name', 'cause_category', 'description', 'website_url', 
      'org_email', 'status', 'address', 'notes'
    ];
    
    if (!in_array($field, $allowed_fields)) {
      http_response_code(400);
      echo json_encode(['success' => false, 'message' => 'Invalid field']);
      exit;
    }
    
    $conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
    if ($conn->connect_error) {
      http_response_code(500);
      echo json_encode(['success' => false, 'message' => 'Database connection failed']);
      exit;
    }
    
    // Prepare and execute update query
    $sql = "UPDATE `recommended_orgs` SET `$field` = ?, `updated_at` = NOW() WHERE `org_id` = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
      http_response_code(500);
      echo json_encode(['success' => false, 'message' => 'Failed to prepare statement']);
      exit;
    }
    
    $stmt->bind_param('si', $value, $org_id);
    
    if ($stmt->execute()) {
      echo json_encode(['success' => true, 'message' => 'Field updated successfully']);
    } else {
      http_response_code(500);
      echo json_encode(['success' => false, 'message' => 'Failed to update field']);
    }
    
    $stmt->close();
    $conn->close();
    exit;
  }
 ?>

<!DOCTYPE html>
<script>
</script>
<html>
  <head>
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Non-Profits Administration</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&display=swap" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-1.11.3.min.js"></script>
    <link href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <!-- DataTables Buttons extension for Excel export -->
    <link href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.dataTables.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
      /* Action buttons styling */
      .action-btn {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        border: none;
        cursor: pointer;
        margin: 2px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        transition: all 0.3s ease;
      }
      
      .btn-update {
        background-color: #007bff;
        color: white;
      }
      
      .btn-update:hover {
        background-color: #0056b3;
        transform: scale(1.1);
      }
      
      .btn-delete {
        background-color: #dc3545;
        color: white;
      }
      
      .btn-delete:hover {
        background-color: #c82333;
        transform: scale(1.1);
      }
      
      /* Tooltip styling */
      .action-btn {
        position: relative;
      }
      
      .action-btn::after {
        content: attr(title);
        position: absolute;
        bottom: -25px;
        left: 50%;
        transform: translateX(-50%);
        background-color: #333;
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        white-space: nowrap;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s;
        z-index: 1000;
      }
      
      .action-btn:hover::after {
        opacity: 1;
      }

      /* Status badge styling */
      .status-badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: bold;
        text-transform: uppercase;
      }
      
      .status-pending {
        background-color: #fff3cd;
        color: #856404;
      }
      
      .status-approved {
        background-color: #d4edda;
        color: #155724;
      }
      
      .status-rejected {
        background-color: #f8d7da;
        color: #721c24;
      }
      
      .status-researching {
        background-color: #d1ecf1;
        color: #0c5460;
      }

      /* Make description column scrollable */
      .description-cell {
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }

      /* Inline editing styles */
      .editable {
        cursor: pointer;
        position: relative;
        padding: 8px;
        border-radius: 4px;
        transition: background-color 0.2s;
      }
      
      .editable:hover {
        background-color: #f8f9fa;
      }
      
      .editable.editing {
        background-color: #fff3cd;
      }
      
      .edit-input {
        width: 100%;
        padding: 4px 8px;
        border: 2px solid #007bff;
        border-radius: 4px;
        font-size: inherit;
        font-family: inherit;
        background-color: white;
      }
      
      .edit-input:focus {
        outline: none;
        border-color: #0056b3;
      }
      
      .edit-select {
        width: 100%;
        padding: 4px 8px;
        border: 2px solid #007bff;
        border-radius: 4px;
        font-size: inherit;
        font-family: inherit;
        background-color: white;
      }
      
      .edit-textarea {
        width: 100%;
        min-height: 60px;
        padding: 4px 8px;
        border: 2px solid #007bff;
        border-radius: 4px;
        font-size: inherit;
        font-family: inherit;
        background-color: white;
        resize: vertical;
      }
      
      .saving {
        opacity: 0.6;
        pointer-events: none;
      }
      
      .save-indicator {
        position: absolute;
        top: 2px;
        right: 2px;
        color: #28a745;
        font-size: 12px;
      }
      
      .error-indicator {
        position: absolute;
        top: 2px;
        right: 2px;
        color: #dc3545;
        font-size: 12px;
      }
      
      /* Status dropdown styling */
      .status-select {
        background-color: transparent;
        border: none;
        color: inherit;
        font-weight: bold;
        text-transform: uppercase;
        font-size: 12px;
        padding: 4px;
        border-radius: 12px;
      }
    </style>
    <script>
      function deleteNonProfit(orgId){
        var confirmation = confirm("Are you sure you want to delete this non-profit recommendation?");
        if(confirmation){
          window.location.href = 'admin_delete_nonprofit.php?id=' + orgId;
        }
      }

      // Inline editing functionality
      function makeEditable() {
        // Make cells editable (excluding non-editable columns)
        $('.editable').off('click').on('click', function() {
          if ($(this).hasClass('editing')) return;
          
          var $cell = $(this);
          var orgId = $cell.closest('tr').find('.org-id').text();
          var field = $cell.data('field');
          var currentValue = $cell.data('original-value') || $cell.text().trim();
          var cellType = $cell.data('type') || 'text';
          
          // Store original value
          $cell.data('original-value', currentValue);
          $cell.addClass('editing');
          
          var inputElement;
          
          if (cellType === 'select' && field === 'status') {
            inputElement = $('<select class="edit-select status-select">' +
              '<option value="pending">Pending</option>' +
              '<option value="approved">Approved</option>' +
              '<option value="rejected">Rejected</option>' +
              '<option value="researching">Researching</option>' +
              '</select>');
            inputElement.val(currentValue.toLowerCase());
          } else if (cellType === 'textarea') {
            inputElement = $('<textarea class="edit-textarea"></textarea>');
            inputElement.val(currentValue);
          } else {
            inputElement = $('<input type="text" class="edit-input">');
            inputElement.val(currentValue);
          }
          
          $cell.html(inputElement);
          inputElement.focus();
          
          // Handle save on blur or Enter key
          inputElement.on('blur keypress', function(e) {
            if (e.type === 'keypress' && e.which !== 13) return;
            if (e.type === 'keypress' && e.shiftKey) return; // Allow Shift+Enter in textarea
            
            var newValue = $(this).val();
            saveField(orgId, field, newValue, $cell);
          });
          
          // Handle Escape key to cancel
          inputElement.on('keydown', function(e) {
            if (e.which === 27) { // Escape key
              cancelEdit($cell, currentValue);
            }
          });
        });
      }
      
      function saveField(orgId, field, newValue, $cell) {
        if ($cell.hasClass('saving')) return;
        
        $cell.addClass('saving');
        $cell.append('<i class="fas fa-spinner fa-spin save-indicator"></i>');
        
        $.ajax({
          url: '',
          method: 'POST',
          data: {
            action: 'update_field',
            org_id: orgId,
            field: field,
            value: newValue
          },
          dataType: 'json',
          success: function(response) {
            if (response.success) {
              // Update cell content
              if (field === 'status') {
                var statusClass = 'status-' + newValue.toLowerCase();
                var statusBadge = "<span class='status-badge " + statusClass + "'>" + newValue + "</span>";
                $cell.html(statusBadge);
              } else if ($cell.hasClass('description-cell')) {
                var shortDesc = newValue.length > 100 ? newValue.substring(0, 100) + '...' : newValue;
                $cell.html(shortDesc);
                $cell.attr('title', newValue);
              } else {
                $cell.html(newValue);
              }
              
              $cell.data('original-value', newValue);
              $cell.append('<i class="fas fa-check save-indicator"></i>');
              
              setTimeout(function() {
                $cell.find('.save-indicator').remove();
              }, 2000);
            } else {
              $cell.append('<i class="fas fa-times error-indicator"></i>');
              setTimeout(function() {
                $cell.find('.error-indicator').remove();
              }, 3000);
            }
          },
          error: function() {
            $cell.append('<i class="fas fa-times error-indicator"></i>');
            setTimeout(function() {
              $cell.find('.error-indicator').remove();
            }, 3000);
          },
          complete: function() {
            $cell.removeClass('saving editing');
          }
        });
      }
      
      function cancelEdit($cell, originalValue) {
        $cell.removeClass('editing');
        
        if ($cell.data('field') === 'status') {
          var statusClass = 'status-' + originalValue.toLowerCase();
          var statusBadge = "<span class='status-badge " + statusClass + "'>" + originalValue + "</span>";
          $cell.html(statusBadge);
        } else {
          $cell.html(originalValue);
        }
      }

    $(document).ready(function () {
      $('#Blog_table thead tr').clone(true).appendTo( '#Blog_table thead' );
      $('#Blog_table thead tr:eq(1) th').each(function () {
      var title = $(this).text();
      if (title !== 'Actions') {
        $(this).html('<input type="text" placeholder="Search ' + title + '" />');
      } else {
        $(this).html('');
      }
      });

      var table = $('#Blog_table').DataTable({
         dom: 'Bfrtip',
         buttons: [
           {
             extend: 'excel',
             text: '<i class="fas fa-file-excel"></i> Export to Excel',
             className: 'btn-excel',
             filename: 'recommended_nonprofits_' + new Date().toISOString().split('T')[0]
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
                 
             // Initialize inline editing after table is loaded
             makeEditable();
             },
         });

      $('a.toggle-vis').on('click', function (e) {
      e.preventDefault();

      // Get the column API object
      var column = table.column($(this).attr('data-column'));

      // Toggle the visibility
      column.visible(!column.visible());
      });
      
      // Re-initialize editing when table is redrawn (after search/sort)
      table.on('draw', function() {
        makeEditable();
      });
     });
    </script>
  </head>
  <body>
  <?php include 'show-navbar.php'; ?>
  <?php show_navbar(); ?>
    <header class="inverse">
      <div class="container">
        <h1><span class="accent-text">Recommended Non-Profits</span></h1>
        <p style="font-size: 14px; margin-top: 10px; opacity: 0.8;">
          <i class="fas fa-info-circle"></i> Click on any cell to edit inline. Press Enter or Tab to save, Escape to cancel.
        </p>
      </div>
    </header>

    <?php if (!empty($_SESSION['flash'])): ?>
      <?php $flash = $_SESSION['flash']; unset($_SESSION['flash']); ?>
      <div class="flash <?= $flash['type'] === 'success' ? 'flash-success' : 'flash-error' ?>" style="margin:16px auto;max-width:90%;padding:12px 14px;border-radius:10px;border:1px solid #ddd;font-weight:600;<?= $flash['type'] === 'success' ? 'background:#f0fff0;border-color:#b2e2b2;' : 'background:#fff5f5;border-color:#f5b5b5;' ?>">
        <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8'); ?>
      </div>
    <?php endif; ?>
	<!-- Jquery Data Table -->

    <div class="toggle_columns">
      Toggle column: 
          <a class="toggle-vis" data-column="1">Org ID</a>
        - <a class="toggle-vis" data-column="2">Suggester ID</a>
        - <a class="toggle-vis" data-column="3">Organization Name</a>
        - <a class="toggle-vis" data-column="4">Category</a>
        - <a class="toggle-vis" data-column="5">Description</a>
        - <a class="toggle-vis" data-column="6">Website</a>
        - <a class="toggle-vis" data-column="7">Email</a>
        - <a class="toggle-vis" data-column="8">Status</a>
        - <a class="toggle-vis" data-column="9">Address</a>
        - <a class="toggle-vis" data-column="10">Created Date</a>
        - <a class="toggle-vis" data-column="11">Updated Date</a>
        - <a class="toggle-vis" data-column="12">Notes</a>
    </div>

    <div style="padding-top: 10px; padding-bottom: 30px; width:90%; margin:auto; overflow:auto">
      <table id="Blog_table" class="display compact">
        <thead>
          <tr>
            <th>Actions</th>
            <th>Org ID</th>
            <th>Suggester ID</th>
            <th>Organization Name</th>
            <th>Category</th>
            <th>Description</th>
            <th>Website</th>
            <th>Email</th>
            <th>Status</th>
            <th>Address</th>
            <th>Created Date</th>
            <th>Updated Date</th>
            <th>Notes</th>
          </tr>
        </thead>
        <tbody>
          <!-- Populating table with data from the database-->
          <?php
            require 'db_configuration.php';
            // Create connection
            $conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
            // Check connection
            if ($conn->connect_error) 
            {
              die("Connection failed: " . $conn->connect_error);
            }
            $sql = "SELECT * FROM `recommended_orgs` ORDER BY org_id DESC";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) 
            {
              // Create table with data from each row
              while($row = $result->fetch_assoc()) 
              {
                  // Format status with badge
                  $statusClass = 'status-' . strtolower($row["status"] ?? 'pending');
                  $statusBadge = "<span class='status-badge {$statusClass}'>" . htmlspecialchars($row["status"] ?? 'pending') . "</span>";
                  
                  // Truncate description for display
                  $description = htmlspecialchars($row["description"] ?? '');
                  $shortDescription = strlen($description) > 100 ? substr($description, 0, 100) . '...' : $description;
                  
                  // Format website as clickable link
                  $website = '';
                  if (!empty($row["website_url"])) {
                      $website = "<a href='" . htmlspecialchars($row["website_url"]) . "' target='_blank' title='" . htmlspecialchars($row["website_url"]) . "'><i class='fas fa-external-link-alt'></i></a>";
                  }
                  
                  echo "<tr>
                    <td style='text-align: center;'>
                      <button class=\"action-btn btn-update\" title=\"Update Non-Profit\" onclick=\"location.href='admin_update_nonprofit.php?id=" . $row['org_id'] . "'\">
                        <i class=\"fas fa-edit\"></i>
                      </button>
                      <button class=\"action-btn btn-delete\" title=\"Delete Non-Profit\" onclick=\"deleteNonProfit(" . $row['org_id'] . ")\">
                        <i class=\"fas fa-trash\"></i>
                      </button>
                    </td>
                    <td class='org-id'>". $row["org_id"]."</td>
                    <td>". $row["suggester_id"]."</td> 
                    <td class='editable' data-field='org_name'>". htmlspecialchars($row["org_name"] ?? '')."</td> 
                    <td class='editable' data-field='cause_category'>". htmlspecialchars($row["cause_category"] ?? '')."</td> 
                    <td class='editable description-cell' data-field='description' data-type='textarea' title='" . htmlspecialchars($row["description"] ?? '') . "'>". $shortDescription ."</td> 
                    <td class='editable' data-field='website_url'>". htmlspecialchars($row["website_url"] ?? '') ."</td> 
                    <td class='editable' data-field='org_email'>". htmlspecialchars($row["org_email"] ?? '')."</td> 
                    <td class='editable' data-field='status' data-type='select'>". $statusBadge ."</td> 
                    <td class='editable' data-field='address' data-type='textarea'>". htmlspecialchars($row["address"] ?? '')."</td> 
                    <td>". ($row["created_at"] ?? '')."</td> 
                    <td>". ($row["updated_at"] ?? '')."</td> 
                    <td class='editable description-cell' data-field='notes' data-type='textarea' title='" . htmlspecialchars($row["notes"] ?? '') . "'>". htmlspecialchars(substr($row["notes"] ?? '', 0, 50)) . (strlen($row["notes"] ?? '') > 50 ? '...' : '') ."</td>
                  </tr>";
              }
            } else {
              echo "<tr><td colspan='13' style='text-align: center;'>No non-profit recommendations found</td></tr>";
            }
            $conn->close();
    		?>
        </tbody>
      </table>
</div>
  </body>
</html>