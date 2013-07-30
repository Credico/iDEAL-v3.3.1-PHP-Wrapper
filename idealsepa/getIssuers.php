<?php

	require_once("ideal.class.php");
	header('Content-Type: text/html; charset=utf-8');
	
	$idealSEPA = new Ideal_SEPA();
	
	// Show list of issuers
	if(!isset($_POST['stap']) && !isset($_GET['stap']))
	{
		
		$issuerList = $idealSEPA->getIssuerList();
		
		if($issuerList['status'] == true)
		{
			// Maak lijstje
			echo '<form method="post" action="requestTransaction.php"><input type="hidden" name="stap" value="2">';
			echo '<select name="issuerId">';
			foreach($issuerList['issuerList'] as $country => $issuers)
			{
				echo '<optgroup label="'.$country.'" />';
				foreach($issuers as $issuer_id => $issuer_name)
				{
					echo '<option value="'.$issuer_id.'">'.$issuer_name.'</option>';	
				}
			}
			echo '<input type="submit" value="Verder">';
			echo '</select>';
			echo '</form>';
		}
		else
		{
			echo '<pre>';
			var_dump($issuerList);
		}
		
	}