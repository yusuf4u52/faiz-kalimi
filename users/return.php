<?php
require_once "common.php";
include('_authCheck.php');

$orderId = $_GET["orderId"];
$orderToken = $_GET["token"];

//check if order is already paid and if order token and id match

function getOrderStatus($orderId){
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

$order = getOrderStatus($orderId);
echo '<pre>';
print_r($order);
$response_body = $order["data"];

if ($response_body["order_status"] == "PAID") {
    echo "Payment Successful";
    // create_receipt_in_sheet($response_body["customer_details"]["customer_id"], $response_body["order_amount"] );
}
