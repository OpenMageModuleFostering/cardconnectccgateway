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
class Cardconnect_Ccgateway_Block_Form extends Mage_Payment_Block_Form
{

    protected function _construct()
    {
        if(Mage::getModel('ccgateway/standard')->getConfigData('active')==1){
            parent::_construct();
            $this->setTemplate('ccgateway/form.phtml');
        }
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

    /**
     * Retrieve Transaction Mode
     *
     * @return string
     */
    public function isTransactionModeTest()
    {
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
     * Retrieve field value data from payment info object
     *
     * @param   string $field
     * @return  mixed
     */
    public function getInfoData($field)
    {
        return $this->escapeHtml($this->getMethod()->getInfoInstance()->getData($field));
    }

    /**
     * Retrieve availables credit card types
     *
     * @return array
     */
    public function getCcTypes()
    {
        $cc_types = new Cardconnect_Ccgateway_Adminhtml_Model_System_Config_Source_Cardtype();
        $types = $cc_types->toOptionArray();

        $availableTypes = Mage::getModel('ccgateway/standard')->getConfigData('card_type');

        if ($availableTypes) {
            $availableTypes = explode(',', $availableTypes);
            $result = array();
            foreach ($types as $val) {
                if (($key = array_search($val['value'], $availableTypes, TRUE)) !== false) {
                    $result[] = $val;
                    unset($availableTypes[$key]);
                }
            }
        }

        return $result;
    }


    /**
     * Retrieve credit card expire months
     *
     * @return array
     */
    public function getCcMonths()
    {
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
    public function getCcYears()
    {
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

    public function getCCProfileName()
    {

        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customerData = Mage::getSingleton('customer/session')->getCustomer();
            $customerID = $customerData->getId();
        }

        $collection = Mage::getModel('cardconnect_ccgateway/cardconnect_wallet')->getCollection()
            ->addFieldToFilter('CC_USER_ID', array('eq' => $customerID))
            ->addFieldToSelect("CC_PROFILEID")
            ->addFieldToSelect("CC_CARD_NAME");

        return $collection;
    }

    public function getDefaultCCProfileId()
    {

        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customerData = Mage::getSingleton('customer/session')->getCustomer();
            $customerID = $customerData->getId();
        }

        $collection = Mage::getModel('cardconnect_ccgateway/cardconnect_wallet')->getCollection()
            ->addFieldToFilter('CC_USER_ID', array('eq' => $customerID))
            ->addFieldToFilter('CC_DEFAULT_CARD', array('eq' => 'Y'))
            ->addFieldToSelect("CC_PROFILEID");

        foreach ($collection as $data) {
            $ccProfileId = $data->getData('CC_PROFILEID');
        }

        if (!empty($ccProfileId)) {
            $resp = Mage::getModel('ccgateway/standard')->getProfileWebServiceCheckout($ccProfileId);

			if(@$resp['resptext'] == "CardConnect_Error"){
				$response = "CardConnect_Error";
			}else{
				$response = json_decode($resp, true);
				if(isset($response[0]['resptext'])){
					$response = "CardConnect_Error";
                }else{
                    $response = json_decode($resp, true);
				}
			}
        } else {
			$myLogMessage = "CC Get Default Profile Service : ". __FILE__ . " @ " . __LINE__ ."  "."Customer doesn't have  default profile in wallet.";
			Mage::log($myLogMessage, Zend_Log::ERR , "cc.log" );
        }

        return $response;
    }

    public function hasCCProfile()
    {
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customerData = Mage::getSingleton('customer/session')->getCustomer();
            $customerID = $customerData->getId();
			$ccProfileId = "";
            $collection = Mage::getModel('cardconnect_ccgateway/cardconnect_wallet')->getCollection()
                    ->addFieldToFilter('CC_USER_ID', array('eq' => $customerID))
                    ->addFieldToSelect("CC_PROFILEID");
            foreach ($collection as $data) {
                $ccProfileId = $data->getData('CC_PROFILEID');
            }

            if ($ccProfileId != null) {
                $msg = true;
            } else {
                $msg = false;
            }
        } else {
            $msg = false;
        }

        return $msg;
    }

}
