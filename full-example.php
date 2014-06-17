<?php



/**
 * iDeal 3.3.1 Class - example.php
 * @author Joshua de Gier (www.pendo.nl)
 */

	require_once( dirname(__FILE__) . '/idealsepa/ideal.class.php');
	$idealSEPA = new Ideal_SEPA();
	
/**
 * Step 1:
 * Let the user choose a back of his/her choice, they are grouped by country
 * Save this choice in the database, cookie or session to use when starting the transaction
 */
 
 
 	echo '<select name="issuerId">';
	$issuerList = $idealSEPA->getIssuerList();
	if( $issuerList['status'] == true )
	{
		foreach( $issuerList['issuerList'] as $country => $issuer )
		{
			echo '<optgroup label="'. $country .'">';
			foreach( $issuers as $issuer_id => $issuer_name )
			{
				echo '<option value="'. $issuer_id .'">'. $issuer_name .'</option>';	
			}
		}
	}
	echo '</select>';
	
/**
 * Step 2:
 * After the user selected an issuer, we have collected all data to start a transaction.
 * We just need some additional information (from the order) to request a transaction.
 */
 
	$sOrderId 			= mktime(); 			// an orderId, should be unique for each transaction.
	$sOrderDescription 	= "Order #".$sOrderId; 	// an order description that is shown on the payment page
	$fOrderAmount 		= 9.99;					// the order total in EURO with 2 decimals
	$sIssuerId 			= $_POST['issuerId'];	// the issuerId as choosen in step 1
	$sReturnUrl			= 'http://www.mysite.com/idealsepa/return.php';
	$sExpirationPeriod	= 1800;					// Payment will expire after 30 minutes
	
	// Then we need to create an entrance code we generate ourselfs, must be unique for each transaction you start!!
	$sEntranceCode 		= sha1($sOrderId . '_' . date('YmdHis'));
	
	// After this, we have our data ready, request a transaction!
	$requestTransaction = $idealSEPA->requestTransaction($sIssuerId, $sOrderId, $fOrderAmount, $sOrderDescription, $sEntranceCode, $sReturnUrl);
	if($requestTransaction['status'] == true)
	{
		// The transaction is requested successfully, now we can save the transaction data to our database
		// You can set the status manual to anything (I prefer open untill the issuer mutated the status)
		$aPaymentData = array(
			'order_id' => $sOrderId,
			'order_amount' => $fOrderAmount,
			'order_entrancecode' => $sEntranceCode,
			'order_transactionid' => $requestTransaction['transactionID'],
			'order_status' => 'open'
		);
		$qPayment = $database->insert('order_payments')->data( $aPaymentData );
		
		// If the payment was successfully added to the database, forward the client to the payment page
		if( $aPayment )
		{
			header( "Location: ". $requestTransaction['issuerAutenticationURL'] );
		}
		
	}
	
/**
 * Step 3:
 * Returning to your page (return.php for example). This page will validate the payment
 * The client will be forwarded to a page with the trxid (transactionID) and ec (entrancecode) in the $_GET parameters
 */
 
	$sTransactionId 	= $_GET["trxid"];
	$sEntranceCode 		= $_GET['ec'];
	
	// First we check if this payment is in our database
	$qOrder = $database->select('order_payments')->where('order_transactionid', $sTransactionId)->where('order_entrancecode', $sEntranceCode);
	if( $qOrder )
	{
		if( $qOrder['order_status'] == 'open' )
		{
			// If we found the payment, request the status using the class
			$requestTransactionStatus = $idealSEPA->requestTransactionStatus( $sTransactionId );
			if($requestTransactionStatus['status'] == true) 
			{
				$sStatus = strtoupper( $requestTransactionStatus['result'] );
				if( $sStatus == 'SUCCESS' )
				{
					// The payment succeeded, update your database
					$database->update('order_payments')->set(array( 'order_status' => 'approved', 'order_sepastatus' => $sStatus ))->where('order_id', $qOrder['order_id'] );
					echo 'The payment succeeded, thanks';
					// You can also send an e-mail to your client here...
				}
				else
				{
					// Save the latest status to the database anyway	
					$database->update('order_payments')->set(array( 'order_sepastatus' => $sStatus ))->where('order_id', $qOrder['order_id'] );
					// The status of the payment is not 'success', so there has been a problem or the payment
					// hasn't been verified yet, show a message to the user
					if($sStatus == "CANCELLED") {
						echo 'The payment was cancelled by the user';
					} elseif($sStatus == "FAILURE") {
						echo 'The payment was refused by the bank';
					} elseif($sStatus == "OPEN") {
						echo 'The payment status is still open';
					} elseif($sStatus == "EXPIRED") {
						echo 'The payment expired';
					} else {
						echo 'The status of the payment is unknown';
					}
				}
			}
			else
			{
				
			}
		} 
		else 
		{
			// This will be echoed if order_status in the database is not 'open' anymore.
			echo 'Payment succeeded, thanks';
		}
	}
	
/**
 * Step 4:
 * in order to make sure that payments are validated, run a cronjob that checks open payments. Run this cronjob every 15 minutes to
 * validate open payments. Best practice is to check the payment for a maximum of 4 times, if they payment is still open and hasn't
 * received a final status yet, than you might want to concider changing the interval to every 30-60 minutes.
 */
 
	$qOpenPayments = $database->select('order_payments')->where('order_status', 'open')->where('order_status_tries', 4, '<=');
	if( $dabase->num_rows( $qOpenPayments ) > 0 ) 
	{
		foreach( $qOpenPayments as $aOrderPayment )
		{
			// If we found the payment, request the status using the class
			$requestTransactionStatus = $idealSEPA->requestTransactionStatus( $aOrderPayment['order_transactionid'] );
			if($requestTransactionStatus['status'] == true) 
			{
				$sStatus = strtoupper( $requestTransactionStatus['result'] );
				if( $sStatus == 'SUCCESS' )
				{
					// The payment succeeded, update your database
					$database->update('order_payments')->set(array( 'order_status' => 'approved', 'order_sepastatus' => $sStatus ))->where('order_id', $aOrderPayment['order_id'] );
					// You can also send an e-mail to your client here...
				}
				else
				{
					// Save the latest status to the database anyway	
					$database->update('order_payments')->set(array( 'order_sepastatus' => $sStatus ))->where('order_id', $aOrderPayment['order_id'] );
				}
			}
		}
	}


?>