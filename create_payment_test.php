<?php
require 'paypal_config.php';

use PayPal\Api\Payer;
use PayPal\Api\Amount;
use PayPal\Api\Transaction;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Payment;

$payer = new Payer();
$payer->setPaymentMethod('paypal');

$amount = new Amount();
$amount->setTotal('10.00'); // Total amount to be charged
$amount->setCurrency('USD');

$transaction = new Transaction();
$transaction->setAmount($amount);
$transaction->setDescription('Payment description');

$redirectUrls = new RedirectUrls();
$redirectUrls->setReturnUrl('http://localhost/learnandhelp/execute_payment.php?success=true')
    ->setCancelUrl('http://localhost/learnandhelp/execute_payment.php?success=false');

$payment = new Payment();
$payment->setIntent('sale')
    ->setPayer($payer)
    ->setTransactions([$transaction])
    ->setRedirectUrls($redirectUrls);

try {
    $payment->create($apiContext);
    $approvalUrl = $payment->getApprovalLink();
    header("Location: {$approvalUrl}");
    exit;
} catch (Exception $ex) {
    // Handle error
    echo $ex->getMessage();
}
