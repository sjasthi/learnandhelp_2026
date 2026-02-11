<?php
require 'paypal_config.php';

use PayPal\Api\Payer;
use PayPal\Api\Amount;
use PayPal\Api\Transaction;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;

function createPayPalPayment($totalAmount, $currency, $description, $returnUrl, $cancelUrl) {
    global $apiContext; 

    $payer = new Payer();
    $payer->setPaymentMethod('paypal');

    $amount = new Amount();
    $amount->setTotal($totalAmount); // Total amount to be charged
    $amount->setCurrency($currency);

    $transaction = new Transaction();
    $transaction->setAmount($amount);
    $transaction->setDescription($description);

    $redirectUrls = new RedirectUrls();
    $redirectUrls->setReturnUrl($returnUrl)
                 ->setCancelUrl($cancelUrl);

    $payment = new Payment();
    $payment->setIntent('sale')
            ->setPayer($payer)
            ->setTransactions([$transaction])
            ->setRedirectUrls($redirectUrls);

    try {
        $payment->create($apiContext);
        return $payment->getApprovalLink();
    } catch (Exception $ex) {
        // Handle error
        return 'Error: ' . $ex->getMessage();
    }
}

function executePayment($paymentId, $payerId, $apiContext) {
    try {
        // Retrieve the payment object by paymentId
        $payment = Payment::get($paymentId, $apiContext);

        $execution = new PaymentExecution();
        $execution->setPayerId($payerId);

        // Execute the payment
        $result = $payment->execute($execution, $apiContext);

        // Payment executed successfully
        $success_message = "Payment successful!";
        return array('success_message' => $success_message, 'payment_id' => $paymentId);
    } catch (Exception $ex) {
        // Handle error
        return "Error: " . $ex->getMessage();
    }
}

function handlePaymentExecution($apiContext) {
    if (isset($_GET['success']) && $_GET['success'] == 'true') {
        $paymentId = $_GET['paymentId'];
        $payerId = $_GET['PayerID'];

        // Call the executePayment function
        $message = executePayment($paymentId, $payerId, $apiContext);
        echo $message;
    } else {
        echo "Payment canceled.";
    }
}
?>
