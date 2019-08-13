<?php

/**
 * @brief Defines the Credit Card Management function
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

require_once Mage::getModuleDir('controllers', 'Mage_Customer').DS.'AccountController.php';

class Cardconnect_Ccgateway_CardmanagementController extends Mage_Customer_AccountController {

    /**
     * Index action. List of cards.
     */
    public function indexAction() {
        $this->loadLayout();
        $this->_initMessages();
        $this->renderLayout();
    }

    /**
     * Init layout messages, add page title
     */
    protected function _initMessages() {
        $this->_initLayoutMessages('customer/session');
        $this->getLayout()->getBlock('head')->setTitle($this->__('Card Management'));
        $this->getLayout()->getBlock('ccgateway/walletcard_index');
    }

    /**
     * Add new card action
     */
    public function newAction() {

        $this->loadLayout();
        $this->_initMessages();
        $this->getLayout()->getBlock('customer_walletcard_management')->setTemplate('ccgateway/cardmanagement/new.phtml');

        $this->renderLayout();
    }

    /**
     * Save payment ajax action
     *
     * Sets either redirect or a JSON response
     */
    public function savePaymentAction() {

        try {
            $data = $this->getRequest()->getPost('addcard', array());

            // Call function Create Profile webservices 
            $response = Mage::getModel('ccgateway/standard')->createProfileService($data);
            if ($response['resptext'] == "Profile Saved") {
                Mage::getSingleton('core/session')->addSuccess("Card has been added successfully.");
            }else{
                Mage::getSingleton('core/session')->addError(Mage::helper('ccgateway')->__("Unable to perform add card. Please, retry."));
            }
            $this->_redirect('customer/cardmanagement');
        } catch (Exception $e) {
            Mage::logException($e);
            throw new Exception("Unable to perform add card. Please, retry.");
        }
    }

    /**
     * Function to make default payment from Card Management Grid
     */
    public function makedefaultpaymentAction() {
        $walletId = $this->getRequest()->getParam('id');

        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customerData = Mage::getSingleton('customer/session')->getCustomer();
            $ccUserId = $customerData->getId();
        }

        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');
        $getTable = $resource->getTableName('cardconnect_wallet');

        /* Query to first update the CC_DEFAULT_CARD in cardconnect_wallet table */
        $updQry = "UPDATE {$getTable} SET CC_DEFAULT_CARD='N' WHERE CC_USER_ID=" . $ccUserId . "  AND CC_DEFAULT_CARD='Y'";
        $writeConnection->query($updQry);

        /* Query to make selected card as Default payment card */
        $makeDafault = "UPDATE {$getTable} SET CC_DEFAULT_CARD='Y' WHERE CC_USER_ID=" . $ccUserId . " AND CC_ID=" . $walletId;
        $writeConnection->query($makeDafault);
        $success_msg = "Wallet Updated Successfully";
        echo $success_msg;
        exit;
    }

    /**
     * To delete wallet profile
     */
    public function deletewalletAction() {

        $walletid = $this->getRequest()->getParam('cc_id');
        $response = Mage::getModel('ccgateway/standard')->deleteWalletDataService($walletid);

        echo $response;
        exit;
    }

    /**
     * Edit card action
     */
    public function editcardAction() {

        $this->loadLayout();
        $this->_initMessages();
        $this->getLayout()->getBlock('customer_walletcard_management')->setTemplate('ccgateway/cardmanagement/editcard.phtml');
        $this->renderLayout();
    }

    /**
     * Save payment ajax action
     *
     * Sets either redirect or a JSON response
     */
    public function updateprofileAction() {

        $data = $this->getRequest()->getPost('editcard', array());

        $param = array(
            'cc_profile_name' => $data['cc_profile_name'],
            'wallet_id' => $data['wallet_id'],
            'defaultacct' => 'Y',
            'profile' => addslashes($data['profile'] . '/1'),
            'profileupdate' => 'Y',
            'cc_number' => $data['cc_number'],
            'cc_type' => $data['cc_type'],
            'cc_exp_month' => $data['cc_exp_month'],
            'cc_exp_year' => $data['cc_exp_year'],
            'cc_owner' => $data['cc_owner'],
            'cc_street' => $data['cc_street'],
            'cc_city' => $data['cc_city'],
            'cc_region' => $data['cc_region'],
            'cc_country' => $data['cc_country'],
            'cc_telephone' => $data['cc_telephone'],
            'cc_postcode' => $data['cc_postcode']);

        // Call function Create Profile webservices
        $response = Mage::getModel('ccgateway/standard')->updateProfileService($param);
	Mage::log($response, Zend_Log::ERR, "cc.log");
        if ($response == "Profile Updated") {
            Mage::getSingleton('core/session')->addSuccess("Card has been updated successfully.");
        } else {
            Mage::getSingleton('core/session')->addError(Mage::helper('ccgateway')->__("Unable to perform update. Please, retry."));
        }
        $this->_redirect('customer/cardmanagement');

    }


}
