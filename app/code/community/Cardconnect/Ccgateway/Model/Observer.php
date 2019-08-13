<?php

/**
 * @brief Defines the observer method for savePaymentAction to implement Create Profile Web Service
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
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category Cardconnect
 * @package Cardconnect_Ccgateway
 * @copyright Copyright (c) 2014 CardConnect (http://www.cardconnect.com)
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Cardconnect_Ccgateway_Model_Observer
{


    public function savePaymentAction()
    {

        $data = Mage::app()->getFrontController()->getRequest()->getParams();

        // Call function Create Profile webservices 
        // if customer wish to save card for future use
        if (isset($data['payment']['cc_wallet']) && $data['payment']['cc_wallet'] == "checked") {
            Mage::getModel('ccgateway/standard')->createProfileService($data['payment']);
        }

        return;
    }


}


