<?php
require 'vendor/autoload.php';

use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;

// Replace these values with your PayPal app's Client ID and Secret
$clientId = 'AW3cq_AvlIhEd7lRglBVtVM-1k7AAOcXMRRnQ8bccOLQz1RH-oPOTSo7bVrnEIOSbhdgdfNnvLz3LQ97';
$clientSecret = 'EDnCTkb_5WC9mK25Q0pRe0e_d2RuIAMC2tO8Zv3doAaSuz07D-nKIpvWjcp6lyce-G-_HPVwoV52mYEA';

$apiContext = new ApiContext(
    new OAuthTokenCredential(
        $clientId,
        $clientSecret
    )
);

$apiContext->setConfig([
    'mode' => 'sandbox', // or 'live'
    'http.ConnectionTimeOut' => 30,
    'log.LogEnabled' => false,
    'log.FileName' => 'PayPal.log',
    'log.LogLevel' => 'FINE', // Available options: 'FINE', 'INFO', 'WARN' or 'ERROR'
    'validation.level' => 'log'
]);
