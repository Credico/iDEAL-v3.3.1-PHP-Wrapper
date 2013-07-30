<?php

	require_once("ideal.class.php");
	header('Content-Type: text/html; charset=utf-8');
	
	$idealSEPA = new Ideal_SEPA();
	
	// Start a transaction and forward if possible
	if(isset($_POST['stap']) && $_POST['stap'] == 2)
	{
		$issuerId = $_POST['issuerId'];
		$purchaseID = "Order123";
		$amount = 10.00;
		$description = "Omschrijving";
		$entranceId = "1234567890000";
		$returnUrl = "http://www.mysite.com/requestTransactionStatus.php";
		
		// Start a transaction
		$requestTransaction = $idealSEPA->requestTransaction($issuerId, $purchaseID, $amount, $description, $entranceId, $returnUrl);
		
		if($requestTransaction['status'] == true)
		{
			header("Location: ".$requestTransaction['issuerAutenticationURL']);	
		}
		else
		{
			echo '<pre>';
			var_dump( $requestTransaction );	
		}
		
		
	}