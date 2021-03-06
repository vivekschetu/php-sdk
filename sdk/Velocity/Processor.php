<?php
/*
 * This class represents a Velocity Transaction.
 * It can be used to query and verify/authorize/authorizeandcapture/capture/undo/adjust/returnbyid/returnunlinked transactions.
 */

class Velocity_Processor 
{
	/* -- Properties -- */

	private $isNew;
	private $connection;
	public $sessionToken ;
	public $messages = array();
	public $errors = array();
	public static $Txn_method = array('verify', 'authorize', 'authorizeandcapture', 'capture', 'adjust', 'undo', 'returnbyid', 'returnunlinked'); // array of method name to identify method request for common process
	public static $identitytoken;
	public static $applicationprofileid;
	public static $merchantprofileid;
	public static $workflowid;
	public static $isTestAccount;

	public function __construct($applicationprofileid, $merchantprofileid, $workflowid, $isTestAccount, $identitytoken = null, $sessiontoken = null ) {
		$this->connection = Velocity_Connection::instance(); // velocity_connection class object store in private data member $connection. 
		self::$identitytoken = $identitytoken;
		self::$applicationprofileid = $applicationprofileid;
		self::$merchantprofileid = $merchantprofileid;
		self::$workflowid = $workflowid;
		self::$isTestAccount = $isTestAccount;
		if(empty($sessiontoken) && !empty($identitytoken)){
			$this->sessionToken = $this->connection->signOn();
		} else {
			$this->sessionToken = $sessiontoken; 
		}
		
	}

	/* -- Class Methods -- */
	
	/*
	* Verify the card detail and address detail of customer.
	* This Method create corresponding xml for gateway request.
	* This Method Reqest send to gateway and handle the response.
	* @param array $options this array hold "avsData, carddata"
	* @return array $this->handleResponse($error, $response) array of successfull or failure of gateway response. 
	*/
	
	public function verify($options = array()) {
		
		try { 
																
			$xml = Velocity_XmlCreator::verify_XML($options);  // got Verify xml object.
			$xml->formatOutput = TRUE;
			$body = $xml->saveXML();
			//echo '<xmp>'.$body.'</xmp>'; die;
			list($error, $response) = $this->connection->post(
                                                                            $this->path(
                                                                                    self::$workflowid, 
                                                                                    self::$Txn_method[0], 
                                                                                    self::$Txn_method[0]
                                                                            ), 
                                                                            array(
                                                                                    'sessiontoken' => $this->sessionToken, 
                                                                                    'xml' => $body, 
                                                                                    'method' => self::$Txn_method[0]
                                                                            )
                                                                     );
			return $this->handleResponse($error, $response);
			//return $response;
		} catch (Exception $e) {
			throw new Exception( $e->getMessage() );
		}
	
	}
	
	/*
	 * Authorizeandcapture operation is used to authorize transactions by performing a check on cardholder's funds and reserves.  
	 * The authorization amount if sufficient funds are available.  
	 * @param array $options this array hold "amount, paymentAccountDataToken, avsData, carddata, invoice no., order no"
	 * @return array $this->handleResponse($error, $response) array of successfull or failure of gateway response. 	 
	 */
	public function authorizeAndCapture($options = array()) { 

		try {
		
			$xml = Velocity_XmlCreator::authandcap_XML($options);  // got authorizeandcapture xml object. 
			$xml->formatOutput = TRUE;
			$body = $xml->saveXML();
			//echo '<xmp>'.$body.'</xmp>'; die;
			list($error, $response) = $this->connection->post(
                                                                            $this->path(
                                                                                    self::$workflowid, 
                                                                                    null, 
                                                                                    self::$Txn_method[2]
                                                                            ), 
                                                                            array(
                                                                                    'sessiontoken' => $this->sessionToken, 
                                                                                    'xml' => $body, 
                                                                                    'method' => self::$Txn_method[2]
                                                                                    )
                                                                     );
			return $this->handleResponse($error, $response);
			//return $response;
		} catch(Exception $e) {
			throw new Exception($e->getMessage());
		}
		
	}
	
	/*
	* Authorize a payment_method for a particular amount.
	* This Method create corresponding xml for gateway request.
	* This Method Reqest send to gateway and handle the response.
	* @param array $options this array hold "amount, paymentAccountDataToken, avsData, carddata, invoice no., order no"
	* @return array $this->handleResponse($error, $response) array of successfull or failure of gateway response. 
	*/
	
	public function authorize($options = array()) {

		try {
			$xml = Velocity_XmlCreator::auth_XML($options);  // got authorize xml object.
			$xml->formatOutput = TRUE;
			$body = $xml->saveXML();
			//echo '<xmp>'.$body.'</xmp>'; die;
			list($error, $response) = $this->connection->post(
                                                                            $this->path(
                                                                                    self::$workflowid, 
                                                                                    null, 
                                                                                    self::$Txn_method[1]
                                                                            ), 
                                                                            array(
                                                                                    'sessiontoken' => $this->sessionToken, 
                                                                                    'xml' => $body, 
                                                                                    'method' => self::$Txn_method[1]
                                                                            )
                                                                     );
			return $this->handleResponse($error, $response);
			//return $response;
		} catch (Exception $e) {
			throw new Exception( $e->getMessage() );
		}

	}

	/*
	* Captures an authorization. Optionally specify an `$amount` to do a partial capture of the initial
	* authorization. The default is to capture the full amount of the authorization.
	* @param array $options this is hold the amount, transactionid, method name. 
	* @return array $this->handleResponse($error, $response) array of successfull or failure of gateway response.
	*/
	public function capture($options = array()) {
		
		if(isset($options['amount']) && isset($options['TransactionId'])) {
			$amount = number_format($options['amount'], 2, '.', '');
			try {
				$xml = Velocity_XmlCreator::cap_XML($options['TransactionId'], $amount);  // got capture xml object.  
				$xml->formatOutput = TRUE;
				$body = $xml->saveXML();
				//echo '<xmp>'.$body.'</xmp>'; die;
				list($error, $response) = $this->connection->put(
                                                                                $this->path(
                                                                                                                self::$workflowid, 
                                                                                                                $options['TransactionId'], 
                                                                                                                self::$Txn_method[3]
                                                                                                        ), 
                                                                                 array(
                                                                                                'sessiontoken' => $this->sessionToken, 
                                                                                                'xml' => $body, 
                                                                                                'method' => self::$Txn_method[3]
                                                                                          )
                                                                        );
				//return $response;
				return $this->handleResponse($error, $response);
			} catch(Exception $e) {
				throw new Exception($e->getMessage());
			}
			
		} else {
		    throw new Exception(Velocity_Message::$descriptions['errcapsesswfltransid']);
		}
	}

	/*
	* Adjust this transaction. If the transaction has not yet been captured and settled it can be Adjust to 
	* A previously authorized amount (incremental or reversal) prior to capture and settlement. 
	* @param array $options this is hold the amount, transactionid, method name.
	* @return array $this->handleResponse($error, $response) array of successfull or failure of gateway response.
	*/
	public function adjust($options = array()) {
		
		if( isset($options['amount']) && isset($options['TransactionId']) ) {
			$amount = number_format($options['amount'], 2, '.', '');
			try {
				$xml = Velocity_XmlCreator::adjust_XML($options['TransactionId'], $amount);  // got adjust xml object.  
				$xml->formatOutput = TRUE;
				$body = $xml->saveXML();
				//echo '<xmp>'.$body.'</xmp>'; die;
				list($error, $response) = $this->connection->put(
                                                                                $this->path(
                                                                                        self::$workflowid, 
                                                                                        $options['TransactionId'], 
                                                                                        self::$Txn_method[4]
                                                                                ), 
                                                                                array(
                                                                                        'sessiontoken' => $this->sessionToken, 
                                                                                        'xml' => $body, 
                                                                                        'method' => self::$Txn_method[4]
                                                                                )
                                                                        );
				return $this->handleResponse($error, $response);
		        //return $response;
			} catch (Exception $e) {
				throw new Exception($e->getMessage());
			}	
			
		} else {
			throw new Exception(Velocity_Message::$descriptions['erradjustsesswfltransid']);
		}
	}
	
	/*
	 * The Undo operation is used to release cardholder funds by performing a void (Credit Card) or reversal (PIN Debit) on a previously 
	 * authorized transaction that has not been captured (flagged) for settlement.
	 * @param array $options this is hold the amount, transactionid, method name.
 	 * @return array $this->handleResponse($error, $response) array of successfull or failure of gateway response.
	 */
	public function undo($options = array()) {
		
		if ( isset($options['TransactionId']) ) {
		
			try {
				$xml = Velocity_XmlCreator::undo_XML($options['TransactionId']);  // got undo xml object.  
				$xml->formatOutput = TRUE;
				$body = $xml->saveXML();
				list($error, $response) = $this->connection->put( 
                                                                                $this->path(
                                                                                        self::$workflowid, 
                                                                                        $options['TransactionId'], 
                                                                                        self::$Txn_method[5]
                                                                                ), 
                                                                                array(
                                                                                        'sessiontoken' => $this->sessionToken, 
                                                                                        'xml' => $body, 
                                                                                        'method' => self::$Txn_method[5]
                                                                                ) 
                                                                        );
				//return $response;
				return $this->handleResponse($error, $response);
			} catch (Exception $e) {
				throw new Exception($e->getMessage());
			}
			
		} else {
			throw new Exception(Velocity_Message::$descriptions['errundosesswfltransid']);
		}
	}
	
	
	/*
	 * The ReturnById operation is used to perform a linked credit to a cardholder�s account from the merchant�s account based on a
	 * previously authorized and settled transaction.
	 * @param array $options this is hold the transactionid, method name.
	 * @return array $this->handleResponse($error, $response) array of successfull or failure of gateway response. 
	 */
	public function returnById($options = array()) {
		
		if(isset($options['amount']) && isset($options['TransactionId'])) {
			$amount = number_format($options['amount'], 2, '.', '');
			try {
				$xml = Velocity_XmlCreator::returnById_XML($amount, $options['TransactionId']);  // got ReturnById xml object. 
				$xml->formatOutput = TRUE;
				$body = $xml->saveXML();
				//echo '<xmp>'.$body.'</xmp>'; die;
				list($error, $response) = $this->connection->post(
                                                                                    $this->path(
                                                                                            self::$workflowid, 
                                                                                            null, 
                                                                                            self::$Txn_method[6]
                                                                                    ), 
                                                                                    array(
                                                                                            'sessiontoken' => $this->sessionToken, 
                                                                                            'xml' => $body, 
                                                                                            'method' => self::$Txn_method[6]
                                                                                    )
                                                                             );
				return $this->handleResponse($error, $response);
				//return $response;
			} catch (Exception $e) {
				throw new Exception($e->getMessage());
			}
			
		} else {
			throw new Exception(Velocity_Message::$descriptions['errreturntranidwid']);
		}  
	}

	
	/*
	 * The ReturnUnlinked operation is used to perform an "unlinked", or standalone, credit to a cardholder�s account from the merchant�s account.
	 * This operation is useful when a return transaction is not associated with a previously authorized and settled transaction.
	 * @param array $options this array hold "amount, paymentAccountDataToken, avsData, carddata, invoice no., order no"
	 * @return array $this->handleResponse($error, $response) array of successfull or failure of gateway response. 
	 */
	public function returnUnlinked($options = array()) {
		
		try {
			$xml = Velocity_XmlCreator::returnunlinked_XML($options);  // got ReturnById xml object. 
			$xml->formatOutput = TRUE;
			$body = $xml->saveXML();
			//echo '<xmp>'.$body.'</xmp>'; die;
			list($error, $response) = $this->connection->post(
                                                                            $this->path(
                                                                                    self::$workflowid, 
                                                                                    null, 
                                                                                    self::$Txn_method[7]
                                                                            ), 
                                                                            array(
                                                                                    'sessiontoken' =>  $this->sessionToken, 
                                                                                    'xml' => $body, 
                                                                                    'method' => self::$Txn_method[7]
                                                                            )
                                                                     );
			return $this->handleResponse($error, $response);
			//return $response;
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
		  
	}

	
	/* path for according to request needed 
	 * @param string $arg1 part of url for request.
	 * @param string $arg2 part of url for request.
	 * @param string $arg3 name of method.
	 * @return array $this->handleResponse($error, $response) array of successfull or failure of gateway response.	 
	 */
	private function path($arg1, $arg2, $rtype) {
		if(isset($arg1) && isset($arg2) && isset($rtype) && ( $rtype == self::$Txn_method[3] || $rtype == self::$Txn_method[4] || $rtype == self::$Txn_method[5] || $rtype == self::$Txn_method[0] ) ) {
			$path = 'Txn/'.$arg1.'/'.$arg2;
			return $path;
		} else if(isset($arg1) && isset($rtype) && ($rtype == self::$Txn_method[2] || $rtype == self::$Txn_method[6] || $rtype == self::$Txn_method[7] || $rtype == self::$Txn_method[1]) ) {
			$path = 'Txn/'.$arg1;
			return $path;
		} else {
			throw new Exception(Velocity_Message::$descriptions['errcapadjpath']);
		}
	}
	
	
	/*
	* Parses the Velocity response for messages (info or error) and updates 
	* the current transaction's information. If an HTTP error is 
	* encountered, it will be thrown from this method.
	* @param object $error error message created on the basis of gateway error status. 
	* @param array $response gateway response deatil. 
	* @return object $error error detail of gateway response.
    * @return array $response successfull/failure response of gateway.
	*/
	public function handleResponse($error, $response) {
		if ($error) {
			  return $this->processError($error, $response);
		} else {
		    if(!empty($response)) {
				if ( isset($response['BankcardTransactionResponsePro']) ) {
					return $response['BankcardTransactionResponsePro'];
				} else if ( isset($response['BankcardCaptureResponse']) ) {
					return $response['BankcardCaptureResponse'];
				} else {
					return $response;
				}
			}
		}
	}
	
	/*
	* Parses the Velocity response for error messages
	* @param object $error error message created on the basis of gateway error status. 
	* @param array $response gateway error response detail. 
	* @return object $error detail created on the basis of gateway error status.
	*/
	public function processError($error, $response) {
		if ( isset($response) )
			return $response;
		else
			return $error;
		
		$reson = isset($response['ErrorResponse']['Reason']) ? $response['ErrorResponse']['Reason'] : 'ERR';
		$validationErrors = isset($response['ErrorResponse']['ValidationErrors']) ? $response['ErrorResponse']['ValidationErrors'] : 'ERR';
		$rulemsg = isset($response['ErrorResponse']['ValidationErrors']['ValidationError']['RuleMessage']) ? $response['ErrorResponse']['ValidationErrors']['ValidationError']['RuleMessage'] : '';
		$rulekey = isset($response['ErrorResponse']['ValidationErrors']['ValidationError']['RuleKey']) ? $response['ErrorResponse']['ValidationErrors']['ValidationError']['RuleKey'] : '';
		$errorid = isset($response['ErrorResponse']['ErrorId']) ? $response['ErrorResponse']['ErrorId'] : 'ERR';

		if( $reson != 'ERR' && $validationErrors != 'ERR' && $validationErrors == '') {
			throw new Exception( $response['ErrorResponse']['Reason'] );
		} else if ( $reson != 'ERR' && $errorid != 'ERR' && $errorid == '9999' ) {
			throw new Exception( Velocity_Message::$descriptions['erstatecode'] );
		} else if ( $reson != 'ERR' && $validationErrors != 'ERR' && $validationErrors != '') {
		
			if ( count($validationErrors) == 13 )
				throw new Exception( Velocity_Message::$descriptions['errmrchtid'] );
			else if ( $rulemsg != '' && $rulekey != '' && $rulekey == 'TenderData.CardData.PAN')  
				throw new Exception( Velocity_Message::$descriptions['errpannum'] );
			else if ( $rulemsg != '' && $rulekey != '' && $rulekey == 'TenderData.CardData.Expire')  
				throw new Exception( Velocity_Message::$descriptions['errexpire'] );
			else if ( $rulemsg != '' && $rulekey != '' && $rulekey == 'TenderData.CardData.CVData')  
				throw new Exception( Velocity_Message::$descriptions['errcvdata'] );	
			else if ( $rulemsg != '' )	
				throw new Exception( $rulekey );
			else 
				throw new Exception( Velocity_Message::$descriptions['errunknown'] );
				
		} else if ($response == '') {
			return $error;
		} else {
			throw new Exception( Velocity_Message::$descriptions['errunknown'] );
		}
	}
}