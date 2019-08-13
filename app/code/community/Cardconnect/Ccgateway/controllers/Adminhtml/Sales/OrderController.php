<?php

/**
 * @brief Defines the void on cancel function
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


require_once 'Mage/Adminhtml/controllers/Sales/OrderController.php';

class Cardconnect_Ccgateway_Adminhtml_Sales_OrderController extends Mage_Adminhtml_Sales_OrderController {

    /**
     * Array of actions which can be processed without secret key validation
     *
     * @var array
     */
    protected $_publicActions = array('view', 'index');

    /**
     * Additional initialization
     *
     */
    protected function _construct() {
        $this->setUsedModuleName('Mage_Sales');
    }


    /**
     * Cancel order
     */
    public function cancelAction()
    {
        if ($order = $this->_initOrder()) {
            try {

                // Check CardConnect Void status
                $voidStatus = Mage::getModel('ccgateway/standard')->getVoidStatus($order);
                if($voidStatus==true){
                    // Checking if payment method is CardConnect then void service will call.
                    $payment_method_code = $order->getPayment()->getMethodInstance()->getCode();
                    if ($payment_method_code == "ccgateway") {
                        $voidResponse = Mage::getModel('ccgateway/standard')->voidService($order);
                        if($voidResponse['respcode'] == '00' ){
                            $order->cancel()->save();
                            $this->_getSession()->addSuccess(
                                $this->__('The order has been cancelled.')
                            );
                        }else{
                            $myLogMessage = "CC Void : ". __FILE__ . " @ " . __LINE__ ."  ".$voidResponse['respcode']." => ".$voidResponse['resptext'];
                            Mage::log($myLogMessage, Zend_Log::ERR , "cc.log" );
                        }
                    }else{
                        $order->cancel()->save();
                        $this->_getSession()->addSuccess(
                            $this->__('The order has been cancelled.')
                        );
                    }
                }else{
                    $this->_getSession()->addError($this->__('Order is already invoiced. Cancel is not allowed on invoiced order.'));
                    Mage::log("Unable to perform full void. Order is already Captured, full void is not allowed on after capture.");
                }
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            } catch (Exception $e) {
                $this->_getSession()->addError($this->__('The order has not been cancelled.'));
                Mage::logException($e);
            }
            $this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
        }
    }


}
