<?php

//$email = $_POST['email'];

//$amount = $_POST['amount'];

//$reference = $_POST['ref'];

//$notify = $_POST['notify'];

    $action_url = "https://api.paystack.co/transaction/initialize";

    $fields = [
    'email' => $_POST['email'],
    'amount' => $_POST['amount'],
    'reference' =>$_POST['ref'],
    'callback_url' => $_POST['notify'],
    ];
    $fields_string = http_build_query($fields);

    //open connection
    $ch = curl_init();

    curl_setopt($ch,CURLOPT_URL, $action_url);
    curl_setopt($ch,CURLOPT_POST, true);
    curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    "Authorization: Bearer ".$_POST['key'],
    "Cache-Control: no-cache",
    ));
    
    //So that curl_exec returns the contents of the cURL; rather than echoing it
    curl_setopt($ch,CURLOPT_RETURNTRANSFER, true); 
    
    //execute post
    $result = curl_exec($ch);
    $res = json_decode($result,true);
    var_dump($res);
    $domain = $res["data"]["authorization_url"]; 
    
    header("location: $domain");