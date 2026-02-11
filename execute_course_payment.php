<?php
require 'paypal_config.php';
require 'paypal_functions.php';
require 'db_configuration.php';

$status = session_status();
if ($status == PHP_SESSION_NONE) {
    session_start();
}

if (!(isset($_SESSION['email']))) {
    header('Location: login.php');
}

function handleCoursePaymentExecution($apiContext) {
    if (isset($_GET['success']) && $_GET['success'] == 'true') {
        $paymentId = $_GET['paymentId'];
        $payerId = $_GET['PayerID'];
        $reg_id = isset($_GET['reg_id']) ? $_GET['reg_id'] : null;

        // Call the executePayment function
        $result = executePayment($paymentId, $payerId, $apiContext);

        if (is_array($result) && isset($result['success_message'])) {
            // Payment executed successfully
            $success_message = $result['success_message'];
            $payment_id = $result['payment_id'];

            $connection = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);

            if ($connection === false) {
                die("Failed to connect to database: " . mysqli_connect_error());
            }

            // Update payment id in SQL db
            if ($reg_id) {
                $query = <<<SQL
                            UPDATE registrations
                            SET Payment_Id = '$payment_id'
                            WHERE Reg_Id = '$reg_id';
                        SQL;
                $result = mysqli_query($connection, $query);
                if ($result) {
                    // Redirect to form_submit.php
                    header("Location: form-submit.php");
                    exit;
                } else {
                    echo "Failed to update the payment ID in the database: " . mysqli_error($connection);
                }
            } else {
                echo "Registration ID is missing.";
            }
            
            mysqli_close($connection);
        } else {
            // Handle error message returned from executePayment
            echo $result;
        }
    } else {
        echo "Payment canceled.";
    }
}

// Call the handleCoursePaymentExecution function
handleCoursePaymentExecution($apiContext);
?>
