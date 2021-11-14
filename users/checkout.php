<?php
require_once "common.php";
include('_authCheck.php');

function createOrder($amount)
{
    $headers = array(
        "content-type: application/json",
        "x-client-id: " . CashfreeConfig::$appId,
        "x-client-secret: " . CashfreeConfig::$secret,
        "x-api-version: " . CashfreeConfig::$apiVersion,
    );

    $data = array(
        "order_amount" => $amount,
        "order_currency" => "INR",
        "customer_details" => array(
            "customer_id" => $_SESSION['thaliid'],
            "customer_email" => $_SESSION['email'],
            "customer_phone" => $_SESSION['mobile']
        ),
        "order_meta" => array(
            "return_url" => CashfreeConfig::$returnHost . "/fmb/users/return.php?orderId={order_id}&token={order_token}",
            "notify_url" => "",
        )
    );
    $postResp = doPost(CashfreeConfig::$baseUrl . "/orders", $headers, $data);
    return $postResp;
}

$amount = $_POST['amount'];
$resp = createOrder($amount);
if ($resp["code"] == 200) {
    $paymentLink = $resp["data"]["payment_link"];
    header("Location: $paymentLink");
} else {
    echo "Something went wrong with order creation! \n";
    echo json_encode($resp["data"]);
}
