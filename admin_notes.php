<?php
function get_admin_notes()
{
  $filename = 'admin_notes.txt';
  if (file_exists($filename)) {
    return file_get_contents($filename);
  } else {
    return '';
  }
}
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
<html lang="en-US">

<head>
  <link rel="icon" href="images/icon_logo.png" type="image/icon type">
  <title>Administration</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&display=swap" rel="stylesheet">
  <link href="css/main.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-1.11.3.min.js"></script>
  <link href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css" rel="stylesheet" type="text/css" />
  <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
</head>

<body>
  <?php include 'show-navbar.php'; ?>
  <?php show_navbar(); ?>
  <header class="inverse">
    <div class="container">
      <h1><span class="accent-text">Admin Notes</span></h1>
    </div>
  </header>
  <div style="padding-top: 10px; padding-bottom: 30px; width:90%; margin:auto; overflow:auto">
    <form method="post" action="update_admin_notes.php">
      <textarea name="admin_notes" id="admin_notes" style="width: 80%; height: 400px; padding: 10px; font-size: 16px;">
          <?php echo htmlspecialchars(get_admin_notes()); ?></textarea>
      <br><br>
      <input type="submit" value="Save" style="padding: 10px 20px; font-size: 16px;">
    </form>
  </div>
  <script>
    $(document).ready(function() {
      var originalContent = $('#admin_notes').val();

      $('form').submit(function(e) {
        if ($('#admin_notes').val() === originalContent) {
          e.preventDefault();
          alert('No changes were made. Please close or edit then update.');
        }
      });
    });
  </script>
</body>

</html>