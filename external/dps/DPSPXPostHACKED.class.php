<?php
/**
 * DPS interface and associated exceptions
 * 
 * For usage instructions see:
 *   http://blog.phpdeveloper.co.nz/2006/10/27/easy-dps-payment-express-interface-for-php/
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation; either version 2.1 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @author James McGlinn <james@nerdsinc.co.nz>
 * @copyright 2005, 2006 Nerds Inc. Limited http://www.nerdsinc.co.nz/
 * @license http://opensource.org/licenses/lgpl-license.php GNU Lesser General Public License
 * @package DPS
 * @version 5
 */

/**
 * Credit card processing class for DPS PXPost gateway
 *
 * @author James McGlinn <james@nerdsinc.co.nz>
 * @copyright 2005, 2006 Nerds Inc. Limited http://www.nerdsinc.co.nz/
 * @uses PEAR::HTTP_Request, PHP compiled with OpenSSL
 * @todo Status
 * @todo Auth/Complete
 * @todo Refund
 */
class DPSPXPost
{

  /**
   * The username for the DPS account
   *
   * @var string
   */
  private $post_username;

  /**
   * The password for the DPS account
   *
   * @var string
   */
  private $post_password;

  /**
   * The XML result string from the transaction
   *
   * @var string
   */
  private $dps_response;

  /**
   * The URL to the PXPost service
   * @var string
   */
  private $dps_url;

  /**
   * The request elements to be sent to DPS
   *
   * @var array
   */
  private $request_elements = array();

  /**
   * String for debug information if necessary
   *
   * @var string
   */
  private $debug_log;

  /**
   * Debug mode on or off
   *
   * @var bool
   */
  private $debug = false;

  /**
   * Constructor for DPS PXPost
   * @param string $post_username The DPS PXPost account username
   * @param string $post_password The DPS PXPost account password
   */
  public function __construct ($post_username, $post_password)
  {
    $this->post_username = $post_username;
    $this->post_password = $post_password;

    $this->dps_url       = 'https://www.paymentexpress.com/pxpost.aspx';

    $this->clearRequestElements();
  }

  /**
   * Executes a DPS PXPost request and returns parsed response data
   *
   * @return array The keyed response data
   */
  private function execute ()
  {
    $response = $this->doRequest();
    return $this->processResponse($response);
  }

  /**
   * Sets a request variable
   *
   * @param string $key The key for the request variable
   * @param string $value The value for the request variable
   * @return mixed The value of the assignment
   */
  private function setRequestElement ($key, $value)
  {
    return $this->request_elements[$key] = $value;
  }

  /**
   * Gets a request variable
   *
   * @param string $key The request variable name
   * @return mixed The request variable value if it exists, otherwise false
   */
  private function getRequestElement($key)
  {
    if (isset($this->request_elements[$key])) {
      return $this->request_elements[$key];
    }
    return false;
  }

  /**
   * Resets the request elements array
   *
   * @return array The empty list of request elements
   */
  private function clearRequestElements ()
  {
    return $this->request_elements = array();
  }

  /**
   * Sets the transaction type for the request
   *
   * @param string $txn_type The transaction type
   * @throws DPSBadTxnTypeException If the transaction type is invalid
   * @return string The transaction type
   */
  private function setTxnType ($txn_type)
  {
    // Check action is valid
    switch ($txn_type) {
      case 'Auth':
      case 'Complete':
      case 'Purchase':
      case 'Refund':
      case 'Status':
      case 'Validate':
        break;

      default:
        throw new DPSBadTxnTypeException('Invalid transaction type specified');
    }

    return $this->setRequestElement('TxnType', $txn_type);
  }

  /**
   * Assembles an XML request for DPS from the elements in the $this->request_elements array
   *
   * @throws DPSIncompleteRequestException If the required elements are not all present for the request
   * @return string The XML body for the request
   * @todo Check required elements are present
   */
  private function assembleRequest ()
  {
    // Import access credentials
    $this->setRequestElement('PostUsername', $this->post_username );
    $this->setRequestElement('PostPassword', $this->post_password );

    // Assemble request
    $doc = new DomDocument('1.0');
    $txn = $doc->createElement('Txn');
    $doc->appendChild($txn);

    foreach ($this->request_elements as $key => $value) {
      $node = $doc->createElement($key, $value);
      $txn->appendChild($node);
    }

    $doc->formatOutput = true;
    $xml = $doc->saveXml();

    // Strip XML header
    $xml = strstr($xml, '<Txn>');

    return $xml;
  }

  /**
   * Processes a response string from DPS
   *
   * @param string $response_body The response string
   * @return SimpleXML The SimpleXML object representing the response
   */
  private function processResponse ($response_body)
  {
    $xml = simplexml_load_string($response_body);
    return $xml;
  }
  
  
  function doRequest ()
  {
    $xml = $this->assembleRequest();
    if ($this->debug) {
      $this->addDebugMessage(sprintf("Outgoing XML:\n%s\n", $xml));
    }
    foreach (JOJO::listPlugins('external/snoopy/Snoopy.class.php') as $pluginfile) {require_once($pluginfile); break;}
print_r($this->request_elements);
    $snoopy = new Snoopy;
    if ($snoopy->submit($this->dps_url,$this->request_elements)) {
        if ($this->debug) {
            $this->addDebugMessage(sprintf("HTTP Response:\n%s\n", $response));
        }
        echo $snoopy->results;
        exit();
        return $snoopy->results;
    } else {
    echo print_r($snoopy->headers,true).$this->dps_url.$snoopy->results;
        exit();
        throw new DPSBadResponseException('No response received from DPS');
    }
  }
  /**
   * Actions the DPS request POST to the server
   *
   * @return string The body of the response
   * @throws DPSBadResponseException If no response received from DPS
   */
  private function doRequestORIGINAL ()
  {
    $xml = $this->assembleRequest();
    if ($this->debug) {
      $this->addDebugMessage(sprintf("Outgoing XML:\n%s\n", $xml));
    }

    require_once 'HTTP/Request.php';

    // POST request to server
    $req = new HTTP_Request($this->dps_url);
    $req->setMethod(HTTP_REQUEST_METHOD_POST);
    $req->setBody($xml);
    if (!PEAR::isError($req->sendRequest())) {

      $response = $req->getResponseBody();
      if ($this->debug) {
        $this->addDebugMessage(sprintf("HTTP Response:\n%s\n", $response));
      }
      return $response;

    } else {
      throw new DPSBadResponseException('No response received from DPS');
    }
  }

  /**
   * Loads card details into DPS system for future billing using a $1 Auth transaction (Setup Phase)
   * @param string $cardholder_name The cardholder's name as written on the card, max 64 chars
   * @param string $card_number The credit card number, numeric, max 20 chars
   * @param string $expiry_month The two digit credit card expiry month 00-12
   * @param string $expiry_year The two digit credit card expiry year 00-99
   * @param string $merchant_ref [Optional] Our reference for this card load, max 64 chars
   * @param string $billing_id [Optional] The billing ID to tag this loaded card with. If not provided, relies on DPS to generate a DpsBillingId for future identification. Max 32 chars
   * @return string The DPS BillingId for the loaded card - max 16 characters
   * @throws DPSCardHolderNameException If the cardholder name is too long
   * @throws DPSCardNumberLengthException If the card number exceeds 20 characters
   * @throws DPSCardNumberCharException If the card number has non-numeric characters
   * @throws DPSCardExpiryMonthException If the card expiry month is not in the range 00-12
   * @throws DPSCardExpiryYearException If the card expiry year is not in the range 00-99
   * @throws DPSMerchantReferenceException If the merchant reference is too long
   * @throws DPSTransactionFailureException If the card could not be loaded
   * @throws DPSBadBillingIdException If the billing ID is too long
   */
  public function loadCard ($cardholder_name, $card_number, $expiry_month, $expiry_year, $merchant_ref = null, $billing_id = null)
  {
    // Validate inputs
    if (strlen($cardholder_name) > 64) {
      throw new DPSCardHolderNameExpection('Cardholder name exceeded 64 characters');
    }

    if (strlen($card_number) > 20) {
      throw new DPSCardNumberLengthException('Card number exceeded 20 characters');
    }

    if (preg_match('/[^0-9]/', $card_number)) {
      throw new DPSCardNumberCharException('Card number contains non-numeric characters');
    }

    if ($expiry_month < '00' || $expiry_month > '12') {
      throw new DPSCardExpiryMonthException('Card expiry month is not in the range 00-12');
    }

    if ($expiry_year < '00' || $expiry_year > '99') {
      throw new DPSCardExpiryYearException('Card expiry year is not in the range 00-99');
    }

    if (strlen($merchant_ref) > 64) {
      throw new DPSMerchantReferenceException('Merchant reference exceeded 64 characters');
    }

    if (strlen($billing_id) > 32) {
      throw new DPSBadBillingIdException('Billing ID exceeded 32 characters');
    }

    $this->clearRequestElements();
    $this->setTxnType('Auth');

    $this->setRequestElement('Amount', '1.00');
    $this->setRequestElement('InputCurrency', 'NZD');
    $this->setRequestElement('CardHolderName', $cardholder_name);
    $this->setRequestElement('CardNumber', $card_number);
    $this->setRequestElement('DateExpiry', sprintf('%02d%02d', $expiry_month, $expiry_year));
    $this->setRequestElement('MerchantReference', $merchant_ref);
    $this->setRequestElement('EnableAddBillCard', 1);

    if ($billing_id !== null) {
      $this->setRequestElement('BillingId', $billing_id);
    }

    // Execute auth transaction
    $response = $this->execute();

    if (((int) $response->Success) == 1) {
      // Transaction success
      $dpsBillingId = (string) $response->Transaction->DpsBillingId;
      return $dpsBillingId;
    }

    // Transaction failed
    $error = (string) $response->HelpText;
    throw new DPSTransactionFailureException($error);
  }

  /**
   * Charge a card already loaded into the DPS system (Rebill Phase)
   *
   * @param float $amount The amount to bill the loaded card
   * @param string $merchant_ref [Optional] Our reference for this card load, max 64 chars
   * @param bool $is_dps_billing_id Whether the billing ID is one generated by DPS or our system. True for DPS, false for us. Default is true
   * @return string The DPS Transaction ID for the successful transaction
   * @throws DPSMerchantReferenceException If the merchant reference is too long
   * @throws DPSTransactionFailureException If the loaded card could not be charged
   * @throws DPSBadBillingIdException If no billing ID is provided
   */
  public function chargeLoadedCard ($billing_id, $amount, $merchant_ref = null, $is_dps_billing_id = true)
  {
    // Validate inputs
    if (empty($billing_id)) {
      throw new DPSBadBillingIdException('Billing ID must be provided');
    }

    // Charge by Billing ID
    $this->clearRequestElements();
    $billing_id_element = $is_dps_billing_id === true ? 'DpsBillingId' : 'BillingId';
    $this->setRequestElement($billing_id_element, $billing_id);

    return $this->charge($amount, $merchant_ref);
  }

  /**
   * Charges an amount to the specified card
   *
   * @param string $cardholder_name The cardholder's name as written on the card, max 64 chars
   * @param string $card_number The credit card number, numeric, max 20 chars
   * @param string $expiry_month The two digit credit card expiry month 00-12
   * @param string $expiry_year The two digit credit card expiry year 00-99
   * @param float $amount The amount to bill the loaded card
   * @param string $merchant_ref [Optional] Our reference for this card load, max 64 chars
   * @return string The DPS Transaction ID for the successful transaction
   * @throws DPSCardHolderNameException If the cardholder name is too long
   * @throws DPSCardNumberLengthException If the card number exceeds 20 characters
   * @throws DPSCardNumberCharException If the card number has non-numeric characters
   * @throws DPSCardExpiryMonthException If the card expiry month is not in the range 00-12
   * @throws DPSCardExpiryYearException If the card expiry year is not in the range 00-99
   * @throws DPSMerchantReferenceException If the merchant reference is too long
   * @throws DPSTransactionFailureException If the card could not be loaded
   */
  public function chargeCard ($cardholder_name, $card_number, $expiry_month, $expiry_year, $amount, $merchant_ref = null)
  {
    // Validate inputs
    if (strlen($cardholder_name) > 64) {
      throw new DPSCardHolderNameExpection('Cardholder name exceeded 64 characters');
    }

    if (strlen($card_number) > 20) {
      throw new DPSCardNumberLengthException('Card number exceeded 20 characters');
    }

    if (preg_match('/[^0-9]/', $card_number)) {
      throw new DPSCardNumberCharException('Card number contains non-numeric characters');
    }

    if ($expiry_month < '00' || $expiry_month > '12') {
      throw new DPSCardExpiryMonthException('Card expiry month is not in the range 00-12');
    }

    if ($expiry_year < '00' || $expiry_year > '99') {
      throw new DPSCardExpiryYearException('Card expiry year is not in the range 00-99');
    }

    // Charge by Credit Card
    $this->clearRequestElements();
    $this->setRequestElement('CardHolderName', $cardholder_name);
    $this->setRequestElement('CardNumber', $card_number);
    $this->setRequestElement('DateExpiry', sprintf('%02d%02d', $expiry_month, $expiry_year));

    return $this->charge($amount, $merchant_ref);

  }

  /**
   * Conduct a charge transaction - either with full card details or to a preloaded card
   *
   * @param float $amount The amount to charge
   * @param string $merchant_ref [Optional] Our reference for this card load, max 64 chars
   */
  private function charge ($amount, $merchant_ref)
  {
    // Validate inputs
    if (strlen($merchant_ref) > 64) {
      throw new DPSMerchantReferenceException('Merchant reference exceeded 64 characters');
    }

    if (!is_float($amount)) {
      throw new DPSBadAmountException('Purchase Amount must be a floating point value');
    }

    $this->setTxnType('Purchase');

    $this->setRequestElement('Amount', sprintf('%.02f', $amount));
    $this->setRequestElement('InputCurrency', 'NZD');
    $this->setRequestElement('MerchantReference', $merchant_ref);
    $this->setRequestElement('EnableAddBillCard', 0);

    // Execute transaction
    $response = $this->execute();

    if (((int) $response->Success) == 1) {
      // Transaction success
      $txn_ref = (string) $response->DpsTxnRef;
      return $txn_ref;
    }

    // Transaction failed
    $error = (string) $response->HelpText;
    
    throw new DPSTransactionFailureException($error);
  }

  /**
   * Set debug mode on or off
   *
   * @param bool $debug True to enable debugging
   * @return bool
   */
  public function setDebug($debug)
  {
    return $this->debug = (bool) $debug;
  }

  /**
   * Add a message to the debug log
   *
   * @param string $message The message to add
   * @return string
   */
  private function addDebugMessage($message)
  {
    return $this->debug_log .= sprintf("%s\n", $message);
  }

  /**
   * Retrieve the debug log
   *
   * @return string
   */
  public function getDebugLog()
  {
    return $this->debug_log;
  }

}

// Exceptions
class DPSCardHolderNameException      extends Exception {}
class DPSCardNumberLengthException    extends Exception {}
class DPSCardNumberCharException      extends Exception {}
class DPSCardExpiryMonthException     extends Exception {}
class DPSCardExpiryYearException      extends Exception {}
class DPSMerchantReferenceException   extends Exception {}
class DPSBadTxnTypeException          extends Exception {}
class DPSIncompleteRequestException   extends Exception {}
class DPSBadResponseException         extends Exception {}
class DPSTransactionFailureException  extends Exception {}
class DPSBadDpsBillingIdException     extends Exception {}
class DPSBadBillingIdException        extends Exception {}
class DPSBadAmountException           extends Exception {}

?>
