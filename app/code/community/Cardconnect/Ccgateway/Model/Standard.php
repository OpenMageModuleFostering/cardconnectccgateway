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
    protected $_cclength = 0;


    protected function _construct() {
        parent::_construct();
    }

    /**
     * Return payment url type string
     *
     * @return string
     */
    public function getUrl() {

        $isTestMode = Mage::getModel('ccgateway/standard')->getConfigData('test_mode');
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
        $order = $this->getOrder();
        $tranType = $this->getConfigData('checkout_trans', $order->getStoreId());
        if ($tranType == "authorize_capture") {
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

    public function getErrorlogUrl() {
        return Mage::getUrl('ccgateway/log/logfrontenderrors');
    }

    /**
     * Get Payment transaction type
     */
    public function getPaymentTransactionType() {
        $checkout_trans = $this->getConfigData('checkout_trans');

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
        if ($this->getConfigData('currency')) {
            $price = number_format($this->getOrder()->getGrandTotal(), 2, '.', '');
        } else {
            $price = number_format($this->getOrder()->getBaseGrandTotal(), 2, '.', '');
        }

        $billing = $this->getOrder()->getBillingAddress();

        $ccArray = array(
            'ccId' => $this->getConfigData('card_id'), 						/* CardConnect Id */
            'ccSite' => $this->getConfigData('site_name'), 					/* Site Name */
            'ccDisplayAddress' => $this->getConfigData('address'), 			/* Display Address */
            'ccCapture' => $this->getConfigData('checkout_trans'), 			/* Checkout Transaction Type */
            'ccTokenize' => $this->getConfigData('tokenize'), 				/* Tokenize */
            'ccDisplayCvv' => $this->getConfigData('display'), 				/* Display CVV */
            'ccAmount' => $price, 																    /* Transaction Amount */
            'ccName' => Mage::helper('core')->removeAccents($billing->getFirstname()
                . ' ' . $billing->getLastname()), 											        /* Account Name */
            'ccAddress' => Mage::helper('core')->removeAccents($billing->getStreet(1)), 		    /* Account street address */
            'ccCity' => Mage::helper('core')->removeAccents($billing->getCity()), 				    /* Account city */
            'ccState' => $billing->getRegionCode(),												    /* US State, Mexican State, Canadian Province, etc. */
            'ccCountry' => $billing->getCountry(), 												    /* Account country */
            'ccZip' => $billing->getPostcode(), 												    /* Account postal code */
            'ccCardTypes' => $this->getConfigData('card_type'),
            'ccOrderId' => Mage::getSingleton('checkout/session')->getLastRealOrderId(), 		    /* Order Id */
            'ccCssUrl' => $this->getConfigData('css'), 						                        /* CSS URL */
            'ccPostbackUrl' => Mage::getUrl('ccgateway/payment/response'), 			                /* Postback URL */
            'ccAsync' => $this->getConfigData('validation'), 				                        /* Immediate Validation */
            'ccCancel' => $this->getConfigData('cancel'), 					                        /* Cancel Button enable flag */
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
	    $_cclength = strlen($info->getCcNumber());
            if (is_numeric($info->getCcNumber()) && $_cclength < 20) {
                $info->setCcNumberEnc($info->encrypt($info->getCcNumber()));
            } else  {
		$info->setCcCidEnc($info->encrypt($info->getCcCid()));
                return $this;
            }
        }

        $info->setCcCidEnc($info->encrypt($info->getCcCid()));

        return $this;
    }

    /** For Authorization * */
    public function authService($order, $authAmount = "", $status = "")
    {

        $orderId = $order->getIncrementId();
        $merchid = $this->getConfigData('merchant', $order->getStoreId());

        if (empty($status)) {
            $ccOwner = Mage::getSingleton('core/session')->getCcOwner();
            $ccNumber = Mage::getSingleton('core/session')->getCcNumber();
            $ccType = Mage::getSingleton('core/session')->getCcType();
            $ccExpiry = Mage::getSingleton('core/session')->getCcExpMonth() . substr(Mage::getSingleton('core/session')->getCcExpYear(), 2);
            $ccCvv2 = Mage::getSingleton('core/session')->getCcCid();
            $price = number_format($order->getBaseGrandTotal(), 2, '.', '');
            $profileId = Mage::getSingleton('core/session')->getCcProfileid();
        } else {
            // For Partial Shipment Reauthorization
            $quote_id = $order->getQuoteId();
            $collection = Mage::getModel('sales/quote_payment')->getCollection()
                ->addFieldToFilter('quote_id', array('eq' => $quote_id));

            foreach ($collection as $data) {
                $ccOwner = $data->getData("cc_owner");
                $ccType = $data->getData("cc_type");
                $ccNumber = Mage::helper('core')->decrypt($data->getData("cc_number_enc"));
                $ccExpiry = $data->getData("cc_exp_month") . substr($data->getData("cc_exp_year"), 2);
                $ccCvv2 = Mage::helper('core')->decrypt($data->getData("cc_cid_enc"));
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
            $captureStatus = "Y";
        } else {
            $captureStatus = "N";
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

        $cc = Mage::helper('ccgateway')->getCardConnectWebService($order);
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
                if ($response['respcode'] === "00") {
                    // Set custom order status
                    $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, 'cardconnect_processing', $response['resptext'])->save();

                    if ($checkout_trans == "authorize_capture") {
                        if ($order->canInvoice())
                            $this->_processOrderStatus($order);
                    }
                } else {
                    Mage::log("CC Authorization Error because response is : " . json_encode($response), Zend_Log::ERR, "cc.log");

                    $this->_cancelOrder($order);

                    $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, 'cardconnect_reject', $response['resptext'])->save();
                    $response = array('resptext' => "CardConnect_Error");
                }
            }
        } else {
            $timeout = strpos($cc->getLastErrorMessage(), "errno=28");
            if ($timeout !== false) {
                $myLogMessage = "CC Authorization Timeout : " . __FILE__ . " @ " . __LINE__ . "  " . $cc->getLastErrorMessage();
                Mage::log($myLogMessage, Zend_Log::ERR, "cc.log");

                // Note: State is updated and Order is canceled by PaymentController
                $response = array('resptext' => "CardConnect_Timeout_Error");
            } else {
                $myLogMessage = "CC Authorization Error : " . __FILE__ . " @ " . __LINE__ . "  " . $cc->getLastErrorMessage();
                Mage::log($myLogMessage, Zend_Log::ERR, "cc.log");

                $this->_cancelOrder($order);

                // Set custom order status
                $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, 'cardconnect_reject', "Invalid response from CardConnect.")->save();
                $response = array('resptext' => "CardConnect_Error");
            }
        }
        return $response;
    }

    private function _processOrderStatus($order)
    {
        $invoice = $order->prepareInvoice();
        $invoice->register()->capture();
        Mage::getModel('core/resource_transaction')
            ->addObject($invoice)
            ->addObject($invoice->getOrder())
            ->save();

        return true;
    }

    private function _cancelOrder($order)
    {
        // add order items back to inventory
        $order->cancel();

        // For each items in the orders, quantity invoiced should be set to zero
        foreach ($order->getAllItems() as $item) {
            $item->setData('qty_invoiced', 0);
            $item->cancel();
            $item->save();
        }
        $order->save();
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
            $merchid = $this->getConfigData('merchant' , $payment->getOrder()->getStoreId());

            $cc = Mage::helper('ccgateway')->getCardConnectWebService($order);

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
                    $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, 'cardconnect_capture', $response['setlstat'])->save();
                }
            } else {
                $timeout = strpos($cc->getLastErrorMessage(), "errno=28");
                if($timeout !== false){
                    $myLogMessage = "CC Capture Timeout : ". __FILE__ . " @ " . __LINE__ ."  ".$cc->getLastErrorMessage();
                    Mage::log($myLogMessage, Zend_Log::ERR , "cc.log" );

                    $order->setState($order->getState(), 'cardconnect_timeout', "Timeout error response on Capture from CardConnect.")->save();

                    $this->_resetInvoice($order);

                    $errorMsg = "Unable to perform operation at this time.  Please consult the Magento log for additional information.";
                    Mage::throwException($errorMsg);
                } else {
                    $myLogMessage = "CC Capture Error : ". __FILE__ . " @ " . __LINE__ ."  ".$cc->getLastErrorMessage();
                    Mage::log($myLogMessage, Zend_Log::ERR , "cc.log" );

                    $order->setState($order->getState(), 'cardconnect_reject', "Invalid response on Capture from CardConnect.")->save();

                    $this->_resetInvoice($order);

                    $errorMsg = "Unable to perform operation.  Please consult the Magento log for additional information.";
                    Mage::throwException($errorMsg);
                }
            }
        }

        return $this;
    }

    /** For resetting capture flags in case of error * */
    private function _resetInvoice($order) {
        // Reset invoice flag
        $order->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_CANCEL, false);
        $order->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_HOLD, false);
        $order->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_UNHOLD, false);
        $order->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_EDIT, false);
        $order->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_CREDITMEMO, false);
        $order->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_INVOICE, false);
        $order->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_REORDER, false);
        $order->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_SHIP, false);
        $order->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_COMMENT, false);
        $order->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_PRODUCTS_PERMISSION_DENIED, false);

        // For each items in the orders, quantity invoiced should be set to zero
        foreach ($order->getAllItems() as $item) {
            $item->setData('qty_invoiced', 0);
            $item->save();
        }
        $order->save();
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

        $cc = Mage::helper('ccgateway')->getCardConnectWebService($order);
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
                $order->setState($order->getState(), 'cardconnect_void', $response['resptext'])->save();
            }

            return $response;
        } else {
            $timeout = strpos($cc->getLastErrorMessage(), "errno=28");
            if($timeout !== false){
                $myLogMessage = "CC Void Timeout : ". __FILE__ . " @ " . __LINE__ ."  ".$cc->getLastErrorMessage();
                Mage::log($myLogMessage, Zend_Log::ERR , "cc.log" );

                $order->setState($order->getState(), 'cardconnect_timeout', "Timeout error response on Void from CardConnect.")->save();

                $errorMsg = "Unable to perform operation at this time.  Please consult the Magento log for additional information.";
                Mage::throwException($errorMsg);
            } else {
                $myLogMessage = "CC Void Error : ". __FILE__ . " @ " . __LINE__ ."  ".$cc->getLastErrorMessage();
                Mage::log($myLogMessage, Zend_Log::ERR , "cc.log" );

                $order->setState($order->getState(), 'cardconnect_reject', "Invalid response on Void from CardConnect.")->save();

                $errorMsg = "Unable to perform operation.  Please consult the Magento log for additional information.";
                Mage::throwException($errorMsg);
            }
        }
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

// Check payment settlement status before refund

    public function processBeforeRefund($invoice, $payment) {
        $order = $payment->getOrder();
        $orderId = $order->increment_id;
        $retref = $this->getRetrefReferenceNumber($orderId, "Refund");

        $cc = Mage::helper('ccgateway')->getCardConnectWebService($order);

        $resp = $cc->inquireService($retref);
        $response = json_decode($resp, true);

        if ($response['setlstat'] == "Accepted") {
            $status = "true";
            Mage::log('Txn settled for your order Id: ' . $orderId);
        } else {
            $status = "false";
            Mage::log("Refund cannot be processed, transaction should be settled first.");
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
        $merchid = $this->getConfigData('merchant' , $payment->getOrder()->getStoreId());

        if ($amount <= 0) {
            Mage::throwException(Mage::helper('ccgateway')->__('Invalid amount for refund.'));
        }

        if (strpos('.', $amount) == "") {
            $amount = number_format($amount, 2);
        }

        $amount = str_replace(",", "", $amount);

        $cc = Mage::helper('ccgateway')->getCardConnectWebService($order);
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
            if ($response['respcode'] == "00") {
                $order->setState($order->getState(), 'cardconnect_refund', $response['resptext'])->save();
            } else {
                $order->setState(Mage_Sales_Model_Order::STATE_COMPLETE, 'cardconnect_reject', $response['resptext'])->save();
            }
        } else {
            $timeout = strpos($cc->getLastErrorMessage(), "errno=28");
            if ($timeout !== false) {
                $myLogMessage = "CC Refund Timeout : " . __FILE__ . " @ " . __LINE__ . "  " . $cc->getLastErrorMessage();
                Mage::log($myLogMessage, Zend_Log::ERR, "cc.log");

                $order->setState(Mage_Sales_Model_Order::STATE_COMPLETE, 'cardconnect_timeout', "Timeout error response on Refund from CardConnect.")->save();
                $errorMsg = "Unable to perform operation at this time.  Please consult the Magento log for additional information.";
                Mage::throwException($errorMsg);
            } else {
                $myLogMessage = "CC Refund Error : " . __FILE__ . " @ " . __LINE__ . "  " . $cc->getLastErrorMessage();
                Mage::log($myLogMessage, Zend_Log::ERR, "cc.log");

                // Set custom order status
                $order->setState(Mage_Sales_Model_Order::STATE_COMPLETE, 'cardconnect_reject', "Invalid response on Refund from CardConnect.")->save();
                $errorMsg = "Unable to perform operation.  Please consult the Magento log for additional information.";
                Mage::throwException($errorMsg);
            }

        }

        return $this;
    }

// Inquire function using web services
    public function inquireService($orderId) {

        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($orderId);
        $responseData = $this->getResponseDataByOrderId($orderId);
        $cc_password = $this->getConfigData('password', $order->getStoreId());


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

                    $cc = Mage::helper('ccgateway')->getCardConnectWebService($order);
                    $resp = $cc->inquireService($retref);
                    $response = json_decode($resp, true);
                    if (!empty($response)) {
                        if (abs($response['amount']) == $order_amount || abs($response['amount']) == '0.00') {
                            if ($response['setlstat'] == 'Accepted' || $response['setlstat'] == 'Voided') {
                                if ($ccAction == 'Refund') {

                                    $order->setState($order->getState(), 'cardconnect_refund', $response['setlstat'])->save();
                                }
                                if ($ccAction == 'authorize' || $ccAction == 'authorize_capture') {
                                    if ($response['setlstat'] == 'Voided') {
                                        $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, 'cardconnect_void', $response['setlstat'])->save();
                                    } else if ($response['setlstat'] == 'Accepted') {
                                        $order->setState($order->getState(), 'cardconnect_txn_settled', $response['setlstat'])->save();
                                    }
                                }
                                if ($ccAction == 'Void') {
                                    $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, 'cardconnect_void', $response['setlstat'])->save();
                                }
                            } else if ($response['setlstat'] == 'Rejected') {
                                // Do not update order status for refund
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
                        $timeout = strpos($cc->getLastErrorMessage(), "errno=28");
                        if ($timeout !== false) {
                            $errorMsg = 2;

                            $myLogMessage = "CC Inquire Timeout : " . __FILE__ . " @ " . __LINE__ . "  " . $cc->getLastErrorMessage();
                            Mage::log($myLogMessage, Zend_Log::ERR, "cc.log");

                            $order->setState($order->getState(), 'cardconnect_timeout', "Timeout error response on Inquire from CardConnect.")->save();
                        }
                        else {
                            $errorMsg = 1;

                            $myLogMessage = "CC Inquire Error : " . __FILE__ . " @ " . __LINE__ . "  " . $cc->getLastErrorMessage();
                            Mage::log($myLogMessage, Zend_Log::ERR, "cc.log");

                            // Note: We do not log inquire errors to transaction history
                        }
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
            $message = "Error while attempting to determine latest transaction status (Inquire)";
        } else if ($errorMsg == 2) {
            // For timeout in inquire
            $message = "Unable to perform operation at this time.  Please consult the Magento log for additional information.";
        } else {
            $message = "Successfully Inquired";
        }

        return $message;
    }

// Create Profile webservices     
    function createProfileService($paymentInformation) {

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

        $cc = Mage::helper('ccgateway')->getCardConnectWebService();
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

                // Save Response data
                $this->saveResponseData($response, "Wallat");
            }
        } else {
            $timeout = strpos($cc->getLastErrorMessage(), "errno=28");
            if ($timeout !== false) {
                $myLogMessage = "CC Create Profile Service Timeout : " . __FILE__ . " @ " . __LINE__ . "  " . $cc->getLastErrorMessage();
                Mage::log($myLogMessage, Zend_Log::ERR, "cc.log");

                $response = array('resptext' => "CardConnect_Timeout_Error");
            } else {
                $myLogMessage = "CC Create Profile Service Error : " . __FILE__ . " @ " . __LINE__ . "  " . $cc->getLastErrorMessage();
                Mage::log($myLogMessage, Zend_Log::ERR, "cc.log");

                $response = array('resptext' => "CardConnect_Error");
            }
        }

        return $response;
    }

// Function for Get Profile webservices
    function getProfileWebService($profileId, $cc_id) {

        $cc = Mage::helper('ccgateway')->getCardConnectWebService();

        $resp = $cc->getProfileService($profileId);
        if (!empty($resp) && $cc_id != "") {
            $resource = Mage::getSingleton('core/resource');
            $readConnection = $resource->getConnection('core_read');
            $getTable = $resource->getTableName('cardconnect_wallet');

            $selQry = "SELECT CC_CARD_NAME FROM {$getTable} WHERE CC_ID=" . $cc_id;
            $rsCard = $readConnection->fetchRow($selQry);
            $resp = json_decode($resp, true);
            $resp[] = $rsCard['CC_CARD_NAME'];
        } else {
            $timeout = strpos($cc->getLastErrorMessage(), "errno=28");
            if ($timeout !== false) {
                $myLogMessage = "CC Get Profile Service Timeout : " . __FILE__ . " @ " . __LINE__ . "  " . $cc->getLastErrorMessage();
            }
            else {
                $myLogMessage = "CC Get Profile Service Error : " . __FILE__ . " @ " . __LINE__ . "  " . $cc->getLastErrorMessage();
            }
            Mage::log($myLogMessage, Zend_Log::ERR, "cc.log");

            $resp[] = array('resptext' => "CardConnect_Error");
        }

        return $resp;
    }

// Function for Get Profile webservices Checkout
    function getProfileWebServiceCheckout($profileId) {

        $cc = Mage::helper('ccgateway')->getCardConnectWebService();
        $resp = $cc->getProfileService($profileId);
        if (empty($resp)) {
            $timeout = strpos($cc->getLastErrorMessage(), "errno=28");
            if ($timeout !== false) {
                $myLogMessage = "CC Get Profile Service Timeout : " . __FILE__ . " @ " . __LINE__ . "  " . $cc->getLastErrorMessage();
            }
            else {
                $myLogMessage = "CC Get Profile Service Error : " . __FILE__ . " @ " . __LINE__ . "  " . $cc->getLastErrorMessage();
            }
            Mage::log($myLogMessage, Zend_Log::ERR, "cc.log");

            $resp[] = array('resptext' => "CardConnect_Error");
        }

        return $resp;
    }

// Function for Delete Profile webservices

    function deleteWalletDataService($profileRowId, $ccUserId= "") {

        if(empty($ccUserId)){
            if (Mage::getSingleton('customer/session')->isLoggedIn()) {
                $customerData = Mage::getSingleton('customer/session')->getCustomer();
                $ccUserId = $customerData->getId();
            }
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
            $cc = Mage::helper('ccgateway')->getCardConnectWebService();
            $resp = $cc->deleteProfileService($ccProfileId);

            if (!empty($resp)) {
                $response = json_decode($resp, true);

                if (($response['resptext'] === "Profile Deleted")  || ($response['resptext'] === "Profile not found")) {
                    $resource = Mage::getSingleton('core/resource');
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
                $timeout = strpos($cc->getLastErrorMessage(), "errno=28");
                if ($timeout !== false) {
                    $myLogMessage = "CC Delete Profile Service Timeout : " . __FILE__ . " @ " . __LINE__ . "  " . $cc->getLastErrorMessage();
                }
                else {
                    $myLogMessage = "CC Delete Profile Service Error : " . __FILE__ . " @ " . __LINE__ . "  " . $cc->getLastErrorMessage();
                }
                Mage::log($myLogMessage, Zend_Log::ERR, "cc.log");

                $msg = "We are unable to perform the requested action, please contact customer service.";
            }
        } else {
            $myLogMessage = "CC Delete Profile Service : " . __FILE__ . " @ " . __LINE__;
            Mage::log($myLogMessage, Zend_Log::ERR, "cc.log");
            $msg = "We are unable to perform the requested action, please contact customer service.";
        }

        return $msg;
    }

// Update Profile webservices     
    function updateProfileService($paymentInformation) {
        $username = $this->getConfigData('username');
        $merchid = $this->getConfigData('merchant');
        $cc_password = $this->getConfigData('password');

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


        $cc = Mage::helper('ccgateway')->getCardConnectWebService();
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
                $errorMessage = "There is some problem in update profile. Due to " . $response['resptext'];
                Mage::log($errorMessage, Zend_Log::ERR, "cc.log");
            }
        } else {
            $timeout = strpos($cc->getLastErrorMessage(), "errno=28");
            if ($timeout !== false) {
                $myLogMessage = "CC Update Profile Service Timeout : " . __FILE__ . " @ " . __LINE__ . "  " . $cc->getLastErrorMessage();
            }
            else {
                $myLogMessage = "CC Update Profile Service Error : " . __FILE__ . " @ " . __LINE__ . "  " . $cc->getLastErrorMessage();
            }
            Mage::log($myLogMessage, Zend_Log::ERR, "cc.log");
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
	    if (!empty($response['token'])){
                $ccToken = @$response['token'];
            } else {
		$ccToken ="";
            }


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

    /**
     * Retrieve information from payment configuration
     *
     * @param string $field
     * @param int|string|null|Mage_Core_Model_Store $storeId
     *
     * @return mixed
     */
    public function getConfigData($field, $storeId = null)
    {

        $path = 'payment/'.$this->getCode().'/'.$field;

        if(empty($storeId)){
            if(Mage::app()->getStore()->getCode() == Mage_Core_Model_Store::ADMIN_CODE) {
                $storeId = Mage::getSingleton('adminhtml/session_quote')->getStoreId();
                return Mage::getStoreConfig($path, $storeId);
            }else {
                return Mage:: getStoreConfig($path);
            }
        }else{
            return Mage::getStoreConfig($path, $storeId);
        }
    }


}
