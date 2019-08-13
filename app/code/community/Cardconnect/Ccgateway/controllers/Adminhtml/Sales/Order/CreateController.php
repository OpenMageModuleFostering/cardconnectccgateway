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

            if (!empty($order) && $payment_method_code == "ccgateway") {
                $amount = number_format($order->getBaseGrandTotal(), 2, '.', '');
                Mage::getModel('ccgateway/standard')->authService($order, $amount, "authFull");
            }


            $this->_getSession()->clear();
            Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The order has been created.'));
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

}
