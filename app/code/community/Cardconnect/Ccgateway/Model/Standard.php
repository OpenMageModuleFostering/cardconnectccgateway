<?php

/**
 * @brief Defines the class representing CardConnect webservices
 * @category Magento CardConnect Payment Module
 * @author CardConnect
 * @copyright Portions copyright 2014 CardConnect
 * @copyright Portions copyright Magento 2014
 * @license GPL v2, please see LICENSE.txt
 * @access public
 * @version $Id: $
 *
 * */
/**
  Magento
 *
  NOTICE OF LICENSE
 *
  This source file is subject to the Open Software License (OSL 3.0)
  that is bundled with this package in the file LICENSE.txt.
  It is also available through the world-wide-web at this URL:
  http://opensource.org/licenses/osl-3.0.php
  If you did not receive a copy of the license and are unable to
  obtain it through the world-wide-web, please send an email
  to license@magentocommerce.com so we can send you a copy immediately.
 *
  @category Cardconnect
  @package Cardconnect_Ccgateway
  @copyright Copyright (c) 2014 CardConnect (http://www.cardconnect.com)
  @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
require('cardconnect_webservice.php');

class Cardconnect_Ccgateway_Model_Standard extends Mage_Payment_Model_Method_Abstract {

    protected $_code = 'ccgateway';
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canVoid = true;
    protected $_canUseInternal = true;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = true;
    protected $_canCapturePartial = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canRefund = true;
    protected $_paymentMethod = 'standard';
    protected $_formBlockType = 'ccgateway/form';
    protected $_infoBlockType = 'ccgateway/info';
    protected $_redirectBlockType = 'ccgateway/redirect';
    protected $_order;
    protected $_canCancelInvoice = true;
    protected $_canSaveCc = true;

    protected function _construct() {
        parent::_construct();
    }

/**
     * Return payment url type string
     *
     * @return string
     */
    public function getUrl() {

        $isTestMode = Mage::getStoreConfig('payment/ccgateway/test_mode');
        switch ($isTestMode) {
            case 0:
                $_url = 'https://securepayments.cardconnect.com/hpp/payment/';         
                break;
            default:
			      $_url = 'https://securepaymentstest.cardconnect.com/hpp/payment/';
                break;
        }

        return $_url;
    }

    /**
     * Return webservices url type string
     *
     * @return string
     */
    public function getWebServicesUrl() {

        $isTestMode = Mage::getStoreConfig('payment/ccgateway/test_mode');
        switch ($isTestMode) {
            case 0:
               $_webServicesUrl = 'https://fts.prinpay.com:8443/cardconnect/rest/'; 
                break;
            default:
                $_webServicesUrl = 'https://fts.prinpay.com:6443/cardconnect/rest/';  
                break;
        }

        return $_webServicesUrl;
    }
    /**
     * Return webservices url type string
     *
     * @return string
     */
    public function getCardSecureApiUrl() {

        $isTestMode = Mage::getStoreConfig('payment/ccgateway/test_mode');
        switch ($isTestMode) {
            case 0:
                $_cardSecureApiUrl = "https://fts.prinpay.com:8443/cardsecure/cs";
                break;
            default:
                $_cardSecureApiUrl = 'https://fts.prinpay.com:6443/cardsecure/cs';
                break;
        }

        return $_cardSecureApiUrl;
    }

    /**
     * Return webservices keys location type string
     *
     * @return string
     */
    public function getKeysLocation() {

        $keys_location = Mage::getModuleDir('', 'Cardconnect_Ccgateway') . '/cc_keys/';

        return $keys_location;
    }

    /**
     * Check capture availability
     *
     * @return bool
     */
    public function canCapture() {

        if ($this->getPaymentTransactionType() == "authorize_capture") {
            $_canCapture = false;
        } else {
            $_canCapture = true;
        }

        return $_canCapture;
    }

    /**
     * Get order model
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder() {
        if (!$this->_order) {
            $this->_order = $this->getInfoInstance()->getOrder();
        }
        return $this->_order;
    }

    public function getOrderPlaceRedirectUrl() {
        return Mage::getUrl('ccgateway/payment/redirect');
    }

    /**
     * Get Payment transaction type
     */
    public function getPaymentTransactionType() {
        $checkout_trans = Mage::getStoreConfig('payment/ccgateway/checkout_trans');

        return $checkout_trans;
    }

    /**
     * Return payment method type string
     *
     * @return string
     */
    public function getPaymentMethodType() {
        return $this->_paymentMethod;
    }

    /**
     * Check refund availability
     *
     * @return bool
     */
    public function canRefund() {

        $_canRefund = true;

        return $_canRefund;
    }

    /**
     * prepare params array to send it to gateway page via POST
     * 
     * NOTE: Currency is not a parameter, it is configured by CardConnect in the merchant profile. 
     * 
     * @return array
     */
    public function getFormFields() {
        // get transaction amount and currency

        if (Mage::getStoreConfig('payment/ccgateway/currency')) {
            $price = number_format($this->getOrder()->getGrandTotal(), 2, '.', '');
        } else {
            $price = number_format($this->getOrder()->getBaseGrandTotal(), 2, '.', '');
        }

        $billing = $this->getOrder()->getBillingAddress();

        $ccArray = array(
            'ccId' => Mage::getStoreConfig('payment/ccgateway/card_id'), 						/* CardConnect Id */
            'ccSite' => Mage::getStoreConfig('payment/ccgateway/site_name'), 					/* Site Name */
            'ccDisplayAddress' => Mage::getStoreConfig('payment/ccgateway/address'), 			/* Display Address */
            'ccCapture' => Mage::getStoreConfig('payment/ccgateway/checkout_trans'), 			/* Checkout Transaction Type */
            'ccTokenize' => Mage::getStoreConfig('payment/ccgateway/tokenize'), 				/* Tokenize */
            'ccDisplayCvv' => Mage::getStoreConfig('payment/ccgateway/display'), 				/* Display CVV */
            'ccAmount' => $price, 																/* Transaction Amount */
            'ccName' => Mage::helper('core')->removeAccents($billing->getFirstname()
                    . ' ' . $billing->getLastname()), 											/* Account Name */
            'ccAddress' => Mage::helper('core')->removeAccents($billing->getStreet(1)), 		/* Account street address */
            'ccCity' => Mage::helper('core')->removeAccents($billing->getCity()), 				/* Account city */
            'ccState' => $billing->getRegionCode(),												/* US State, Mexican State, Canadian Province, etc. */
            'ccCountry' => $billing->getCountry(), 												/* Account country */
            'ccZip' => $billing->getPostcode(), 												/* Account postal code */
            'ccCardTypes' => Mage::getStoreConfig('payment/ccgateway/card_type'),
            'ccOrderId' => Mage::getSingleton('checkout/session')->getLastRealOrderId(), 		/* Order Id */
            'ccCssUrl' => Mage::getStoreConfig('payment/ccgateway/css'), 						/* CSS URL */
            'ccPostbackUrl' => Mage::getUrl('ccgateway/payment/response'), 						/* Postback URL */
            'ccAsync' => Mage::getStoreConfig('payment/ccgateway/validation'), 					/* Immediate Validation */
            'ccCancel' => Mage::getStoreConfig('payment/ccgateway/cancel'), 					/* Cancel Button enable flag */
        );

        return $ccArray;
    }

    /**
     * Assign data to info model instance
     *
     * @param   mixed $data
     * @return  Mage_Payment_Model_Info
     */
    public function assignData($data) {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }

        Mage::getSingleton('core/session')->setCcOwner($data->getCcOwner());
        Mage::getSingleton('core/session')->setCcNumber($data->getCcNumber());
        Mage::getSingleton('core/session')->setCcType($data->getCcType());
        Mage::getSingleton('core/session')->setCcExpMonth($data->getCcExpMonth());
        Mage::getSingleton('core/session')->setCcExpYear($data->getCcExpYear());
        Mage::getSingleton('core/session')->setCcCid($data->getCcCid());

        $value['profile_name'] = "";
        foreach ($data as $value) {
            if (isset($value['profile_name']) || @$value['profile_name'] != "Checkout with new card") {
                Mage::getSingleton('core/session')->setCcProfileid(@$value['profile_name']);
            }
        }

        $info = $this->getInfoInstance();
        $info->setCcType($data->getCcType())
                ->setCcOwner($data->getCcOwner())
                ->setCcLast4(substr($data->getCcNumber(), -4))
                ->setCcNumber($data->getCcNumber())
                ->setCcCid($data->getCcCid())
                ->setCcExpMonth($data->getCcExpMonth())
                ->setCcExpYear($data->getCcExpYear())
                ->setCcSsIssue($data->getCcSsIssue())
                ->setCcSsStartMonth($data->getCcSsStartMonth())
                ->setCcSsStartYear($data->getCcSsStartYear())
        ;
        return $this;
    }

    /**
     * Prepare info instance for save
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function prepareSave() {
        $info = $this->getInfoInstance();
        if ($this->_canSaveCc) {
            $info->setCcNumberEnc($info->encrypt($info->getCcNumber()));
        }

        $info->setCcCidEnc($info->encrypt($info->getCcCid()));

        return $this;
    }

    /** For Authorization * */
    public function authService($order, $authAmount = "", $status = "") {

        $orderId = $order->getIncrementId();
        $merchid = Mage::getStoreConfig('payment/ccgateway/merchant');
        $passWord = Mage::getStoreConfig('payment/ccgateway/password');
        $cc_password = Mage::getModel('core/encryption')->decrypt($passWord);

        if (empty($status)) {
            $ccOwner = Mage::getSingleton('core/session')->getCcOwner();
            $ccNumber = Mage::getSingleton('core/session')->getCcNumber();
            $ccType = Mage::getSingleton('core/session')->getCcType();
            $ccExpiry = Mage::getSingleton('core/session')->getCcExpMonth() . substr(Mage::getSingleton('core/session')->getCcExpYear(), 2);
            $ccCvv2 = Mage::getSingleton('core/session')->getCcCid();
            $price = number_format($order->getBaseGrandTotal(), 2, '.', '');
            $profileId = Mage::getSingleton('core/session')->getCcProfileid();
        } else {
            // For Pratial Shipment Reauthorization
            $quote_id = $order->getQuoteId();
            $collection = Mage::getModel('sales/quote_payment')->getCollection()
                    ->addFieldToFilter('quote_id', array('eq' => $quote_id));

            foreach ($collection as $data) {
                $ccOwner = $data->getData("cc_owner");
                $ccType = $data->getData("cc_type");
                $ccNumber = Mage::getModel('core/encryption')->decrypt($data->getData("cc_number_enc"));
                $ccExpiry = $data->getData("cc_exp_month") . substr($data->getData("cc_exp_year"), 2);
                $ccCvv2 = Mage::getModel('core/encryption')->decrypt($data->getData("cc_cid_enc"));
            }
            $price = $authAmount;
        }


        $billing = $order->getBillingAddress();

        if (empty($status) || $status == "authFull") {
            $checkout_trans = $this->getPaymentTransactionType();
        } else {
            $checkout_trans = "authorize_capture";
        }

        if ($checkout_trans == "authorize_capture") {
            $captureStatus = 1;
        } else {
            $captureStatus = 0;
        }

        if (strlen($ccExpiry) < 4) {
            $ccExpiry = "0" . $ccExpiry;
        }

        if (!empty($profileId)) {
            $param = array(
                'profileid' => $profileId,
                'order_id' => $orderId,
                'currency_value' => $price,
                'cvv_val' => $ccCvv2,
                'ecomind' => "E",
                'capture' => $captureStatus,
                'tokenize' => 'Y');
        } else {
            $param = array(
                'merchid' => $merchid,
                'acc_type' => $ccType,
                'order_id' => $orderId,
                'acc_num' => $ccNumber,
                'expirydt' => $ccExpiry,
                'currency_value' => $price,
                'currency' => "USD",
                'cc_owner' => $ccOwner,
                'billing_street_address' => $billing->getStreet(1),
                'billing_city' => $billing->getCity(),
                'billing_state' => $billing->getRegionCode(),
                'billing_country' => $billing->getCountry(),
                'billing_postcode' => $billing->getPostcode(),
                'ecomind' => "E",
                'cvv_val' => $ccCvv2,
                'track' => null,
                'capture' => $captureStatus,
                'tokenize' => 'Y');
        }

        $cc = new CardConnectWebService($this->getWebServicesUrl(), Mage::getStoreConfig('payment/ccgateway/username'), $cc_password, Mage::getStoreConfig('payment/ccgateway/merchant'), $this->getKeysLocation());

        $resp = $cc->authService($param);

        if (empty($status)) {
            Mage::getSingleton('core/session')->unsCcOwner();
            Mage::getSingleton('core/session')->unsCcNumber();
            Mage::getSingleton('core/session')->unsCcType();
            Mage::getSingleton('core/session')->unsCcExpMonth();
            Mage::getSingleton('core/session')->unsCcExpYear();
            Mage::getSingleton('core/session')->unsCcCid();
            Mage::getSingleton('core/session')->unsCcProfileid();
        }

        if ($resp != "") {
            $response = json_decode($resp, true);

            $response['orderid'] = $orderId;

            if (!empty($status)) {
                $response['action'] = $checkout_trans;
                $response['merchid'] = $merchid;
                $response['setlstat'] = "";
                $response['voidflag'] = "";

                // Save Partial Authorization Response data
                $this->saveResponseData($response);
                // Set custom order status
                $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, 'cardconnect_processing', $response['resptext'])->save();
            }
        } else {
			$myLogMessage = "CC Authorization : ". __FILE__ . " @ " . __LINE__ ."  ".$cc->getLastErrorMessage();
			Mage::log($myLogMessage, Zend_Log::ERR , "cc.log" );

            // Set custom order status
            $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, 'cardconnect_reject', "Invalid response from CardConnect.")->save();
			$response= array('resptext' => "CardConnect_Error");

        }


        return $response;
    }

    /** For card purge * */
    public function cardPurgeService($tokenNum) {

        $passWord = Mage::getStoreConfig('payment/ccgateway/password');
        $cc_password = Mage::getModel('core/encryption')->decrypt($passWord);

        $cc = new CardConnectWebService($this->getCardSecureApiUrl(), Mage::getStoreConfig('payment/ccgateway/username'), $cc_password, Mage::getStoreConfig('payment/ccgateway/merchant'), $this->getKeysLocation());


        $resp = $cc->cardPurgeService("CP", "xml", $tokenNum);

        $response = simplexml_load_string($resp) or die("Error: Cannot create object");
        Mage:: log($response->data);


        return $response->data;
    }

    /** For capture * */
    public function capture(Varien_Object $payment, $amount) {

        if (!$this->canCapture()) {
            return $this;
        }

        if ($amount <= 0) {
            Mage::throwException(Mage::helper('ccgateway')->__('Invalid amount for capture.'));
        }
        $order = $payment->getOrder();
        $orderId = $order->increment_id;
        $fullAuthorizedAmount = $order->getBaseGrandTotal();
        if (strpos('.', $amount) == "") {
            $amount = number_format($amount, 2);
            $fullAuthorizedAmount = number_format($fullAuthorizedAmount, 2);
        }
        $amount = str_replace(",", "", $amount);
        $fullAuthorizedAmount = str_replace(",", "", $fullAuthorizedAmount);

        $canCapture = $this->checkCaptureOnceDone($orderId);
        if ($canCapture == TRUE) {
            $this->authService($order, $amount, "authPartial");
        } else {
            $retref = $this->getRetrefReferenceNumber($orderId);
            $authCode = $this->getAuthCode($orderId);
            $checkout_trans = $this->getPaymentTransactionType();
            $merchid = Mage::getStoreConfig('payment/ccgateway/merchant');

            $passWord = Mage::getStoreConfig('payment/ccgateway/password');
            $cc_password = Mage::getModel('core/encryption')->decrypt($passWord);

            $cc = new CardConnectWebService($this->getWebServicesUrl(), Mage::getStoreConfig('payment/ccgateway/username'), $cc_password, Mage::getStoreConfig('payment/ccgateway/merchant'), $this->getKeysLocation());


            if ($fullAuthorizedAmount == $amount) {
                $resp = $cc->captureService($retref, $authCode, $amount, $orderId);
            } else {
                $amountForVoid = $fullAuthorizedAmount - $amount;
                $this->voidService($order, $amountForVoid, "Partial");
                $resp = $cc->captureService($retref, $authCode, $amount, $orderId);
            }

            if ($resp != "") {
                if ($checkout_trans != "authorize_capture") {
                    $response = json_decode($resp, true);

                    $response['action'] = "Capture";
                    $response['orderid'] = $orderId;
                    $response['merchid'] = $merchid;
                    $response['authcode'] = "";
                    $response['voidflag'] = "";

                    // Save Capture Response data
                    $this->saveResponseData($response);
                    // Set custom order status
                    $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, 'cardconnect_capture', $response['setlstat'])->save();
                }
            } else {
                $myLogMessage = "CC Capture : ". __FILE__ . " @ " . __LINE__ ."  ".$cc->getLastErrorMessage();
				Mage::log($myLogMessage, Zend_Log::ERR , "cc.log" );		
				
                // Set custom order status
                $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, 'cardconnect_reject', "Invalid response from CardConnect.")->save();
                $errorMsg = "Unable to perform operation.  Please consult the Magento log for additional information.";
                Mage::throwException($errorMsg);

            }
        }

        return $this;
    }

// Check capture once performed for an order
    function checkCaptureOnceDone($orderId) {

        $collection = Mage::getModel('cardconnect_ccgateway/cardconnect_resp')->getCollection()
                ->addFieldToFilter('CC_ORDERID', array('eq' => $orderId))
                ->addFieldToSelect('CC_ACTION');

        $cc_action = array();
        foreach ($collection as $data) {
            $cc_action[] = $data->getData('CC_ACTION');
        }
        if (in_array("Capture", $cc_action)) {
            $c_status = TRUE;
        } else {
            $c_status = FALSE;
        }

        return $c_status;
    }

// Void function using web services    
    public function voidService($order, $partialAmount = "", $action = "") {

        $orderId = $order->getIncrementId();
        $retref = $this->getRetrefReferenceNumber($orderId);
        if (empty($partialAmount)) {
            $amount = $order->getBaseGrandTotal();
        } else {
            $amount = $partialAmount;
        }

        if (strpos('.', $amount) == "") {
            $amount = number_format($amount, 2);
        }
        $amount = str_replace(",", "", $amount);
        $passWord = Mage::getStoreConfig('payment/ccgateway/password');
        $cc_password = Mage::getModel('core/encryption')->decrypt($passWord);

        $cc = new CardConnectWebService($this->getWebServicesUrl(), Mage::getStoreConfig('payment/ccgateway/username'), $cc_password, Mage::getStoreConfig('payment/ccgateway/merchant'), $this->getKeysLocation());

        $resp = $cc->voidService($retref, $amount);

        if ($resp != "") {
            $response = json_decode($resp, true);
            $response['action'] = "Void";
            $response['orderid'] = $orderId;
            $response['setlstat'] = "";
            $response['voidflag'] = "";

            // Save Void Response data        
            $this->saveResponseData($response);

            if (empty($action)) {
                // Set custom order status
                $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, 'cardconnect_void', $response['resptext'])->save();
            }
        } else {
			$myLogMessage = "CC Void : ". __FILE__ . " @ " . __LINE__ ."  ".$cc->getLastErrorMessage();
			Mage::log($myLogMessage, Zend_Log::ERR , "cc.log" );				
			
            // Set custom order status
            $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, 'cardconnect_reject', "Invalid response from CardConnect.")->save();

            $errorMsg = "Unable to perform operation.  Please consult the Magento log for additional information.";
            Mage::throwException($errorMsg);

        }

        return $this;
    }

// Check the Capture status for a current order     
    public function getVoidStatus($order) {

        $orderId = $order->getIncrementId();

        $collection = Mage::getModel('cardconnect_ccgateway/cardconnect_resp')->getCollection()
                ->addFieldToFilter('CC_ORDERID', array('eq' => $orderId))
                ->addFieldToSelect('CC_ACTION');

        $cc_action = array();
        foreach ($collection as $data) {
            $cc_action[] = $data->getData('CC_ACTION');
        }
        if (in_array("Void", $cc_action)) {
            $c_status = false;
        } else {
            $c_status = true;
        }

        return $c_status;
    }

// Check payment settlement satatus before refund  

    public function processBeforeRefund($invoice, $payment) {
        $order = $payment->getOrder();
        $orderId = $order->increment_id;
        $retref = $this->getRetrefReferenceNumber($orderId, "Refund");
        $passWord = Mage::getStoreConfig('payment/ccgateway/password');
        $cc_password = Mage::getModel('core/encryption')->decrypt($passWord);

        $cc = new CardConnectWebService($this->getWebServicesUrl(), Mage::getStoreConfig('payment/ccgateway/username'), $cc_password, Mage::getStoreConfig('payment/ccgateway/merchant'), $this->getKeysLocation());

        $resp = $cc->inquireService($retref);
        $response = json_decode($resp, true);

        if ($response['setlstat'] == "Accepted") {
            $status = "true";
            Mage::log('Txn settled for your order Id: ' . $orderId);
        } else {
            $status = "false";
            Mage::throwException("Refund cannot be processed, transaction should be settled first.");
        }

        return $this;
    }

// Refund function using web services

    public function refund(Varien_Object $payment, $amount) {

        if (!$this->canRefund()) {
            return $this;
        }

        $order = $payment->getOrder();
        $orderId = $order->increment_id;
        $retref = $this->getRetrefReferenceNumber($orderId, "Refund");
        $merchid = Mage::getStoreConfig('payment/ccgateway/merchant');
        if ($amount <= 0) {
            Mage::throwException(Mage::helper('ccgateway')->__('Invalid amount for refund.'));
        }

        if (strpos('.', $amount) == "") {
            $amount = number_format($amount, 2);
        }

        $amount = str_replace(",", "", $amount);
        $passWord = Mage::getStoreConfig('payment/ccgateway/password');
        $cc_password = Mage::getModel('core/encryption')->decrypt($passWord);

        $cc = new CardConnectWebService($this->getWebServicesUrl(), Mage::getStoreConfig('payment/ccgateway/username'), $cc_password, Mage::getStoreConfig('payment/ccgateway/merchant'), $this->getKeysLocation());

        $resp = $cc->refundService($retref, $amount);

        if ($resp != "") {
            $response = json_decode($resp, true);

            $response['action'] = "Refund";
            $response['orderid'] = $orderId;
            $response['merchid'] = $merchid;
            $response['setlstat'] = "";
            $response['authcode'] = "";
            $response['voidflag'] = "";

            // Save Refund Response data    
            $this->saveResponseData($response);
            // Set custom order status
            $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, 'cardconnect_refund', $response['resptext'])->save();
        } else {
			
			$myLogMessage = "CC Refund : ". __FILE__ . " @ " . __LINE__ ."  ".$cc->getLastErrorMessage();
			Mage::log($myLogMessage, Zend_Log::ERR , "cc.log" );				
			
            // Set custom order status
            $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, 'cardconnect_reject', "Invalid response from CardConnect.")->save();
            $errorMsg = "Unable to perform operation.  Please consult the Magento log for additional information.";
            Mage::throwException($errorMsg);
            
        }

        return $this;
    }

// Inquire function using web services
    public function inquireService($orderId) {

        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($orderId);
        $responseData = $this->getResponseDataByOrderId($orderId);
        $passWord = Mage::getStoreConfig('payment/ccgateway/password');
        $cc_password = Mage::getModel('core/encryption')->decrypt($passWord);

        $response = "";
        $errorMsg = 0;
        $message = "";

        if ($cc_password != "") {
            if ($responseData->count() != 0) {
                foreach ($responseData as $data) {
                    $ccId = $data->getData('CC_ID');
                    $ccAction = $data->getData('CC_ACTION');
                    $retref = $data->getData('CC_RETREF');
                    $order_amount = $data->getData('CC_AMT');
                    $setlstat = $data->getData('CC_SETLSTAT');


                    $cc = new CardConnectWebService($this->getWebServicesUrl(), Mage::getStoreConfig('payment/ccgateway/username'), $cc_password, Mage::getStoreConfig('payment/ccgateway/merchant'), $this->getKeysLocation());

                    $resp = $cc->inquireService($retref);
                    $response = json_decode($resp, true);
                    if (!empty($response)) {
                        if (abs($response['amount']) == $order_amount || abs($response['amount']) == '0.00') {
                            if ($response['setlstat'] == 'Accepted' || $response['setlstat'] == 'Voided') {
                                if ($ccAction == 'Refund') {
                                    $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, 'cardconnect_refund', $response['setlstat'])->save();
                                }
                                if ($ccAction == 'authorize' || $ccAction == 'authorize_capture') {
                                    if ($response['setlstat'] == 'Voided') {
                                        $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, 'cardconnect_void', $response['setlstat'])->save();
                                    } else if ($response['setlstat'] == 'Accepted') {
                                        $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, 'cardconnect_txn_settled', $response['setlstat'])->save();
                                    }
                                }
                                if ($ccAction == 'Void') {
                                    $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, 'cardconnect_void', $response['setlstat'])->save();
                                }
                            } else if ($response['setlstat'] == 'Rejected') {
                                $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, 'cardconnect_reject', $response['setlstat'])->save();
                            }

                            $stat = strpos($setlstat, $response['setlstat']);
                            if ($stat !== false) {
                                $message = "status matched";
                                Mage::log('Current status matched with Inquire status');
                            } else if ($response['setlstat'] !== 'Authorized' && $response['setlstat'] !== 'Queued for Capture' && $response['setlstat'] !== '' && $response['setlstat'] !== 'Refunded') {
                                $fields = array('CC_SETLSTAT' => $response['setlstat']);
                                $this->updateAfterInquireService($fields, $ccId);
                            }
                        } else if ($ccAction == 'Refund') {
                            $cmp_setlstat = strpos($response['setlstat'], $setlstat);
                            if ($cmp_setlstat !== false) {
                                $message = "status matched";
                                Mage::log('Current Refund status matched with Inquire status');
                            } else {
                                $fields = array('CC_SETLSTAT' => $response['setlstat']);
                                $this->updateAfterInquireService($fields, $ccId);
                            }
                        }
                    } else {
                        $errorMsg = 1;
						$myLogMessage = "CC Inquire : ". __FILE__ . " @ " . __LINE__ ."  ".$cc->getLastErrorMessage();
						Mage::log($myLogMessage, Zend_Log::ERR , "cc.log" );							
						
                    }
                }
            } else {
                $message = "status matched";
            }
        } else {
            Mage::log("Unable to get decrypted password");
        }

        if ($message == "status matched") {
            $message = "Current status matched with Inquire status";
        } else if ($errorMsg == 1) {
            $message = "There is some problem  in inquire services";
        } else {
            $message = "Successfully Inquired";
        }

        return $message;
    }

// Create Profile webservices     
    function createProfileService($paymentInformation) {

        $passWord = Mage::getStoreConfig('payment/ccgateway/password');
        $cc_password = Mage::getModel('core/encryption')->decrypt($passWord);

        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customerData = Mage::getSingleton('customer/session')->getCustomer();
            $ccUserId = $customerData->getId();
        }

        $ccCardName = $paymentInformation['cc_profile_name'];
        $ccExpiry = $paymentInformation['cc_exp_month'] . substr($paymentInformation['cc_exp_year'], 2);
        if (strlen($ccExpiry) < 4) {
            $ccExpiry = "0" . $ccExpiry;
        }

        $profrequest = array(
            'defaultacct' => "N",
            'profile' => "",
            'profileupdate' => "N",
            'account' => $paymentInformation['cc_number'],
            'accttype' => $paymentInformation['cc_type'],
            'expiry' => $ccExpiry,
            'name' => $paymentInformation['cc_owner'],
            'address' => $paymentInformation['cc_street'],
            'city' => $paymentInformation['cc_city'],
            'region' => $paymentInformation['cc_region'],
            'country' => $paymentInformation['cc_country'],
            'phone' => $paymentInformation['cc_telephone'],
            'postal' => $paymentInformation['cc_postcode']
        );

        $cc = new CardConnectWebService($this->getWebServicesUrl(), Mage::getStoreConfig('payment/ccgateway/username'), $cc_password, Mage::getStoreConfig('payment/ccgateway/merchant'), $this->getKeysLocation());

        $resp = $cc->createProfileService($profrequest);

        if ($resp != "") {
            $response = json_decode($resp, true);

            if ($response['resptext'] == "Profile Saved") {
                $response['ccUserId'] = $ccUserId;
                $response['ccCardName'] = $ccCardName;
                if ($this->hasWalletCard($response['ccUserId']) == "Yes") {
                    $response['defaultacct'] = "N";
                } else {
                    $response['defaultacct'] = "Y";
                }

                // Save Refund Response data    
                $this->saveResponseData($response, "Wallat");
            }
        } else {
			$myLogMessage = "CC Create Profile Service : ". __FILE__ . " @ " . __LINE__ ."  ".$cc->getLastErrorMessage();
			Mage::log($myLogMessage, Zend_Log::ERR , "cc.log" );
			$response= array('resptext' => "CardConnect_Error");
        }

        return $response;
    }

// Function for Get Profile webservices
    function getProfileWebService($profileId, $cc_id) {

        $passWord = Mage::getStoreConfig('payment/ccgateway/password');
        $cc_password = Mage::getModel('core/encryption')->decrypt($passWord);
	
        $cc = new CardConnectWebService($this->getWebServicesUrl(), Mage::getStoreConfig('payment/ccgateway/username'), $cc_password, Mage::getStoreConfig('payment/ccgateway/merchant'), $this->getKeysLocation());
        $resp = $cc->getProfileService($profileId);
        if (!empty($resp) && $cc_id != "") {
            $resource = Mage::getSingleton('core/resource');
            $readConnection = $resource->getConnection('core_read');
            $writeConnection = $resource->getConnection('core_write');
            $getTable = $resource->getTableName('cardconnect_wallet');

            $selQry = "SELECT CC_CARD_NAME FROM {$getTable} WHERE CC_ID=" . $cc_id;
            $rsCard = $readConnection->fetchRow($selQry);
            $resp = json_decode($resp, true);
            $resp[] = $rsCard['CC_CARD_NAME'];
        } else {
			$myLogMessage = "CC Get Profile Service : ". __FILE__ . " @ " . __LINE__ ."  ".$cc->getLastErrorMessage();
			Mage::log($myLogMessage, Zend_Log::ERR , "cc.log" );
			$resp[] = array('resptext' => "CardConnect_Error");

        }

        return $resp;
    }

// Function for Get Profile webservices Checkout
    function getProfileWebServiceCheckout($profileId) {

        $passWord = Mage::getStoreConfig('payment/ccgateway/password');
        $cc_password = Mage::getModel('core/encryption')->decrypt($passWord);
	
        $cc = new CardConnectWebService($this->getWebServicesUrl(), Mage::getStoreConfig('payment/ccgateway/username'), $cc_password, Mage::getStoreConfig('payment/ccgateway/merchant'), $this->getKeysLocation());
        $resp = $cc->getProfileService($profileId);
        if (empty($resp)) {
			$myLogMessage = "CC Get Profile Service : ". __FILE__ . " @ " . __LINE__ ."  ".$cc->getLastErrorMessage();
			Mage::log($myLogMessage, Zend_Log::ERR , "cc.log" );
			$resp[] = array('resptext' => "CardConnect_Error");
        }

        return $resp;
    }

// Function for Delete Profile webservices

    function deleteWalletDataService($profileRowId) {

        $passWord = Mage::getStoreConfig('payment/ccgateway/password');
        $cc_password = Mage::getModel('core/encryption')->decrypt($passWord);
		if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customerData = Mage::getSingleton('customer/session')->getCustomer();
            $ccUserId = $customerData->getId();
		}

        $collection = Mage::getModel('cardconnect_ccgateway/cardconnect_wallet')->getCollection()
                ->addFieldToFilter('CC_ID', array('eq' => $profileRowId))
				->addFieldToFilter('CC_USER_ID', array('eq' => $ccUserId))
                ->addFieldToSelect("*");

        foreach ($collection as $data) {
            $ccProfileId = $data->getData('CC_PROFILEID');
            $tokenNum = $data->getData('CC_MASK');
        }


		if (!empty($tokenNum)) {
			$cc = new CardConnectWebService($this->getWebServicesUrl(), Mage::getStoreConfig('payment/ccgateway/username'), $cc_password, Mage::getStoreConfig('payment/ccgateway/merchant'), $this->getKeysLocation());
			$resp = $cc->deleteProfileService($ccProfileId);

			if (!empty($resp)) {
				$response = json_decode($resp, true);

				if (($response['resptext'] === "Profile Deleted")  || ($response['resptext'] === "Profile not found")) {
					$resource = Mage::getSingleton('core/resource');
					$readConnection = $resource->getConnection('core_read');
					$writeConnection = $resource->getConnection('core_write');

					$getTable = $resource->getTableName('cardconnect_wallet');
					// Query to delete cardconnect_wallet table 
					$delQry = "DELETE FROM {$getTable} WHERE CC_ID=" . $profileRowId." AND CC_USER_ID=". $ccUserId;
					$writeConnection->query($delQry);
					$msg = "Card has been deleted successfully.";
				} else {
					$msg = "We are unable to perform the requested action, please contact customer service.";
					$myLogMessage = "CC Delete Profile Service : ". __FILE__ . " @ " . __LINE__ ."  ".$cc->getLastErrorMessage();
					Mage::log($myLogMessage, Zend_Log::ERR , "cc.log" );
					$myMessage = "CC Delete Profile Service : ". __FILE__ . " @ " . __LINE__ ."  ".$response['resptext'];
					Mage::log($myMessage, Zend_Log::ERR , "cc.log" );
				}
			} else {
				$myLogMessage = "CC Delete Profile Service : ". __FILE__ . " @ " . __LINE__ ."  ".$cc->getLastErrorMessage();
				Mage::log($myLogMessage, Zend_Log::ERR , "cc.log" );
				$msg = "We are unable to perform the requested action, please contact customer service.";
			}
        }

        return $msg;
    }

// Update Profile webservices     
    function updateProfileService($paymentInformation) {
        $passWord = Mage::getStoreConfig('payment/ccgateway/password');
        $cc_password = Mage::getModel('core/encryption')->decrypt($passWord);
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customerData = Mage::getSingleton('customer/session')->getCustomer();
            $ccUserId = $customerData->getId();
            $ccCardName = $paymentInformation['cc_profile_name'];
            $cc_id = $paymentInformation['wallet_id'];
        }

        $ccExpiry = $paymentInformation['cc_exp_month'] . substr($paymentInformation['cc_exp_year'], 2);
        if (strlen($ccExpiry) < 4) {
            $ccExpiry = "0" . $ccExpiry;
        }


        $profrequest = array(
            'defaultacct' => $paymentInformation['defaultacct'],
            'profile' => $paymentInformation['profile'],
            'profileupdate' => $paymentInformation['profileupdate'],
            'account' => $paymentInformation['cc_number'],
            'accttype' => $paymentInformation['cc_type'],
            'expiry' => $ccExpiry,
            'name' => $paymentInformation['cc_owner'],
            'address' => $paymentInformation['cc_street'],
            'city' => $paymentInformation['cc_city'],
            'region' => $paymentInformation['cc_region'],
            'country' => $paymentInformation['cc_country'],
            'phone' => $paymentInformation['cc_telephone'],
            'postal' => $paymentInformation['cc_postcode']
        );
	

        $cc = new CardConnectWebService($this->getWebServicesUrl(), Mage::getStoreConfig('payment/ccgateway/username'), $cc_password, Mage::getStoreConfig('payment/ccgateway/merchant'), $this->getKeysLocation());

        $resp = $cc->createProfileService($profrequest);
        if ($resp != "") {
            $response = json_decode($resp, true);
            if ($response['resptext'] == "Profile Saved") {
				$fields = array('CC_CARD_NAME' => $ccCardName, 'CC_MASK' => $response['token']);
				$connectionWrite = Mage::getSingleton('core/resource')->getConnection('core_write');
				$connectionWrite->beginTransaction();
				$where = $connectionWrite->quoteInto('CC_ID =?', $cc_id);
				$connectionWrite->update('cardconnect_wallet', $fields, $where);
				$connectionWrite->commit();				
                $response = "Profile Updated";
            } else {
                $errorMessage = "There is some problem in updatae profile. Due to " . $response['resptext'];
                Mage::log($errorMessage, Zend_Log::ERR);
            }
        } else {
				$myLogMessage = "CC Update Profile Service : ". __FILE__ . " @ " . __LINE__ ."  ".$cc->getLastErrorMessage();
				Mage::log($myLogMessage, Zend_Log::ERR , "cc.log" );
        }


        return $response;
    }

// Check has wallet card
    function hasWalletCard($customerID) {

        $collection = Mage::getModel('cardconnect_ccgateway/cardconnect_wallet')->getCollection()
                ->addFieldToFilter('CC_USER_ID', array('eq' => $customerID))
                ->addFieldToSelect("CC_USER_ID");

        $ccProfileId = "";
        foreach ($collection as $data) {
            $ccProfileId = $data->getData('CC_USER_ID');
        }

        if ($ccProfileId != null) {
            $msg = "Yes";
        } else {
            $msg = "No";
        }

        return $msg;
    }

// function for get data from response table by order id

    public function getResponseDataByOrderId($orderId) {
        $collection = Mage::getModel('cardconnect_ccgateway/cardconnect_resp')->getCollection()
                ->addFieldToFilter('CC_ORDERID', array('eq' => $orderId))
                ->addFieldToFilter('CC_SETLSTAT', array(
            array('nin' => array('Accepted', 'Voided')),
            array('null' => true),
                )
        );

        return $collection;
    }

// Update response table after Inquire webservices    
    protected function updateAfterInquireService($fields, $ccgateway_id) {

        $connectionWrite = Mage::getSingleton('core/resource')->getConnection('core_write');
        $connectionWrite->beginTransaction();
        $where = $connectionWrite->quoteInto('CC_ID =?', $ccgateway_id);
        $connectionWrite->update('cardconnect_resp', $fields, $where);
        $connectionWrite->commit();

        return $this;
    }

// Save response date to ccgateway table    
    public function saveResponseData($response, $table = "") {


        if ($table == "Wallat") {
			$ccMask = substr_replace(@$response['token'], '************', 0, 12);
			
            $data = array('CC_USER_ID' => $response['ccUserId'], 		/* Checkout Transaction Type */
                'CC_PROFILEID' => $response['profileid'], 				/* Retrieval Reference Number */
                'CC_ACCTID' => $response['acctid'], 					/* Capture Amount */
                'CC_MASK' => $ccMask, 									/* Masked number */
                'CC_CARD_NAME' => $response['ccCardName'], 				/* Order Id */
                'CC_DEFAULT_CARD' => @$response['defaultacct'], 		/* Token */
                'CC_CREATED' => now() 									/* Request's response time */
            );
            $model = Mage::getModel('cardconnect_ccgateway/cardconnect_wallet')->setData($data);
        } else {
            $retref = $response['retref'];
			// $ccToken = @$response['token'];
			$ccToken ="";
			
            $data = array('CC_ACTION' => $response['action'], 			/* Checkout Transaction Type */
                'CC_RETREF' => "$retref", 								/* Retrieval Reference Number */
                'CC_AMT' => @$response['amount'], 						/* Capture Amount */
                'CC_AUTHCODE' => @$response['authcode'], 				/* Authorization code */
                'CC_ORDERID' => $response['orderid'],					/* Order Id */
                'CC_TOKEN' => $ccToken, 								/* Token */
                'CC_AVSRESP' => @$response['avsresp'], 					/* AVS Result */
                'CC_CVVRESP' => @$response['cvvresp'], 					/* CVV Result */
                'CC_RESPTEXT' => $response['resptext'], 				/* Response Description */
                'CC_MERCHID' => @$response['merchid'], 					/* Merchant Id */
                'CC_RESPPROC' => $response['respproc'], 				/* Response Processor */
                'CC_RESPCODE' => $response['respcode'], 				/* Response Code */
                'CC_RESPSTAT' => $response['respstat'], 				/* Response Status */
                'CC_SETLSTAT' => @$response['setlstat'], 				/* settlement Status */
                'CC_VOIDED' => $response['voidflag'], 					/* Void Flag */
                'CC_CREATED' => now() 									/* Request's response time */
            );
            $model = Mage::getModel('cardconnect_ccgateway/cardconnect_resp')->setData($data);
        }
        $model->save();
    }

// Function for get Authcode
    public function getAuthCode($currentOrderId) {

        $collection = Mage::getModel('cardconnect_ccgateway/cardconnect_resp')->getCollection()
                ->addFieldToFilter('CC_ORDERID', array('eq' => $currentOrderId))
                ->addFieldToSelect('CC_AUTHCODE');

        $authDesc = "";
        foreach ($collection as $data) {
            $authDesc = $data->getData('CC_AUTHCODE');
        }

        return $authDesc;
    }

// Function for get retref refrence number
    public function getRetrefReferenceNumber($currentOrderId, $action = "") {

        $collection = Mage::getModel('cardconnect_ccgateway/cardconnect_resp')->getCollection()
                ->addFieldToFilter('CC_ORDERID', array('eq' => $currentOrderId))
                ->addFieldToSelect('CC_RETREF');
        if ($action !== "Refund") {
            $collection->setOrder('CC_CREATED', 'DESC');
        }
        $collection->getSelect()->limit(1);

        $retrefRefrenceNumber = "";
        foreach ($collection as $data) {
            $retrefRefrenceNumber = $data->getData('CC_RETREF');
        }

        return $retrefRefrenceNumber;
    }

}
