<?php
/**
 * @brief Defines the class representing get checkout data for CardConnect payment page
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

class Cardconnect_Ccgateway_Block_Redirect extends Mage_Core_Block_Template {

    /**
     * Return checkout session instance
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckout() {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Return order instance
     *
     * @return Mage_Sales_Model_Order|null
     */
    protected function _getOrder() {
        if ($this->getOrder()) {
            return $this->getOrder();
        } elseif ($orderIncrementId = $this->_getCheckout()->getLastRealOrderId()) {
            return Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
        } else {
            return null;
        }
    }

    /**
     * Get form data
     *
     * @return array
     */
    public function getFormData() {
        return $this->_getOrder()->getPayment()->getMethodInstance()->getFormFields();
    }

    /**
     * Getting gateway url
     *
     * @return string
     */
    public function getFormAction() {
        return $this->_getOrder()->getPayment()->getMethodInstance()->getUrl();
    }

}
