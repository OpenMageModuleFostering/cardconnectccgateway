<?php

/**
 * @brief Defines the class for Payment information Block Frontend (Tokenize Post)
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
class Cardconnect_Ccgateway_Block_Cardmanagement extends Mage_Core_Block_Template {

    const TYPE_NEW_CART = 'cart';
    const TYPE_NEW_CUSTOMER = 'customer';
    const TYPE_EDIT = 'edit';

    /**
     * Internal constructor. Set template, model
     */
    function __construct() {
        parent::__construct();
        $this->setTemplate('ccgateway/cardmanagement/index.phtml');
 
    }

    /**
     * Returns url for add
     * 
     * @return string
     */
    public function getAddUrl() {
        return $this->getUrl('customer/cardmanagement/new');
    }

    /**
     * Returns url for edit
     *
     * @return string
     */
    public function getEditUrl() {
        return $this->getUrl('customer/cardmanagement/editcard');
    }

	
	    /**
     * Retrieve Transaction Mode
     *
     * @return string
     */
    public function isTransactionModeTest() {
        $isTestMode = Mage::getModel('ccgateway/standard')->getConfigData('test_mode');
        switch ($isTestMode) {
            case 0:
                $isTestMode = 'no';
                break;
            default:
				$isTestMode = 'yes';
                break;
        }

        return $isTestMode;
    }	
	
	
	
	
	
    /**
     * Returns Profile Data for Card Managment
     * 
     * @return string
     */
    public function getCCProfileName() {

        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customerData = Mage::getSingleton('customer/session')->getCustomer();
            $customerID = $customerData->getId();
        }

        $collection = Mage::getModel('cardconnect_ccgateway/cardconnect_wallet')->getCollection()
                ->addFieldToFilter('CC_USER_ID', array('eq' => $customerID))
                ->addFieldToSelect("*")
                ->setOrder('CC_CREATED', 'DESC');

        return $collection;
    }

    /**
     * Retrieve availables credit card types
     *
     * @return array
     */
    public function getCcTypes() {

		$cc_types = new Cardconnect_Ccgateway_Adminhtml_Model_System_Config_Source_Cardtype();
		$types = $cc_types->toOptionArray();

        $availableTypes = Mage::getModel('ccgateway/standard')->getConfigData('card_type');

        if ($availableTypes) {
            $availableTypes = explode(',', $availableTypes);
			$result = array(); 
			foreach ($types as $val) { 
			  if (($key = array_search($val['value'], $availableTypes, TRUE))!==false) { 
				 $result[] = $val; 
				 unset($availableTypes[$key]); 
			  }
			}
        }

        return  $result; 
    }

    /**
     * Retrieve credit card expire months
     *
     * @return array
     */
    public function getCcMonths() {
        $data = Mage::app()->getLocale()->getTranslationList('month');
        foreach ($data as $key => $value) {
            $monthNum = ($key < 10) ? '0' . $key : $key;
            $data[$key] = $monthNum . ' - ' . $value;
        }

        $months = $this->getData('cc_months');
        if (is_null($months)) {
            $months[0] = $this->__('Month');
            $months = array_merge($months, $data);
            $this->setData('cc_months', $months);
        }


        return $months;
    }

    /**
     * Retrieve credit card expire years
     *
     * @return array
     */
    public function getCcYears() {
        $cc_years = array();
        $first = date("Y");

        for ($index = 0; $index <= 10; $index++) {
            $year = $first + $index;
            $cc_years[$year] = $year;
        }
        $years = $this->getData('cc_years');
        if (is_null($years)) {
            $years = array(0 => $this->__('Year')) + $cc_years;
            $this->setData('cc_years', $years);
        }
        return $years;
    }

    /**
     * Retrieve Customer default billing address
     *
     * @return array
     */
    public function getCustomerBillingAddress() {

        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customerData = Mage::getSingleton('customer/session')->getCustomer();
            $customerID = $customerData->getId();
        }

        $visitorData = Mage::getModel('customer/customer')->load($customerID);
        $billingaddress = Mage::getModel('customer/address')->load($visitorData->default_billing);
        $addressdata = $billingaddress->getData();

        return $addressdata;
    }

}
