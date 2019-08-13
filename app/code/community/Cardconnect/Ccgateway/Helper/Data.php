<?php
/**
 * @brief Defines the support class Helper Method for CardConnect Payments Module
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

class Cardconnect_Ccgateway_Helper_Data extends Mage_Payment_Helper_Data {

    public function getPendingPaymentStatus() {
        if (version_compare(Mage::getVersion(), '1.4.0', '<')) {
            return Mage_Sales_Model_Order::STATE_HOLDED;
        }
        return Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
    }
    
    
    public function matchResponseError($respErrorCode){

        $errorList = array("PPS11" => "Invalid card",
                    "PPS12" => "Invalid track",
                    "PPS13" =>  "Bad card check digit",
                    "PPS14" =>  "Non-numeric CVV",
                    "PPS15" =>  "Non-numeric expiry",
                    "PPS16" =>  "Card expired",
                    "PPS17" =>  "Invalid zip",
                    "PPS19" =>  "CardDefense Decline",
                    "PPS23" =>  "No auth queue",
                    "PPS31" =>  "Invalid currency",
                    "PPS32" =>  "Wrong currency for merch",
                    "PPS33" =>  "Unknown card type",
                    "PPS35" =>  "No postal code",
                    "PPS37" =>  "CVV mismatch",
                    "PPS41" =>  "Below min amount",
                    "PPS42" =>  "Above max amount",
                    "PPS43" =>  "Invalid amount",
                    "PPS61" =>  "Line down",
                    "PPS62" =>  "Timed out",
                    "PPS91" =>  "No TokenSecure",
                    "PPS92" =>  "No Merchant table",
                    "PPS93" =>  "No Database",
                    "FNOR05" =>  "Do not honor",
                    "FNOR12" =>  "Invalid transaction",
                    "FNOR13" =>  "Invalid amount",
                    "FNOR14" =>  "Invalid card number",
                    "FNOR28" =>  "Please retry",
                    "FNOR51" =>  "Declined",
                    "FNOR54" =>  "Wrong expiration",
                    "FNOR61" =>  "Exceeds withdrawal limit",
                    "FNOR63" =>  "Service not allowed",
                    "FNOR89" =>  "Invalid Term ID",
                    "FNORC2" =>  "CVV decline",
                    "FNORN3" =>  "Invalid Account",
                    "FNORNU" =>  "Insufficient funds",
                    "MNS04" =>  "Pick up card",
                    "MNS05" =>  "Do not honor",
                    "MNS07" => "Suspected fraud",
                    "MNS13" => "Invalid amount",
                    "MNS14" => "Invalid card number",
                    "MNS15" => "No such card issuer",
                    "MNS19" => "Re-enter transaction",
                    "MNS34" => "Suspected fraud", 
                    "MNS41" => "Card reported lost",
                    "MNS43" => "Card reported stolen",
                    "MNS51" => "Insufficient funds",
                    "MNS54" => "Wrong expiration",
                    "MNS65" => "Activity limit exceeded",
                    "MNS82" => "CVV incorrect",
                    "MNS99" => "Decline",
                    "PMT000" => "System Down",
                    "PMT200" => "Auth network down",
                    "PMT201" => "Invalid CC number",
                    "PMT202" => "Bad amount",
                    "PMT203" => "Zero amount",
                    "PMT233" => "Card does not match type",
                    "PMT238" => "Invalid currency",
                    "PMT239" => "Invalid card for merchant",
                    "PMT243" => "Invalid Level 3 field",
                    "PMT302" => "Insufficient funds",
                    "PMT303" => "Processor decline",
                    "PMT304" => "Invalid card",
                    "PMT501" => "Pickup card",
                    "PMT502" => "Card reported lost",
                    "PMT503" => "Fraud",
                    "PMT521" => "Insufficient funds",
                    "PMT522" => "Card expired",
                    "PMT530" => "Do not honor",
                    "PMT531" => "CVV mismatch",
                    "PMT591" => "Invalid card number",
                    "PMT592" => "Bad amount",
                    "PMT605" => "Invalid expiry date",
                    "PMT607" => "Invalid amount",
                    "PMT903" => "Invalid expiry",
                    "PMT904" => "Card not active",
                    "VPS04" => "Pick up card",
                    "VPS05" => "Do not honor",
                    "VPS07" => "Suspected fraud",
                    "VPS13" => "Invalid amount",
                    "VPS14" => "Invalid card number",
                    "VPS19" => "Re-enter transaction",
                    "VPS23" => "Bad fee amount", 
                    "VPS28" => "File temporarily unavailable",
                    "VPS34" => "Suspected fraud",
                    "VPS41" => "Card reported lost",
                    "VPS43" => "Card reported stolen",
                    "VPS51" => "Insufficient funds",
                    "VPS54" => "Wrong expiration",
                    "VPS61" => "Exceeds withdrawal limit",
                    "VPS65" => "Activity limit exceeded",
                    "VPS82" => "CVV incorrect",
                    "VPS96" => "System malfunction",
                    "VPSN7" => "CVV mismatch",
                    "AMEX100" => "Decline",
                    "AMEX101" => "Expired card",
                    "AMEX103" => "CID failed",
                    "AMEX105" => "Card cancelled",
                    "AMEX110" => "Invalid amount",
                    "AMEX111" => "Invalid card",
                    "AMEX122" => "Invalid CID",
                    "AMEX182" => "Try later",
                    "AMEX200" => "Pick up card",
                    "PSTR02" => "Declined",
                    "PSTR06" => "AVS_Declined",
                    "PSTR07" => "CCVS_Declined",
                    "PSTR08" => "Expired"
            
            );

        if (array_key_exists($respErrorCode, $errorList)) {
             $message = $errorList[ $respErrorCode ];
        }else{
            $message = "The order has been canceled.";
        }

            
        return $message;
    }
    
}

?>