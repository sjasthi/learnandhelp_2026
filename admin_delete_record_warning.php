<?php
$status = session_status();
$response = false;

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
//Message presented to user with (OK and Cancel button options)
$message = "!!!! Warning !!!! You are about to DELETE a record from the database.  Are you sure you want to proceed??";
?>

<script>
    function confirmAction(message) {
        return confirm(message);
    }
    
    function handleConfirmation(response) {
        if (response) {
            // If user selects "OK" the process delete request via the id="deleteEntry", which calls the php file to delete the record
            document.getElementById('deleteEntry').submit();
        } else {
            // If user clicks Cancel, then revert back to original webpage
            // windows.location.href="javascript:history.back()";
            // header("Location: admin_classes.php");
            window.location.href = 'admin_classes.php';
        }
    }
    
    window.onload = function() {
        var message = "<?php echo addslashes($message); ?>"; // Escape special characters in message
        var response = confirmAction(message);
        handleConfirmation(response);
    };

</script>

<form id="deleteEntry" action="admin_delete_class.php" method="post">
    <input type="hidden" name="Class_Id" value="<?php echo $_POST['Class_Id']; ?>">
</form>
