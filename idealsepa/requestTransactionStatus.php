<?php

	require_once("ideal.class.php");
	header('Content-Type: text/html; charset=utf-8');
	
	$idealSEPA = new Ideal_SEPA();
	
	$transactionID = $_GET["trxid"];
	
	$requestTransactionStatus = $idealSEPA->requestTransactionStatus($transactionID);
	
	echo '<pre>';
	var_dump($requestTransactionStatus);