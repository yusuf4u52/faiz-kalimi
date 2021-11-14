<?php

function doPost($url, $headers, $data)
{
    $curl = curl_init($url);

    curl_setopt($curl, CURLOPT_VERBOSE, 1);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
    // remove below properties in production
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

    $resp = curl_exec($curl);
    if ($resp === false) {
        throw new \Exception("Unable to post to cashfree");
    }
    $info = curl_getinfo($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $responseJson = json_decode($resp, true);
    curl_close($curl);
    return array("code" => $httpCode, "data" => $responseJson);
}

function doGet($url, $headers)
{
    $curl = curl_init($url);

    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    // remove below properties in production
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    $resp = curl_exec($curl);
    if ($resp === false) {
        throw new \Exception("Unable to get to cashfree");
    }
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $responseJson = json_decode($resp, true);
    curl_close($curl);
    return array("code" => $httpCode, "data" => $responseJson);
}
