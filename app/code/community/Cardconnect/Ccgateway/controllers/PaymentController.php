<?php

/**
 * @brief Defines the redirect and response function
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
class Cardconnect_Ccgateway_PaymentController extends Mage_Core_Controller_Front_Action {

    protected $_order = NULL;
    protected $_paymentInst = NULL;

    /**
     * Get singleton of Checkout Session Model
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckout() {
        return Mage::getSingleton('checkout/session');
    }

    protected function _getPendingPaymentStatus() {
        return Mage::helper('ccgateway')->getPendingPaymentStatus();
    }

    // The redirect action is triggered when someone places an order
    public function redirectAction() {
        try {
            $session = $this->_getCheckout();

            $order = Mage::getModel('sales/order');
            $order->loadByIncrementId($session->getLastRealOrderId());
            if (!$order->getId()) {
                Mage::throwException('No order found for processing');
            }
            if ($order->getState() != Mage_Sales_Model_Order::STATE_PENDING_PAYMENT) {
                $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, $this->_getPendingPaymentStatus(), Mage::helper('ccgateway')->__('Customer was redirected to ccgateway.'))->save();
            }

            if ($session->getQuoteId() && $session->getLastSuccessQuoteId()) {
                $session->setccgatewayQuoteId($session->getQuoteId());
            }

            $checkoutType = Mage::getModel('ccgateway/standard')->getConfigData('checkout_type' , $order->getStoreId());

            if ($checkoutType == "tokenized_post") {
                $response = Mage::getModel('ccgateway/standard')->authService($order);

                if($response['resptext']=="CardConnect_Error"){
                    $errorMsg = "We are unable to perform the requested action, please contact customer service.";
                    $session = $this->_getCheckout();
                    $session->addError(Mage::helper('ccgateway')->__($errorMsg));
                    $this->_redirect('checkout/cart');
                }else{
                    $this->responseAction($response);
                }

            } else {
                $this->loadLayout();
                $this->renderLayout();
            }
            $session->unsQuoteId();
            return;
        } catch (Mage_Core_Exception $e) {
            $this->_getCheckout()->addError($e->getMessage());
        }
    }

    // The response action is triggered when your gateway sends back a response after processing the customer's payment

    public function responseAction($response = "") {

        $session = $this->_getCheckout();
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($session->getLastRealOrderId());
        $cc_action = Mage::getModel('ccgateway/standard')->getConfigData('checkout_trans' , $order->getStoreId());
        $merchid = Mage::getModel('ccgateway/standard')->getConfigData('merchant' , $order->getStoreId());

        $ccToken = "";

        if ($this->getRequest()->isPost()) {
            $post = $this->getRequest()->getPost();

            $orderId = $post['orderId'];
            $errorCode = $post['errorCode'];
            $errorDesc = $post['errorDesc'];
            $avsResp = $post['avsResp'];
            $cvvResp = $post['cvvResp'];
            $retref = $post['retref'];


            /* Handling Hosted Payment Page API Response */
            $data = array('CC_ACTION' => $cc_action, 			/* Checkout Transaction Type */
                'CC_RETREF' => "$retref", 						/* Retrieval Reference Number */
                'CC_AMT' => $post['amount'], 					/* Capture Amount */
                'CC_AUTHCODE' => $post['authCode'], 			/* Authorization code */
                'CC_ORDERID' => $post['orderId'], 				/* Order Id */
                'CC_TOKEN' => $ccToken, 						/* Token  @$post['token']; */
                'CC_MERCHID' => $merchid, 						/* Merchant Id */
                'CC_RESPCODE' => $post['errorCode'], 			/* Error Code */
                'CC_RESPTEXT' => $post['errorDesc'], 			/* Error Description */
                'CC_AVSRESP' => $post['avsResp'], 				/* AVS Result */
                'CC_CVVRESP' => $post['cvvResp'], 				/* CVV Result */
                'CC_CREATED' => now() 							/* Request's response time */
            );
        } else {

            $orderId = $response['orderid'];
            $errorCode = $response['respcode'];
            $errorDesc = $response['resptext'];
            $avsResp = @$response['avsresp'];
            $cvvResp = @$response['cvvresp'];
            $retref = $response['retref'];



            /* Handling Tokenize Post API Response */
            $data = array('CC_ACTION' => $cc_action, 			/* Checkout Transaction Type */
                'CC_RETREF' => "$retref", 						/* Retrieval Reference Number */
                'CC_AMT' => $response['amount'], 				/* Capture Amount */
                'CC_AUTHCODE' => @$response['authcode'], 		/* Authorization code */
                'CC_ORDERID' => $response['orderid'], 			/* Order Id */
                'CC_TOKEN' => $ccToken,							/* Token  @$response['token']; */
                'CC_MERCHID' => $merchid, 						/* Merchant Id */
                'CC_RESPPROC' => $response['respproc'], 		/* Response Processor */
                'CC_RESPCODE' => $response['respcode'], 		/* Error Code */
                'CC_RESPTEXT' => $response['resptext'], 		/* Error Description */
                'CC_RESPSTAT' => $response['respstat'], 		/* Response Status */
                'CC_AVSRESP' => $avsResp, 						/* AVS Result */
                'CC_CVVRESP' => $cvvResp, 						/* CVV Result */
                'CC_CREATED' => now() 							/* Request's response time */
            );
        }

        if ($errorCode == 00) {
            $statCVV = true;            // false = Failure, true = Success
            $statAVS = true;            // false = Failure, true = Success
            $voidOnAvs = Mage::getModel('ccgateway/standard')->getConfigData('void_avs' , $order->getStoreId());
            $voidOnCvv = Mage::getModel('ccgateway/standard')->getConfigData('void_cvv' , $order->getStoreId());
            // Check config setting if void on cvv is yes
            if ($voidOnCvv == 1) {
                if ($cvvResp == "N") {
                    $statCVV = false;
                    $errorStat = "CVV";
                } else {
                    $statCVV = true;
                }
            }
            // Check config setting if void on Avs is yes
            if ($voidOnAvs == 1) {
                if ($avsResp == "N") {
                    $statAVS = false;
                    $errorStat = "AVS";
                } else {
                    $statAVS = true;
                }
            }


            if (($statAVS && $statCVV) == false) {
                $this->saveResponseData2ccgateway($data);
                $voidResponse = Mage::getModel('ccgateway/standard')->voidService($order);
                if($voidResponse['respcode'] == '00' ){
                    $this->responseCancel($errorStat);
                    // Set custom order status
                    $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, 'cardconnect_reject', $errorDesc)->save();
                }
            } else {
                $this->saveResponseData2ccgateway($data);
                $this->responseSuccess($orderId);
                // Set custom order status
                $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, 'cardconnect_processing', $errorDesc)->save();
            }
        } elseif ($errorCode == 02) {
            $errorStat = $response['respproc'] . $response['respcode'];
            $this->responseCancel($errorStat);
            $this->saveResponseData2ccgateway($data);
            // Set custom order status
            $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, 'cardconnect_reject', $errorDesc)->save();
        } else {
            $errorStat = $response['respproc'] . $response['respcode'];
            $this->responseCancel(@$errorStat);
            $this->saveResponseData2ccgateway($data);
            // Set custom order status
            $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, 'cardconnect_reject', $errorDesc)->save();
        }
    }

    // Save date to CardConnect responce table    
    protected function saveResponseData2ccgateway($data) {

        $collection = Mage::getModel('cardconnect_ccgateway/cardconnect_resp')->getCollection()
            ->addFieldToFilter('CC_ORDERID', array('eq' => $data['CC_ORDERID']))
            ->addFieldToSelect('CC_ACTION');

        if ($collection->count() == 0) {
            $model = Mage::getModel('cardconnect_ccgateway/cardconnect_resp')->setData($data);
            $model->save();
        }

        return $this;
    }

// Payment was successful, so update the order's state, send order email and move to the success page
    protected function responseSuccess($orderId) {
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($orderId);
        $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, 'Gateway has authorized the payment.');

        $order->sendNewOrderEmail();
        $order->setEmailSent(true);

        try {

            $session = Mage::getSingleton('checkout/session');
            $session->setQuoteId($session->getCcgatewayQuoteId());
            $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, 'cardconnect_processing', "Successfully order placed via CardConnect.");
            $order->save();
            Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();
            $this->_redirect('checkout/onepage/success', array('_secure'=>true));
            return;
        } catch (Mage_Core_Exception $e) {
            $this->_getCheckout()->addError($e->getMessage());
            $this->_redirect('checkout/cart');             // Redirect on cart page if there is error.
        } catch (Exception $e) {
            $this->_debug('Ccgateway error: ' . $e->getMessage());
            Mage::logException($e);
        }

    }


// Function for cancel the order     
    protected function responseCancel($errorStat) {
        $session = $this->_getCheckout();
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($session->getLastRealOrderId());

        Mage::helper('ccgateway')->cancelCcOrder($order);
        if ($quoteId = $session->getCcgatewayQuoteId()) {
            $quote = Mage::getModel('sales/quote')->load($quoteId);
            if ($quote->getId()) {
                $quote->setIsActive(true)->save();
                $session->setQuoteId($quoteId);
            }
        }

        $errorMsg = "";
        if ($errorStat == "CVV") {
            $session->addError(Mage::helper('ccgateway')->__('Error - Invalid cvv , Please check cvv information,which is entered on payment page.'));
            $errorMsg = "CVV does not match.";
            Mage::log("Auto voided the transaction Due to CVV NO MATCH");
        } elseif ($errorStat == "AVS") {
            $session->addError(Mage::helper('ccgateway')->__('Error - Please check billing information , try again.'));
            $errorMsg = "AVS does not match.";
            Mage::log("Auto voided the transaction Due to AVS NO MATCH");
        } else {
            $errorMsg = Mage::helper('ccgateway')->matchResponseError($errorStat);
            Mage:: log($errorStat . " :- " . $errorMsg);
            $session->addError(Mage::helper('ccgateway')->__($errorMsg));
        }

        // Set custom order status
        $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, 'cardconnect_reject', $errorMsg)->save();

        $this->_redirect('checkout/cart');
    }

// Function for call Inquire services 
    public function inquireAction() {

        $orderid = $this->getRequest()->getParam('orderId');
        $model = Mage::getModel('ccgateway/standard');
        $data = $model->inquireService($orderid);
        echo $data;

        return $data;
    }

// Function for call Get Profile Webservices Checkout Page
    public function getProfileDataAction() {
        $profileId = $this->getRequest()->getParam('profile');

        $response = Mage::getModel('ccgateway/standard')->getProfileWebServiceCheckout($profileId);

        $json = json_encode($response);

        $this->getResponse()
            ->clearHeaders()
            ->setHeader('Content-Type', 'application/json')
            ->setBody($json);
    }

// Function for call Get Profile Webservices Edit Card Data
    public function getProfileDataEditAction() {
        $profileId = $this->getRequest()->getParam('profile');
        $cc_id = $this->getRequest()->getParam('cc_id');
        $response = Mage::getModel('ccgateway/standard')->getProfileWebService($profileId, $cc_id);

        $json = json_encode($response);

        $this->getResponse()
            ->clearHeaders()
            ->setHeader('Content-Type', 'application/json')
            ->setBody($json);
    }

    /**
     * To delete wallet profile
     */
    public function deletewalletAction() {

        $walletid = $this->getRequest()->getParam('cc_id');
        $customerId = $this->getRequest()->getParam('customerId');

        $response = Mage::getModel('ccgateway/standard')->deleteWalletDataService($walletid, $customerId);

        echo $response;
        exit;
    }

}
