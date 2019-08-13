<?php

/**
 * @brief Defines the Reorder function
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
include_once("Mage/Adminhtml/controllers/Sales/Order/CreateController.php");

class Cardconnect_Ccgateway_Adminhtml_Sales_Order_CreateController extends Mage_Adminhtml_Sales_Order_CreateController {

    /**
     * Additional initialization
     *
     */
    protected function _construct() {
        $this->setUsedModuleName('Mage_Sales');

        // During order creation in the backend admin has ability to add any products to order
        Mage::helper('catalog/product')->setSkipSaleableCheck(true);
    }

    public function reorderAction() {

        $this->_getSession()->clear();
        $orderId = $this->getRequest()->getParam('order_id');
        $order = Mage::getModel('sales/order')->load($orderId);
        if (!Mage::helper('sales/reorder')->canReorder($order)) {
            return $this->_forward('noRoute');
        }

        if ($order->getId()) {
            $order->setReordered(true);
            $this->_getSession()->setUseOldShippingMethod(true);
            $this->_getOrderCreateModel()->initFromOrder($order);

            $this->_redirect('*/*');
        } else {
            $this->_redirect('*/sales_order/');
        }
    }

    /**
     * Saving quote and create order
     */
    public function saveAction() {
        try {
            $this->_processActionData('save');
            $paymentData = $this->getRequest()->getPost('payment');

            if ($paymentData) {
                $paymentData['checks'] = Mage_Payment_Model_Method_Abstract::CHECK_USE_INTERNAL | Mage_Payment_Model_Method_Abstract::CHECK_USE_FOR_COUNTRY | Mage_Payment_Model_Method_Abstract::CHECK_USE_FOR_CURRENCY | Mage_Payment_Model_Method_Abstract::CHECK_ORDER_TOTAL_MIN_MAX | Mage_Payment_Model_Method_Abstract::CHECK_ZERO_TOTAL;
                $this->_getOrderCreateModel()->setPaymentData($paymentData);
                $this->_getOrderCreateModel()->getQuote()->getPayment()->addData($paymentData);
            }


            $order = $this->_getOrderCreateModel()
                    ->setIsValidate(true)
                    ->importPostData($this->getRequest()->getPost('order'))
                    ->createOrder();

            /* Check Payment Method for Authorization on Reorder     */
            $payment_method_code = $order->getPayment()->getMethodInstance()->getCode();
            $this->_getSession()->clear();

            // make payment if payment method is ccgateway
            if (!empty($order) && $payment_method_code == "ccgateway") {
                $this->makeCcPayment($order);
            }


            if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/view')) {
                $this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
            } else {
                $this->_redirect('*/sales_order/index');
            }
        } catch (Mage_Payment_Model_Info_Exception $e) {
            $this->_getOrderCreateModel()->saveQuote();
            $message = $e->getMessage();
            if (!empty($message)) {
                $this->_getSession()->addError($message);
            }
            $this->_redirect('*/*/');
        } catch (Mage_Core_Exception $e) {
            $message = $e->getMessage();
            if (!empty($message)) {
                $this->_getSession()->addError($message);
            }
            $this->_redirect('*/*/');
        } catch (Exception $e) {
            $this->_getSession()->addException($e, $this->__('Order saving error: %s', $e->getMessage()));
            $this->_redirect('*/*/');
        }
    }


    /**
     * Call authorization service and check AVS, CVV fails check
     */

    protected function makeCcPayment($order){
        $amount = number_format($order->getBaseGrandTotal(), 2, '.', '');
        $response = Mage::getModel('ccgateway/standard')->authService($order, $amount, "authFull");
        $errorStat = $response['respproc'] . $response['respcode'];

        if($response['resptext']=="CardConnect_Error"){
            // Cancel the order if authorization service fails
            Mage::helper('ccgateway')->cancelCcOrder($order);
            $errorMsg = Mage::helper('ccgateway')->matchResponseError($errorStat);
            // Set custom order status
            $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, 'cardconnect_reject', $errorMsg)->save();
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('ccgateway')->__('We are unable to perform the requested action, please contact customer service.'));
        }else{
            $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, 'cardconnect_processing', 'Gateway has authorized the payment.');
            $autoVoidStatus = Mage::helper('ccgateway')->checkAutoVoidStatus($order, $response['avsresp'], $response['cvvresp']);
            if (($autoVoidStatus["STATUS_AVS"] && $autoVoidStatus["STATUS_CVV"]) == false) {
                $voidResponse = Mage::getModel('ccgateway/standard')->voidService($order);
                if($voidResponse['respcode'] == '00' ){
                    // Cancel the order if Auto void success
                    Mage::helper('ccgateway')->cancelCcOrder($order);

                    $errorMsg = "";
                    if ($autoVoidStatus["STATUS_ERROR"] == "CVV") {
                        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('ccgateway')->__('Error - Invalid cvv , Please check cvv information,which is entered on payment page.'));
                        $errorMsg = "CVV does not match.";
                        $myLogMessage = "CC Admin Authorization : ". __FILE__ . " @ " . __LINE__ ."  Auto voided the transaction Due to CVV NO MATCH";
                        Mage::log($myLogMessage, Zend_Log::ERR , "cc.log" );
                    } elseif ($autoVoidStatus["STATUS_ERROR"] == "AVS") {
                        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('ccgateway')->__('Error - Please check billing information , try again.'));
                        $errorMsg = "AVS does not match.";
                        $myLogMessage = "CC Admin Authorization : ". __FILE__ . " @ " . __LINE__ ."  Auto voided the transaction Due to AVS NO MATCH";
                        Mage::log($myLogMessage, Zend_Log::ERR , "cc.log" );
                    } else {
                        $errorMsg = Mage::helper('ccgateway')->matchResponseError($errorStat);
                        $myLogMessage = "CC Admin Authorization : ". __FILE__ . " @ " . __LINE__ ."  ".$errorStat . " :- " . $errorMsg;
                        Mage::log($myLogMessage, Zend_Log::ERR , "cc.log" );
                    }
                }
                $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, 'cardconnect_reject', $errorMsg)->save();

            }else{
                Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The order has been created.'));
                $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, 'cardconnect_processing', "Successfully order placed via CardConnect.")->save();
            }

        }

    }



}
