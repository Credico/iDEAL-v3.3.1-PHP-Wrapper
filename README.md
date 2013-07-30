iDEAL-v3.3.1-PHP-Wrapper
========================

A PHP Wrapper for the iDeal v3.3.1 (SEPA) code

This class is meant to make it easier for any PHP code to implement iDeal v3.3.1 into their website. The class uses the code provided by the acquirer (ABN/ING/Rabobank) but wraps it up in an easy to use class. Only things left to do is setting the configuration file and creating the merchants key and certificate files.

----------------------------
HOW TO USE THE CLASS
----------------------------
This is just a general case of how the class can be used

1. Customer is ready to pay and '$IssuerList = Ideal_SEPA::getIssuerList()' is used to display the available Issuers
2. Customer chooses an issuer and '$requestTransaction = Ideal_SEPA::requestTransaction()' is called, all transaction details as parameters (see bottom of readme)
3. '$requestTransaction = Ideal_SEPA::requestTransaction()' returns an array with: $requestTransaction['issuerAutenticationURL'] - this is the location the customer has to be forwarded to and $requestTransaction['transactionID'] - this is the unique transaction ID. Make sure to save both the TransactionID and EntranceCode to your database for future use(!!)
4. After the customer did (or did not do) the payment, '$requestTransactionStatuts = Ideal_SEPA::requestTransactionStatus()' is called with the trxid as parameter. The result of the payment can be found in $requestTransactionStatus['result']
5. Based on the result you perform an action in your database, you can use the transactionId ($_GET['trxid']) and entranceode ($_GET['ec']) to find the payment in your database

Possible results are:
- success: the payment is succesfully done
- cancelled: the customer cancelled the payment
- failure: an error occured (not caused by the customer)
- open: the payment is still open
- expired: the payment expired



----------------------------
Step 1: CONFIGURE THE CLASS
----------------------------

1. Copy the contents of the 'idealsepa' folder to a folder in your website. For example: /public_html/includes/idealsepa/

2. Create a merchant private key using an openSSL tool and this command (dont forget to change 'password'): 
openssl genrsa -aes128 -out private_key.pem -passout pass:password 2048

3. Create a merchant private certificate using an openSSL tool and this command (again: don't forget to change 'password'):
openssl req -x509 -sha256 -new -key private_key.pem -passin pass:password -days 1825 -out private_cert.cer

4. Place both files (private_key.pem & private_cert.cer) inside the 'certificates' folder.

5. Upload the certificates folder to your website, preferably outside the webroot (one folder up from public_html)

6. Login to your iDEAL dashboard and download the latest iDeal_v3.cer provided by your bank. There are three files available in the 'certificates' folder, but they are about to expire, so best practice is to always download the latest.

7. While you're in your iDeal dashboard, quickly upload the "private_key.cer" file to your account.

7. Open /public_html/includes/idealsepa/Connector/config.conf and change the parameters to match your needs *

*: if you want to test first, make sure the parameter 'MERCHANTRETURNURL' in config.conf points towards the demo file: requestTransactionStatus.php



----------------------
Step 2: TESTING iDEAL
----------------------

Now it's time to test the configuration, simply browse to http://www.yourdomain.com/includes/idealsepa/getIssuers.php and test all steps. If working properly you will be forwarded towards the test payment page after choosing a bank and after confirming the payment you will be prompted with an array containing the payment data.



----------------------------
Ideal_SEPA::getIssuerList()
----------------------------

This method returns all available issuers as array:

$issuerList = array(
  'Country' => array(
    'BIC' => 'IssuerName'
  )
);

--------------------------------
Ideal_SEPA::requestTransaction()
--------------------------------

Ideal_SEPA::requestTransaction($issuerId, $purchaseId, $amount, $description, $entranceCode, $merchantReturnUrl = false, $expirationPeriod = 0)
This method starts a transaction

- $issuerId: the value retrieved by Ideal_SEPA::getIssuerList()
- $purchaseId: a unique identification of the order in the merchants system (max 16 chars)
- $amount: the amount payable in euros (with a periode (.) used as decimal separator)
- $description: description of the order (max 32 chars)
- $entranceCode: an authentication identifier to facilitate continuation of the session between Merchant and Consumer
- $merchantReturnUrl: can be used to overwrite MERCHANTRETURNURL from config.conf
- $expirationPeriod: can be used to overwrite EXPIRATIONPERIOD from config.conf

---------------------------------------
Ideal_SEPA::requestTransactionStatus()
---------------------------------------

Ideal_SEPA::requestTransactionStatus($transactionId)
This method returns the transaction status

- $transactionId: the transactionId (will be send as $_GET-parameter to the returnurl: requestTransactionStatus.php?trxid=_TRANSACTIONID_&ec=_ENTRANCECODE_
