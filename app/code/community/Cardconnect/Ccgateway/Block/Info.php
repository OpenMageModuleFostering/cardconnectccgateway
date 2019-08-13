<?php
/**
 * @brief Defines the class representing CardConnect Payment information
 * @category Magento CardConnect Payment Module
 * @author CardConnect
 * @copyright Portions copyright 2014 CardConnect
 * @copyright Portions copyright Magento 2014
 * @license GPL v2, please see LICENSE.txt
 * @access public
 * @version $Id: $
 *
 **/
 
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

class Cardconnect_Ccgateway_Block_Info extends Mage_Payment_Block_Info {

    protected function _construct() {
        parent::_construct();
        $this->setTemplate('ccgateway/info.phtml');
    }

    
    public function getMethodCode()
    {
        return $this->getInfo()->getMethodInstance()->getCode();
    }

    
    public function getCurrentOrderId() {
        $info = $this->getInfo();
        if ($info instanceof Mage_Sales_Model_Order_Payment) {
            $order = $info->getOrder();
            $currentOrderId = $order->getData('increment_id');
        }

        return $currentOrderId;
    }
    
    public function getPaymentResponseData() {
        $currentOrderId = $this->getCurrentOrderId();

        $collection = Mage::getModel('cardconnect_ccgateway/cardconnect_resp')->getCollection()
                        ->addFieldToFilter('CC_ORDERID', array('eq' => $currentOrderId))
                        ->addFieldToSelect("*");

        return $collection;
    }

    
    /**
     * Retrieve payment info model
     *
     * @return Mage_Payment_Model_Info
     */
    public function getPaymentInfo()
    {
        $info = Mage::getSingleton('checkout/session')->getQuote()->getPayment();
        if ($info->getMethod()) {
            return $info;
        }
        return false;
    }

     /**
     * Retrieve Transaction Type
     *
     * @return string
     */    
    public function getCheckoutType(){
        $checkoutType = Mage::getModel('ccgateway/standard')->getConfigData('checkout_type');

        return $checkoutType;
    }
    

}
