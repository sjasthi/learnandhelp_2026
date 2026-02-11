<?php
// Print errors (match your pattern)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Session + admin-gate (modeled on admin_usersList.php)
$status = session_status();
if ($status == PHP_SESSION_NONE) {
  session_start();
}
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
<html lang="en">
  <head>
    <meta charset="utf-8"/>
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Administration — Partners</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&display=swap" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-1.11.3.min.js"></script>
    <link href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>

    <script>
      function deletePartner(partnerId){
        var confirmation = confirm("Are you sure you want to delete this partner?");
        if (confirmation){
          window.location.href = 'admin_deletepartner.php?id=' + partnerId;
        }
      }

      $(document).ready(function () {
        // Clone header row to add per-column search inputs (like users list)
        $('#PartnersTable thead tr').clone(true).appendTo('#PartnersTable thead');
        $('#PartnersTable thead tr:eq(1) th').each(function () {
          var title = $(this).text();
          // Skip "Actions" column search box
          if (title.toLowerCase() === 'actions') {
            $(this).html('');
          } else {
            $(this).html('<input type="text" placeholder="Search ' + title + '" />');
          }
        });

        var table = $('#PartnersTable').DataTable({
          orderCellsTop: true,
          fixedHeader: true,
          initComplete: function () {
            this.api().columns().every(function () {
              var that = this;
              $('input', this.header()).on('keyup change clear', function () {
                if (that.search() !== this.value) {
                  that.search(this.value).draw();
                }
              });
            });
          }
        });

        $('a.toggle-vis').on('click', function (e) {
          e.preventDefault();
          var column = table.column($(this).attr('data-column'));
          column.visible(!column.visible());
        });
      });
    </script>

    <style>
      .thumb {
        width: 48px; height: 48px; object-fit: contain; border-radius: 6px; border: 1px solid #eee;
      }
      .btn {
        width: fit-content; height: 44px; border-radius: 5px; padding: 0 20px; cursor: pointer;
      }
      .btn-primary {
        background-color: #99D930; color: white; border: solid 0px;
      }
      .btn-outline {
        background-color: transparent; color: black; border: solid 1px black;
      }
      .btn-danger {
        background-color: transparent; color: red; border: solid 1px red;
      }
      .toolbar { padding: 10px 0 30px 0; width: 90%; margin: auto; overflow: auto; }
      .toggle_columns { padding: 10px 0; width: 90%; margin: auto; overflow: auto; }
      table.dataTable thead input { width: 100%; box-sizing: border-box; }
    </style>
  </head>
  <body>
    <?php include 'show-navbar.php'; ?>
    <?php show_navbar(); ?>

    <header class="inverse">
      <div class="container">
        <h1><span class="accent-text">Partners</span></h1>
      </div>
    </header>

    <!-- Toggle columns (optional) -->
    <div class="toggle_columns">
      Toggle column:
      <a class="toggle-vis" data-column="0">Partner ID</a> -
      <a class="toggle-vis" data-column="1">Name</a> -
      <a class="toggle-vis" data-column="2">Type</a> -
      <a class="toggle-vis" data-column="3">Logo</a> -
      <a class="toggle-vis" data-column="4">Website</a> -
      <a class="toggle-vis" data-column="5">Impact</a> -
      <a class="toggle-vis" data-column="6">Created</a> -
      <a class="toggle-vis" data-column="7">Updated</a>
    </div>

    <!-- Create Partner -->
    <div class="toolbar">
      <button type="button" class="btn btn-primary" onclick="location.href='admin_createpartner.php'">Create Partner</button>
    </div>

    <!-- Partners Table -->
    <div class="toolbar">
      <table id="PartnersTable" class="display compact" style="width:100%">
        <thead>
          <tr>
            <th>Partner ID</th>
            <th>Partner Name</th>
            <th>Partner Type</th>
            <th>Logo</th>
            <th>Website URL</th>
            <th>Impact Description</th>
            <th>Created At</th>
            <th>Updated At</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php
          require 'db_configuration.php';
          $conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
          if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
          }

          // Pull all partners
          $sql = "SELECT partner_id, partner_name, partner_type, logo_image, website_url, impact_description, created_at, updated_at
                  FROM community_partners
                  ORDER BY created_at DESC";
          $result = $conn->query($sql);

          if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
              // Build a logo thumbnail if we can resolve a path; otherwise show filename
              $logoFile = htmlspecialchars($row['logo_image'] ?? '');
              $possiblePaths = [
                "images/community_partners/$logoFile",
                "images/$logoFile",
                $logoFile
              ];
              $logoSrc = "";
              foreach ($possiblePaths as $path) {
                if ($logoFile && file_exists($path)) { $logoSrc = $path; break; }
              }
              if (!$logoSrc) {
                $logoSrc = "images/community_partners/default_logo.png"; // fallback image if present
              }

              $partnerId   = (int)$row['partner_id'];
              $name        = htmlspecialchars($row['partner_name']);
              $type        = htmlspecialchars($row['partner_type']);
              $website     = htmlspecialchars($row['website_url']);
              $impact      = htmlspecialchars($row['impact_description']);
              $created     = htmlspecialchars($row['created_at']);
              $updated     = htmlspecialchars($row['updated_at']);

              echo "<tr>
                      <td>{$partnerId}</td>
                      <td>{$name}</td>
                      <td>{$type}</td>
                      <td>" . ($logoSrc ? "<img class=\"thumb\" src=\"{$logoSrc}\" alt=\"{$name} logo\"/>" : htmlspecialchars($logoFile)) . "</td>
                      <td><a href=\"{$website}\" target=\"_blank\" rel=\"noopener noreferrer\">{$website}</a></td>
                      <td>{$impact}</td>
                      <td>{$created}</td>
                      <td>{$updated}</td>
                      <td>
                        <button type=\"button\" class=\"btn btn-outline\" onclick=\"location.href='admin_updatepartner.php?id={$partnerId}'\">Update</button>
                        <button type=\"button\" class=\"btn btn-danger\" onclick=\"deletePartner({$partnerId})\">Delete</button>
                      </td>
                    </tr>";
            }
          } else {
            echo "<tr><td colspan='9'>No partners found.</td></tr>";
          }
          $conn->close();
        ?>
        </tbody>
      </table>
    </div>
  </body>
</html>
