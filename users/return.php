<?php
require_once "common.php";
include('_authCheck.php');
include('googlesheet.php');

$orderId = $_GET["orderId"];
$orderToken = $_GET["token"];

//check if order is already paid and if order token and id match
function getOrderStatus($orderId)
{
    $url = CashfreeConfig::$baseUrl . "/orders/" . $orderId;

    $headers = array(
        "content-type: application/json",
        "x-client-id: " . CashfreeConfig::$appId,
        "x-client-secret: " . CashfreeConfig::$secret,
        "x-api-version: " . CashfreeConfig::$apiVersion,
    );

    $orderResp = doGet($url, $headers);
    return $orderResp;
}

function getPaymentBankReference($orderId)
{
    $url = CashfreeConfig::$baseUrl . "/orders/" . $orderId . "/payments";

    $headers = array(
        "content-type: application/json",
        "x-client-id: " . CashfreeConfig::$appId,
        "x-client-secret: " . CashfreeConfig::$secret,
        "x-api-version: " . CashfreeConfig::$apiVersion,
    );

    $paymentResp = doGet($url, $headers);
    return $paymentResp;
}

$payment = getPaymentBankReference($orderId);
$bankreferenceid = is_null($payment["data"][0]["bank_reference"]) ? "" : $payment["data"][0]["bank_reference"];
$order = getOrderStatus($orderId);
$response_body = $order["data"];
if ($response_body["order_status"] == "PAID") {
    create_receipt_in_sheet($_SESSION['thali'], $response_body["order_amount"], $bankreferenceid, $orderId);
}
