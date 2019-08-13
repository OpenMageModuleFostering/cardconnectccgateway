/**
 * @brief Defines the JS representing CardConnect Tokenization
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


function tokenize(cardNum , isTestMode) {

    document.getElementById("ccgateway_cc_number_org").disabled = true;

    // construct url
	if(isTestMode == "yes"){
	//	var url = "https://fts.cardconnect.com:6443/cardsecure/cs";
		var url = "https://fts.prinpay.com:6443/cardsecure/cs";
	}else{
		var url = "https://fts.prinpay.com:8443/cardsecure/cs";
	}

    var method = "GET";
    var type = "json";
    var params = "action=CE";
    params = params + "&type=" + type;
    params = params + "&data=" + cardNum;

    // send request
    if (window.XMLHttpRequest) {
        xhr = new XMLHttpRequest();
        if (xhr.withCredentials !== undefined) {
            xhr.onreadystatechange = processXMLHttpResponse;
        } else {
            xhr = new XDomainRequest();
            xhr.onload = processXDomainResponse;
        }
    } else {
        if (window.ActiveXObject) {
            try {
                xhr = new ActiveXObject("Microsoft.XMLHTTP");
                xhr.onreadystatechange = processXMLHttpResponse;
            }
            catch (e) {
            }
        }
    }
    if (xhr) {
        if (method == "GET") {
            xhr.open("GET", url + "?" + params, true);
            xhr.send(null);
        } else {
            xhr.open("POST", url + "?action=CE", true);
            xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhr.send(params);
        }

    }
    else {
        document.getElementById("response").innerHTML = "Sorry, this browser does not support AJAX requests.";
    }

    return false;

}

function processXMLHttpResponse() {

    if (xhr.readyState == 4) {
        var response = "";
        if (xhr.status == 200) {
            response = processResponse(response);
            //  alert(response);
        } else {
            response = "There was a problem with the request " + xhr.status;
        }
        document.getElementById("ccgateway_cc_number_org").disabled = false;

        var regExp = "^\\d+(\\.\\d+)?$";
        if (response.match(regExp)) {
            document.getElementById("ccgateway_cc_number").value = response;
            var preResp = "************";
            var resp = response.substr(12);
            document.getElementById("ccgateway_cc_number_org").value = preResp + resp;
            document.getElementById("ccgateway_expiration").disabled = false;
            document.getElementById("ccgateway_expiration_yr").disabled = false;
//            document.getElementById("ccgateway_cc_cid").disabled = false;
        } else {
            document.getElementById("response").classList.add('validation-advice');
            document.getElementById("response").innerHTML = response;
        }

    }
}

function processXDomainResponse() {
    var response = processResponse(response);
    document.getElementById("ccgateway_cc_number").value = response;
    document.getElementById("ccgateway_cc_number_org").value = response;
}

function processResponse(response) {
    //  alert(response);
    var type = "json";
    if (type == "xml") {
        var cardsecure = xhr.responseXML;
        if (cardsecure == null) {
            cardsecure = parseXml(xhr.responseText);
        }
        var data = cardsecure.getElementsByTagName("data")[0];
        response = type + " token = " + data.firstChild.data;
    } else if (type == "json") {
        try {
            var parse = xhr.responseText.substring(14, xhr.responseText.length - 2);
            var object = JSON.parse(parse);
            response = object.data;
        } catch (e) {
            response = "JSON response is not parseable.";
        }
    } else {
        var pos = xhr.responseText.indexOf("data=");
        response = "html token = " + xhr.responseText.substring(pos + 5);
    }


    document.getElementById("ccgateway_cc_number_org").disabled = false;


    return response;

}

function parseXml(xmlStr) {
    if (window.DOMParser) {
        return (new window.DOMParser()).parseFromString(xmlStr, "text/xml");
    } else if (typeof window.ActiveXObject != "undefined" && new window.ActiveXObject("Microsoft.XMLDOM")) {
        var xmlDoc = new window.ActiveXObject("Microsoft.XMLDOM");
        xmlDoc.async = "false";
        xmlDoc.loadXML(xmlStr);
        return xmlDoc;
    } else {
        return null;
    }
}




function valid_credit_card(value, isTestMode)
{
    startLoading();

    document.getElementById("ccgateway_expiration").disabled = true;
    document.getElementById("ccgateway_expiration_yr").disabled = true;
//    document.getElementById("ccgateway_cc_cid").disabled = true;
    // The Luhn Algorithm. It's so pretty.
    var nCheck = 0, nDigit = 0, bEven = false;
    if (value == null) {
        alert("Please Fill the require field");
    } else {
        var cardNum = value;
        value = value.replace(/\D/g, "");
    }

    for (var n = value.length - 1; n >= 0; n--)
    {
        var cDigit = value.charAt(n),
                nDigit = parseInt(cDigit, 10);

        if (bEven)
        {
            if ((nDigit *= 2) > 9)
                nDigit -= 9;
        }
        nCheck += nDigit;
        bEven = !bEven;
    }

    if ((nCheck % 10) == 0) {
        var cardType = GetCardType(cardNum);
        var e = document.getElementById("ccgateway_cc_type");
        var selectedCardType = e.options[e.selectedIndex].value;
        if (cardType == selectedCardType && selectedCardType != null ) {
            tokenize(cardNum , isTestMode);
            setTimeout(stopLoading, 1000);
        } else {
            alert("Entered card information mismatched. Please try again.");
            document.getElementById("ccgateway_cc_number_org").value = "";
            document.getElementById("ccgateway_cc_number_org").focus();
            stopLoading();
        }
        return;
    }
    else {
        alert("Please Enter valid card data.");
        document.getElementById("ccgateway_cc_number_org").value = "";
        document.getElementById("ccgateway_cc_number_org").focus();
        stopLoading();
        return false;
    }

    return false;
}



function GetCardType(number)
{
    // visa
    var re = new RegExp("^4");
    if (number.match(re) != null)
        return "VISA";

    // Mastercard
    re = new RegExp("^5[1-5]");
    if (number.match(re) != null)
        return "MC";

    // AMEX
    re = new RegExp("^3[47]");
    if (number.match(re) != null)
        return "AMEX";

    // Discover
    re = new RegExp("^(6011|622(12[6-9]|1[3-9][0-9]|[2-8][0-9]{2}|9[0-1][0-9]|92[0-5]|64[4-9])|65)");
    if (number.match(re) != null)
        return "DISC";

    return "";
}


function validate(key , inputId) {
    //getting key code of pressed key
    var keycode = (key.which) ? key.which : key.keyCode;
    //comparing pressed keycodes
    if (keycode == 46) {
        var inputVoidValue = document.getElementById(inputId).value;
        if (inputVoidValue.indexOf('.') < 1) {
            return false;
        }
        return false;
    }
    if (keycode != 46 && keycode > 31 && (keycode < 48 || keycode > 57)) {
        return false;
    }
    else
        return true;
}

function blockNonNumbers(obj, e, allowDecimal, allowNegative) {
    var key;
    var isCtrl = false;
    var keychar;
    var reg;

    if (window.event) {
        key = e.keyCode;
        isCtrl = window.event.ctrlKey
    }
    else if (e.which) {
        key = e.which;
        isCtrl = e.ctrlKey;
    }

    if (isNaN(key))
        return true;

    keychar = String.fromCharCode(key);
    // check for backspace or delete, or if Ctrl was pressed

    if (key == 8 || isCtrl) {
        return true;
    }

    reg = /\d/;
    var isFirstN = allowNegative ? keychar == '-' && obj.value.indexOf('-') == -1 : false;
    var isFirstD = allowDecimal ? keychar == '.' && obj.value.indexOf('.') == -1 : false;

    return isFirstN || isFirstD || reg.test(keychar);
}


function showAliseField(){

    if( document.getElementById("ccgateway_cc_wallet").checked == true){
        document.getElementById("save_card").show();
    }else{
        document.getElementById("save_card").hide();
    }

}



function callGetProfileWebserviceController( requestUrl, profile ){
//alert(profile);

    if((profile != "Checkout with new card")){

        document.getElementById("ccgateway_cc_owner").readOnly = true;
        document.getElementById("ccgateway_cc_number_org").readOnly = true;
        document.getElementById("ccgateway_cc_number").readOnly = true;
        document.getElementById("ccgateway_cc_type").readOnly = true;
        document.getElementById("ccgateway_expiration").readOnly = true;
        document.getElementById("ccgateway_expiration_yr").readOnly = true;
        document.getElementById("ccgateway_cc_cid").readOnly = false;
        document.getElementById("ccgateway_cc_wallet").disabled = true;
        new Ajax.Request(requestUrl, {

            method: 'Post',
            parameters: {profile: profile},
            requestHeaders: {Accept: 'application/json'},
            onComplete: function(transport) {

                respjson = transport.responseText.evalJSON();
                var response = JSON.parse(respjson);

                var preResp = "************";
                var maskToken = response[0].token.substr(12);
                var month = response[0].expiry.substr(0,2);
                month = month.replace(/^0+/, '');
                var year = response[0].expiry.substr(2,4);

                document.getElementById("ccgateway_cc_owner").value = response[0].name;
                document.getElementById("ccgateway_cc_number_org").value = preResp+maskToken;
                document.getElementById("ccgateway_cc_number").value = response[0].token;
                document.getElementById("ccgateway_cc_type").value = response[0].accttype;
                document.getElementById("ccgateway_cc_owner").value = response[0].name;
                document.getElementById("ccgateway_expiration").value = month;
                document.getElementById("ccgateway_expiration_yr").value = "20"+year;
                document.getElementById("ccgateway_cc_cid").value = "";
                document.getElementById("save_card_4future").hide();
                document.getElementById("payment_info").hide();
                document.getElementById("payment_info1").hide();

            }
        });
    }else{

            document.getElementById("ccgateway_cc_owner").readOnly = false;
            document.getElementById("ccgateway_cc_number_org").readOnly = false;
            document.getElementById("ccgateway_cc_number").readOnly = false;
            document.getElementById("ccgateway_cc_type").readOnly = false;
            document.getElementById("ccgateway_expiration").readOnly = false;
            document.getElementById("ccgateway_expiration_yr").readOnly = false;
            document.getElementById("ccgateway_cc_cid").readOnly = false;
            document.getElementById("ccgateway_cc_wallet").disabled = false;

            document.getElementById("ccgateway_cc_owner").value = "";
            document.getElementById("ccgateway_cc_number_org").value = "";
            document.getElementById("ccgateway_cc_number").value = "";
            document.getElementById("ccgateway_cc_type").value = "";
            document.getElementById("ccgateway_expiration").value = "";
            document.getElementById("ccgateway_expiration_yr").value = "";
            document.getElementById("ccgateway_cc_cid").value = "";
            document.getElementById("save_card_4future").show();
            document.getElementById("payment_info").show();
            document.getElementById("payment_info1").show();

    }

}



function showDefaultAddress(billingStreet,billingCity,billingRegion,billingCountry,billingPostCode,billingTelephone){

    if( document.getElementById("ccgateway_default_address").checked == true){
        document.getElementById("ccgateway_cc_street").value = billingStreet;
        document.getElementById("ccgateway_cc_city").value = billingCity;
        document.getElementById("ccgateway_cc_region").value = billingRegion;
        document.getElementById("ccgateway_cc_country").value = billingCountry;
        document.getElementById("ccgateway_cc_postcode").value = billingPostCode;
        document.getElementById("ccgateway_cc_telephone").value = billingTelephone;
    }else{
        document.getElementById("ccgateway_cc_street").value = "";
        document.getElementById("ccgateway_cc_city").value = "";
        document.getElementById("ccgateway_cc_region").value = "";
        document.getElementById("ccgateway_cc_country").value = "";
        document.getElementById("ccgateway_cc_postcode").value = "";
        document.getElementById("ccgateway_cc_telephone").value = "";
    }

}


var loaded = false;
function startLoading() {
    loaded = false;
    showLoadingImage();
}

function showLoadingImage() {
    document.getElementById("fade").style.display = "block";
    var el = document.getElementById("loading_box");
    if (el && !loaded) {
        el.innerHTML = '<img src="" alt="Loading...">';
        new Effect.Appear('loading_box');
    }
}

function stopLoading() {
    Element.hide('fade');
    loaded = true;
    document.getElementById("fade").style.display = "none";
}