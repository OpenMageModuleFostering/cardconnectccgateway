<?php
/**
 * @brief Defines the class representing CardConnect Payments
 * @category CardConnect Payment Module
 * @author CardConnect
 * @copyright Portions copyright 2014 CardConnect
 * @license GPL v2, please see LICENSE.txt
 * @access public
 * @version $Id: $
 *
 **/
/*
This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA
*/

/**
 * CardConnect REST API Wrapper Class
 *
 * @package CardConnect
 * @category Library
 * @author CardConnect
 *
 */
class CardConnectWebService
{
    var $userName;
    var $passWord;
    var $restUrl;
    var $merchantId;
    var $lasterror;
    var $testMode;
    var $ccSite;
    var $frontEndID;
    var $testTemplateUrl;
    var $prodTemplateUrl;

    function CardConnectWebService($testmode, $ccsite, $frontendid, $username, $password, $merchantId, $keys_location)
    {
        $this->userName = $username;
        $this->passWord = $password;
        $this->merchantId = $merchantId;
        $this->keys_location = $keys_location;
        $this->testMode = $testmode;
        $this->ccSite = $ccsite;
        $this->frontEndID = $frontendid;
        $this->testTemplateUrl = 'https://[SITE].prinpay.com:6443/cardconnect/rest/';
        $this->prodTemplateUrl = 'https://[SITE].prinpay.com:8443/cardconnect/rest/';

        if (!empty($this->ccSite)) {
            if ($testmode === 'No' || $testmode == 0) {
                // replace sitename in $prodTemplateUrl
                $this->restUrl = str_ireplace("[SITE]", $this->ccSite, $this->prodTemplateUrl);
                error_log("Modified Production URL " . $this->restUrl);
            } else {
                $this->restUrl = str_ireplace("[SITE]", $this->ccSite, $this->testTemplateUrl);
                error_log("Modified Test URL " . $this->restUrl);
            }
        } else {
            $mymessage = "CC: ccSite name is empty ";
            error_log("CC Error : " . $mymessage . " Last Error Message : " . $this->getLastErrorMessage());
            $this->restUrl = "";
        }

    }

    /**
     * Function: getLastErrorMessage
     * Description: Return the last curl error encountered while executing web services
     */
    public function getLastErrorMessage()
    {
        return $this->lasterror;
    }

    /**
     * Function: sendTransactionToGateway
     * Description: Send web service request to payment gateway
     * Parameters:
     * @param $service "accept service name (auth, capture, refund, void, inquire)"
     * @param $parameters "input of web service request data"
     * @return $result returns JSON encoded string to the appropriate Service
     */
    public function sendTransactionToGateway($service, $parameters)
    {
        $url = $this->restUrl;
        $postString = "";

        if ($service == 'inquire') {
            $method = 'GET';
            $url = $url . $service . "/" . $parameters;
        } elseif ($service == 'CP') {
            $method = 'GET';
            $url = $url . "?" . $parameters;
        } elseif ($service == 'getprofile') {
            $method = 'GET';
            $url = $url . "profile" . "/" . $parameters;
        } elseif ($service == 'deleteprofile') {
            $method = 'DELETE';
            $url = $url . "profile" . "/" . $parameters;
        } else {
            $method = 'PUT';
            $url = $url . $service;
            $postString = json_encode($parameters);
        }

        $headers = array("Content-Type: application/json", "Accept: application/json");

        if (function_exists('curl_init')) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_USERPWD, $this->userName . ":" . $this->passWord);
            curl_setopt($curl, CURLOPT_CAINFO, $this->keys_location . "cacert.pem");
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_COOKIESESSION, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postString);

            $result = curl_exec($curl);
            $info = curl_getinfo($curl);
            $curlerrno = curl_errno($curl);
            $curlerrdesc = curl_error($curl);

            curl_close($curl);

            if ($info['http_code'] != "200") {
                $this->lasterror = "ERROR ";
                if (!empty($info['http_code']))
                    $this->lasterror = $this->lasterror . ":http=" . $info['http_code'];
                if ($curlerrno != 0)
                    $this->lasterror = $this->lasterror . ":errno=" . $curlerrno . ":errdesc=" . $curlerrdesc;
                if (!empty($result))
                    $this->lasterror = $this->lasterror . ":result=" . $result;
                $result = "";
            }
            return $result;
        }
    }

    /**
     * Function: authService
     * Description: Implementation of auth web service request
     * Parameters:
     * @param $authrequest "Accept auth request array"
     * @return $response "Returns JSON encoded string of auth service response"
     */
    public function authService($authrequest)
    {

        if (!empty($authrequest['profileid'])) {
            $param = array(
                'merchid'       => $this->merchantId,
                'profile'       => $authrequest['profileid'],
                'orderid'       => $authrequest['order_id'],
                'amount'        => $authrequest['currency_value'],
                'ecomind'       => $authrequest['ecomind'],
                'cvv2'          => $authrequest['cvv_val'],
                'capture'       => $authrequest['capture'],
                //'ccSite'        => $this->ccSite,
                'frontendid'    => $this->frontEndID);

            $response = $this->sendTransactionToGateway('auth', $param);
        } else {
            if (!empty($authrequest['acc_num'])) {
                $param = array(
                    'merchid'       => $this->merchantId,
                    'accttype'      => $authrequest['acc_type'],
                    'orderid'       => $authrequest['order_id'],
                    'account'       => $authrequest['acc_num'],
                    'expiry'        => $authrequest['expirydt'],
                    'amount'        => $authrequest['currency_value'],
                    'currency'      => $authrequest['currency'],
                    'name'          => $authrequest['cc_owner'],
                    'address'       => $authrequest['billing_street_address'],
                    'city'          => $authrequest['billing_city'],
                    'region'        => $authrequest['billing_state'],
                    'country'       => $authrequest['billing_country'],
                    'postal'        => $authrequest['billing_postcode'],
                    'ecomind'       => $authrequest['ecomind'],
                    'cvv2'          => $authrequest['cvv_val'],
                    'track'         => null,
                    'tokenize'      => 'Y',
                    'capture'       => $authrequest['capture'],
                    //'ccSite'        => $this->ccSite,
                    'frontendid'    => $this->frontEndID);

                $response = $this->sendTransactionToGateway('auth', $param);

            } else {
                $mymessage = "CC: Account number is empty ";
                error_log("CC Error : " . $mymessage . " Last Error Message : " . $this->getLastErrorMessage());
                $response = '';
            }
        }

        return $response;

    }

    /**
     * Function: captureService
     * Description: Implementation of capture web service request
     * Parameters:
     * @param $cc_retref "Retrieval reference number"
     * @param $cc_authcode "Authorization number"
     * @param $currency_value "Amount"
     * @param $order_id "Order Id"
     * @return $response "Returns JSON encoded string of capture service response"
     */
    public function captureService($cc_retref, $cc_authcode = null, $currency_value = null, $order_id = null)
    {
        $param = array(
            'retref'        => $cc_retref,
            'merchid'       => $this->merchantId,
            'authcode'      => $cc_authcode,
            'amount'        => $currency_value,
            'invoiceid'     => $order_id,
            //'ccSite'        => $this->ccSite,
            'frontendid'    => $this->frontEndID);

        $response = $this->sendTransactionToGateway('capture', $param);
        return $response;
    }

    /**
     * Function: voidService
     * Description: Implementation of void web service request
     * Parameters:
     * @param $cc_retref "Retrieval reference number"
     * @param $currency_value "Amount"
     * @return $response "Returns JSON encoded string of void service response"
     */
    public function voidService($cc_retref, $currency_value = null)
    {
        $param = array(
            'retref'        => $cc_retref,
            'merchid'       => $this->merchantId,
            'amount'        => $currency_value,
            //'ccSite'        => $this->ccSite,
            'frontendid'    => $this->frontEndID
        );

        $response = $this->sendTransactionToGateway('void', $param);
        return $response;
    }

    /**
     * Function: refundService
     * Description: Implementation of refund web service request
     * Parameters:
     * @param $cc_retref "Retrieval reference number"
     * @param $currency_value "Amount"
     * @return $response "Returns JSON encoded string of refund service response"
     */
    public function refundService($cc_retref, $currency_value = null)
    {
        $param = array(
            'retref'        => $cc_retref,
            'merchid'       => $this->merchantId,
            'amount'        => $currency_value,
            //'ccSite'        => $this->ccSite,
            'frontendid'    => $this->frontEndID
        );

        $response = $this->sendTransactionToGateway('refund', $param);
        return $response;
    }

    /**
     * Function: inquireService
     * Description: Implementation of inquire web service request
     * Parameters:
     * @param $cc_retref "Retrieval reference number"
     * @return $response "Returns JSON encoded string of inquire service response"
     */
    public function inquireService($cc_retref)
    {
        $param = array(
            'retref'        => $cc_retref,
            'merchid'       => $this->merchantId
            /*'ccSite'        => $this->ccSite,
            'frontendid'    => $this->frontEndID*/
        );

        $postString = '';

        foreach ($param as $key => $value) {
            $postString .= urlencode(utf8_encode(trim($value))) . '/';
        }

        $postString = substr($postString, 0, -1);
        $response = $this->sendTransactionToGateway('inquire', $postString);
        return $response;
    }

    /**
     * Function: cardPurgeService
     * Description: Implementation of Purge web service request to delete token
     * Parameters:
     * @param $tokenNum "token number, $action, $type"
     * @param $action "CardSecure action"
     * @param $type "Type of request/response format"
     * @return $response "Returns XML encoded string of purge service response"
     */
    public function cardPurgeService($action, $type, $tokenNum)
    {
        $postString = "action=" . $action . "&type=" . $type . "&data=" . $tokenNum;

        $response = $this->sendTransactionToGateway('CP', $postString);
        return $response;
    }

    /**
     * Function: createProfileService
     * Description: Implementation of profile web service request
     * Parameters:
     * @param $profrequest "Accept profile request array"
     * @return $response "Returns JSON encoded string of create/update profile service response"
     */
    public function createProfileService($profrequest)
    {
        $param = array(
            'defaultacct'           => $profrequest['defaultacct'],
            'profile'               => $profrequest['profile'],
            'profileupdate'         => $profrequest['profileupdate'],
            'account'               => $profrequest['account'],
            'accttype'              => $profrequest['accttype'],
            'expiry'                => $profrequest['expiry'],
            'name'                  => $profrequest['name'],
            'address'               => $profrequest['address'],
            'city'                  => $profrequest['city'],
            'region'                => $profrequest['region'],
            'country'               => $profrequest['country'],
            'phone'                 => $profrequest['phone'],
            'postal'                => $profrequest['postal'],
            'merchid'               => $this->merchantId
            /*'ccSite'                => $this->ccSite,
            'frontendid'            => $this->frontEndID*/
        );

        $response = $this->sendTransactionToGateway('profile', $param);
        return $response;
    }

    /**
     * Function: getProfileService
     * Description: Implementation of profile web service request
     * Parameters:
     * @param $profileid "accept profileid to display account details"
     * @return $response "returns JSON encoded string of get profile service response"
     */
    public function getProfileService($profileid, $acctid = "")
    {
        $param = array(
            'profileid'         => $profileid,
            'acctid'            => $acctid,
            'merchid'           => $this->merchantId
            /*'ccSite'            => $this->ccSite,
            'frontendid'        => $this->frontEndID*/
        );

        $postString = '';
        foreach ($param as $key => $value) {
            $postString .= urlencode(utf8_encode(trim($value))) . '/';
        }

        $postString = substr($postString, 0, -1);
        $response = $this->sendTransactionToGateway('getprofile', $postString);
        return $response;

    }

    /**
     * Function: deleteProfileService
     * Description: Implementation of profile web service request
     * Parameters:
     * @param Accept profileid
     * @return Returns JSON encoded string of delete profile service response
     */
    public function deleteProfileService($profileid)
    {
        $param = array(
            'profileid'         => $profileid,
            'merchid'           => $this->merchantId
            /*'ccSite'            => $this->ccSite,
            'frontendid'        => $this->frontEndID*/
        );

        $postString = '';

        foreach ($param as $key => $value) {
            $postString .= urlencode(utf8_encode(trim($value))) . '//';
        }

        $postString = substr($postString, 0, -2);
        $response = $this->sendTransactionToGateway('deleteprofile', $postString);
        return $response;

    }


    /**
     * Function: getCCErrorMessage
     * Description: Function to return proper CardConnect Error messages
     * Parameters:
     * @param $respError "response processor and response code"
     * @return $message "returns Error message response"
     */
    public function getCCErrorMessage($respError)
    {
        $errorList = array(
            "PPS11"             => "Invalid card",
            "PPS12"             => "Invalid track",
            "PPS13"             => "Bad card check digit",
            "PPS14"             => "Non-numeric CVV",
            "PPS15"             => "Non-numeric expiry",
            "PPS16"             => "Card expired",
            "PPS17"             => "Invalid zip",
            "PPS19"             => "CardDefense Decline",
            "PPS23"             => "No auth queue",
            "PPS31"             => "Invalid currency",
            "PPS32"             => "Wrong currency for merch",
            "PPS33"             => "Unknown card type",
            "PPS35"             => "No postal code",
            "PPS37"             => "CVV mismatch",
            "PPS41"             => "Below min amount",
            "PPS42"             => "Above max amount",
            "PPS43"             => "Invalid amount",
            "PPS61"             => "Line down",
            "PPS62"             => "Timed out",
            "PPS91"             => "No TokenSecure",
            "PPS92"             => "No Merchant table",
            "PPS93"             => "No Database",
            "FNOR05"            => "Do not honor",
            "FNOR12"            => "Invalid transaction",
            "FNOR13"            => "Invalid amount",
            "FNOR14"            => "Invalid card number",
            "FNOR28"            => "Please retry",
            "FNOR51"            => "Declined",
            "FNOR54"            => "Wrong expiration",
            "FNOR61"            => "Exceeds withdrawal limit",
            "FNOR63"            => "Service not allowed",
            "FNOR89"            => "Invalid Term ID",
            "FNORC2"            => "CVV decline",
            "FNORN3"            => "Invalid Account",
            "FNORNU"            => "Insufficient funds",
            "MNS04"             => "Pick up card",
            "MNS05"             => "Do not honor",
            "MNS07"             => "Suspected fraud",
            "MNS13"             => "Invalid amount",
            "MNS14"             => "Invalid card number",
            "MNS15"             => "No such card issuer",
            "MNS19"             => "Re-enter transaction",
            "MNS34"             => "Suspected fraud",
            "MNS41"             => "Card reported lost",
            "MNS43"             => "Card reported stolen",
            "MNS51"             => "Insufficient funds",
            "MNS54"             => "Wrong expiration",
            "MNS65"             => "Activity limit exceeded",
            "MNS82"             => "CVV incorrect",
            "MNS99"             => "Decline",
            "PMT000"            => "System Down",
            "PMT200"            => "Auth network down",
            "PMT201"            => "Invalid CC number",
            "PMT202"            => "Bad amount",
            "PMT203"            => "Zero amount",
            "PMT233"            => "Card does not match type",
            "PMT238"            => "Invalid currency",
            "PMT239"            => "Invalid card for merchant",
            "PMT243"            => "Invalid Level 3 field",
            "PMT302"            => "Insufficient funds",
            "PMT303"            => "Processor decline",
            "PMT304"            => "Invalid card",
            "PMT501"            => "Pickup card",
            "PMT502"            => "Card reported lost",
            "PMT503"            => "Fraud",
            "PMT521"            => "Insufficient funds",
            "PMT522"            => "Card expired",
            "PMT530"            => "Do not honor",
            "PMT531"            => "CVV mismatch",
            "PMT591"            => "Invalid card number",
            "PMT592"            => "Bad amount",
            "PMT605"            => "Invalid expiry date",
            "PMT607"            => "Invalid amount",
            "PMT903"            => "Invalid expiry",
            "PMT904"            => "Card not active",
            "VPS04"             => "Pick up card",
            "VPS05"             => "Do not honor",
            "VPS07"             => "Suspected fraud",
            "VPS13"             => "Invalid amount",
            "VPS14"             => "Invalid card number",
            "VPS19"             => "Re-enter transaction",
            "VPS23"             => "Bad fee amount",
            "VPS28"             => "File temporarily unavailable",
            "VPS34"             => "Suspected fraud",
            "VPS41"             => "Card reported lost",
            "VPS43"             => "Card reported stolen",
            "VPS51"             => "Insufficient funds",
            "VPS54"             => "Wrong expiration",
            "VPS61"             => "Exceeds withdrawal limit",
            "VPS65"             => "Activity limit exceeded",
            "VPS82"             => "CVV incorrect",
            "VPS96"             => "System malfunction",
            "VPSN7"             => "CVV mismatch",
            "AMEX100"           => "Decline",
            "AMEX101"           => "Expired card",
            "AMEX103"           => "CID failed",
            "AMEX105"           => "Card cancelled",
            "AMEX110"           => "Invalid amount",
            "AMEX111"           => "Invalid card",
            "AMEX122"           => "Invalid CID",
            "AMEX182"           => "Try later",
            "AMEX200"           => "Pick up card",
            "PSTR02"            => "Declined",
            "PSTR06"            => "AVS_Declined",
            "PSTR07"            => "CCVS_Declined",
            "PSTR08"            => "Expired"

        );

        if (array_key_exists($respError, $errorList)) {
            $message = $errorList[$respError];
        } else {
            $message = "Your payment is unable to process, call customer service";
        }

        return $message;
    }


}

?>
