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

// Confirming deletion
$message = "!!!! Warning !!!! You are about to DELETE a record from the database. Are you sure you want to proceed??";
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
            window.location.href = 'instructors.php';
        }
    }

    window.onload = function() {
        var message = "<?php echo addslashes($message); ?>"; // Escape special characters in message
        var response = confirmAction(message);
        handleConfirmation(response);
    };

</script>

<form id="deleteEntry" action="proccess_delete_instructor.php" method="post">
    <input type="hidden" name="instructor_ID" value="<?php echo $_POST['instructor_ID']; ?>">
</form>
