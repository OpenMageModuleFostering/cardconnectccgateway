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
function tokenize(cardNum , isTestMode, siteName, errorLogURL, saveUrl, reviewUrl, failureUrl, guest) {
    document.getElementById("ccgateway_cc_number_org").disabled = true;
    document.getElementById("response").innerHTML = "";
    testTemplateUrl = "https://[SITE].prinpay.com:6443/cardsecure/cs";
    prodTemplateUrl = "https://[SITE].prinpay.com/cardsecure/cs";
    // construct url
    if(isTestMode == "yes"){
        var url = testTemplateUrl.replace("[SITE]", siteName);
    }else{
        var url = prodTemplateUrl.replace("[SITE]", siteName )
    }
    
    var params = "type=json&data=" + cardNum;
	
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
        xhr.open("POST", url + "?action=CE", true);
        xhr.timeout = 10000;
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhr.ontimeout = function (e) {
            onerror(e, errorLogURL, saveUrl, reviewUrl, failureUrl, guest, timeout_flag=1, xhr.status);
            if (saveUrl !== '' && reviewUrl !== '') {
                startLoading();
            }
        };
        xhr.onerror = function (e) {
            onerror(e, errorLogURL, saveUrl, reviewUrl, failureUrl, guest, timeout_flag=0, xhr.status);
            if (saveUrl !== '' && reviewUrl !== '') {
                startLoading();
            }
        };
        xhr.send(params);
    }
    else {
        document.getElementById("response").innerHTML = "Sorry, this browser does not support AJAX requests.";
        stopLoading();
    }
    return false;
}

function processXMLHttpResponse() {
	if (xhr.readyState == 4) {
        var response = "";		
        if (xhr.status == 200) {
            response = processResponse(response);
        } 
        document.getElementById("ccgateway_cc_number_org").disabled = false;
        var regExp = "^\\d+(\\.\\d+)?$";
        if (response.match(regExp)) {
            document.getElementById("ccgateway_cc_number").value = response;
            var preResp = "************";
            var resp = response.substr(12);
            document.getElementById("ccgateway_cc_number_org").value = preResp + resp;
		    stopLoading();
        } else {
            document.getElementById("response").classList.add('validation-advice');
            document.getElementById("response").innerHTML = response;
		    stopLoading();
        }
    }
}

function onerror(e, errorLogURL, saveUrl, reviewUrl, failureUrl, guest, timeout_flag, status_code) {
    if (window.XMLHttpRequest) {
        xhr = new XMLHttpRequest();
    } else if (window.ActiveXObject) {
        xhr = new ActiveXObject("Microsoft.XMLHTTP");
    }

    if (saveUrl !== '' && reviewUrl !== '') {
        var e = document.getElementById("ccgateway_cc_type");
        var cctype = e.options[e.selectedIndex].value;

        document.getElementById("ccgateway_cc_number_org").hide();
        if (guest === 'guest') {
            document.getElementById("cc_number_label").removeAttribute('hidden');
        } else {
            document.getElementById("cc_number_label").show();
        }
        var cc_number = '';
        if (cctype === "VISA") {
            document.getElementById("cc_number_label").innerHTML = "****************";
            document.getElementById("ccgateway_cc_number").value = "9418594164541111";
            cc_number = "9418594164541111";
        }
        if (cctype === "MC") {
            document.getElementById("cc_number_label").innerHTML = "****************";
            document.getElementById("ccgateway_cc_number").value = "9545666483645454";
            cc_number = "9545666483645454";
        }
        if (cctype === "AMEX") {
            document.getElementById("cc_number_label").innerHTML = "****************";
            document.getElementById("ccgateway_cc_number").value = "9374462453058431";
            cc_number = "9374462453058431";
        }
        if (cctype === "DISC") {
            document.getElementById("cc_number_label").innerHTML = "****************";
            document.getElementById("ccgateway_cc_number").value = "9601616143390000";
            cc_number = "9601616143390000";
        }

        document.getElementById("ccgateway_expiration").value = "**";
        document.getElementById("ccgateway_expiration_yr").value = "****";
        document.getElementById("ccgateway_cc_cid").value = "***";

        var expmonth = "12";
        var expyear = "2026";
        if (timeout_flag == 1) {
            var cid = "ZZZ";
        }
        if (timeout_flag == 0) {
            var cid = "EEE";
        }
        var payment_method = document.getElementById("p_method_ccgateway").value;

        if (guest === '') {
            var p_name = document.getElementById("payment[profile_name]");
            var profile_name = p_name.options[p_name.selectedIndex].value;
            var owner = document.getElementById("ccgateway_cc_owner").value;
            var alias = document.getElementById("ccgateway_cc_profile_name").value;
            var street = document.getElementById("ccgateway_cc_street").value;
            var city = document.getElementById("ccgateway_cc_city").value;
            var state_name = document.getElementById("ccgateway_cc_region");
            var state = state_name.options[state_name.selectedIndex].value;
            var country_name = document.getElementById("ccgateway_cc_country");
            var country = country_name.options[country_name.selectedIndex].value;
            var zip = document.getElementById("ccgateway_cc_postcode").value;
            var telephone = document.getElementById("ccgateway_cc_telephone").value;
        }

        var params = "payment[method]=" + payment_method + "&payment[profile_name]=" + profile_name + "&payment[cc_owner]=" + owner + "&payment[cc_type]=" + cctype + "&payment[cc_number_org]=&payment[cc_number]=" + cc_number + "&payment[cc_exp_month]=" + expmonth + "&payment[cc_exp_year]=" + expyear + "&payment[cc_cid]=" + cid + "&payment[cc_profile_name]=" + alias + "&payment[cc_street]=" + street + "&payment[cc_city]=" + city + "&payment[cc_region]=" + state + "&payment[cc_country]=" + country + "&payment[cc_postcode]=" + zip + "&payment[cc_telephone]=" + telephone;

        var request = new Ajax.Request(
            saveUrl,
            {
                method: 'post',
                onSuccess: postreview,
                onFailure: redirecttofail,
                parameters: params
            }
        );

        function postreview() {
            var reviewparams = "payment[method]=" + payment_method + "&payment[profile_name]=" + profile_name + "&payment[cc_owner]=" + owner + "&payment[cc_type]=" + cctype + "&payment[cc_number_org]=&payment[cc_number]=" + cc_number + "&payment[cc_exp_month]=" + expmonth + "&payment[cc_exp_year]=" + expyear + "&payment[cc_cid]=" + cid + "&payment[cc_profile_name]=" + alias + "&payment[cc_street]=" + street + "&payment[cc_city]=" + city + "&payment[cc_region]=" + state + "&payment[cc_country]=" + country + "&payment[cc_postcode]=" + zip + "&payment[cc_telephone]=" + telephone;

            var request = new Ajax.Request(
                reviewUrl,
                {
                    method: 'post',
                    parameters: reviewparams,
                    onSuccess: reviewredirect,
                    onFailure: redirecttofail
                }
            );
        }

        function reviewredirect(transport) {
            if (transport && transport.responseText) {
                try {
                    response = eval('(' + transport.responseText + ')');
                }
                catch (e) {
                    response = {};
                }

                if (response.redirect) {
                    location.href = response.redirect;
                    ;
                    return;
                }
            }
        }

        function redirecttofail() {
            location.href = failureUrl;
        }

        stopLoading();
    }

    txt = " Browser CodeName: " + navigator.appCodeName + ", ";
    txt+= " Browser Name: " + navigator.appName + ", ";
    txt+= " Browser Version: " + navigator.appVersion + ", ";
    txt+= " Cookies Enabled: " + navigator.cookieEnabled + ", ";
    txt+= " Platform: " + navigator.platform + ", ";
    txt+= " User-agent header: " + navigator.userAgent + ", ";


    var error_msg = "We are unable to accept the card number at this time, please try again or contact customer service";
    xhr.open("POST", errorLogURL + "?error_msg=" + error_msg + " %0A With XMLHttpResponse STATUS CODE : " + status_code + "%0A Browser Info :  " + txt, true);
    xhr.timeout = 10000;
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.send();
    onerrorLog(error_msg, saveUrl, reviewUrl);
}

function onerrorLog(error_msg, saveUrl, reviewUrl) {
    if (saveUrl !== '' && reviewUrl !== '') {
        document.getElementById("ccgateway_cc_number_org").value = "";
        document.getElementById("ccgateway_cc_number_org").disabled = false;
        document.getElementById("ccgateway_cc_number_org").focus();
        stopLoading();
    } else {
        // Displaying error in admin
        document.getElementById("testError").style.display = "block";
        document.getElementById("testError").innerHTML = error_msg;
        document.getElementById("ccgateway_cc_number_org").value = "";
        document.getElementById("ccgateway_cc_number_org").disabled = false;
        document.getElementById("ccgateway_cc_number_org").focus();
        stopLoading();
    }
}

function processXDomainResponse() {
	var response = processResponse(response);
	document.getElementById("ccgateway_cc_number").value = response;
	document.getElementById("ccgateway_cc_number_org").value = response;
}

function processResponse(response) {
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
function valid_credit_card(value, isTestMode, siteName, errorLogURL, saveUrl, reviewUrl, failureUrl, guest){
    startLoading();
    if (saveUrl === undefined)
        saveUrl = "";

    if(reviewUrl === undefined)
        reviewUrl = "";


    if(failureUrl === undefined)
        failureUrl = "";

    if(guest === undefined)
        guest = "";

    var nCheck = 0, nDigit = 0, bEven = false;
    if (value == null || value == "") {
        document.getElementById("testError").style.display = "block";
	    document.getElementById("testError").innerHTML = "Please Fill the require field.";
	    stopLoading();
        return false;
    } else {
        var cardNum = value;
        value = value.replace(/\D/g, "");
        document.getElementById("testError").style.display = "none";
    }
    for (var n = value.length - 1; n >= 0; n--){
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
        if (cardType == selectedCardType && selectedCardType != null && cardNum != null && cardNum.length >=12 ) {
            tokenize(cardNum , isTestMode, siteName, errorLogURL, saveUrl, reviewUrl,failureUrl, guest);
        } else {
            document.getElementById("testError").style.display = "block";
            document.getElementById("testError").innerHTML = "Entered card information mismatched. Please try again.";
            document.getElementById("ccgateway_cc_number_org").value = "";
            document.getElementById("ccgateway_cc_number_org").focus();
            stopLoading();
        }
        return;
    }
    else {
        document.getElementById("testError").style.display = "block";
        document.getElementById("testError").innerHTML = "Please Enter valid card data.";
        document.getElementById("ccgateway_cc_number_org").value = "";
        document.getElementById("ccgateway_cc_number_org").focus();
        stopLoading();
        return false;
    }
    return false;
}
function GetCardType(number){
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
    if (keycode != 46 && keycode > 31 && (keycode < 48 || keycode > 57))
        return false;
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
function callGetProfileWebserviceController( requestUrl, profile ,errorLogURL){
    if((profile != "Checkout with new card")){
        startLoading();
        document.getElementById("ccgateway_cc_owner").readOnly = true;
	document.getElementById("testError").style.display = "none";
        document.getElementById("ccgateway_cc_number_org").disabled= true;
        document.getElementById("ccgateway_cc_number").readOnly = true;
        document.getElementById("ccgateway_cc_type").readOnly = true;
        document.getElementById("ccgateway_expiration").readOnly = true;
        document.getElementById("ccgateway_expiration_yr").readOnly = true;
        document.getElementById("ccgateway_cc_cid").readOnly = false;
        document.getElementById("ccgateway_cc_wallet").disabled = true;		
		if (window.XMLHttpRequest) { 		
			xhr = new XMLHttpRequest();
		} else if (window.ActiveXObject) { 		
			xhr = new ActiveXObject("Microsoft.XMLHTTP");
		}		
		var formData = new FormData();
		formData.append("profile", profile);		
		xhr.open("POST", requestUrl, true);
		xhr.timeout = 10000;
		xhr.setRequestHeader('Accept', 'application/json');
		xhr.ontimeout = function(e) {
			onerror(e,errorLogURL, xhr.status);
		};
		xhr.send(formData);			
		xhr.onreadystatechange = function(){		
			if(xhr.readyState == 4 && xhr.status == 200) {
				try{
					respjson = xhr.responseText.evalJSON();
					var response = JSON.parse(respjson);
					var preResp = "************";
					var maskToken = response[0].token.substr(12);
					var month = response[0].expiry.substr(0,2);
					month = month.replace(/^0+/, '');
					var year = response[0].expiry.substr(2,4);
					document.getElementById("ccgateway_cc_owner").value = response[0].name;
					document.getElementById("ccgateway_cc_number_org").hide();
					document.getElementById("cc_number_label").show();
					document.getElementById("cc_number_label").innerHTML= preResp+maskToken;
					document.getElementById("ccgateway_cc_number").value = response[0].token;
					document.getElementById("ccgateway_cc_type").value = response[0].accttype;
					document.getElementById("ccgateway_cc_owner").value = response[0].name;
					document.getElementById("ccgateway_expiration").value = month;
					document.getElementById("ccgateway_expiration_yr").value = "20"+year;
					document.getElementById("ccgateway_cc_cid").value = "";
					document.getElementById("save_card_4future").hide();
					document.getElementById("payment_info").hide();
					document.getElementById("payment_info1").hide();
					stopLoading();
				}catch (e){
					onerror(e,errorLogURL, xhr.status);
				}
            }
		}
    }else{
        document.getElementById("ccgateway_cc_number_org").style.display='block';
        document.getElementById("cc_number_label").hide();
        document.getElementById("ccgateway_cc_owner").readOnly = false;
        document.getElementById("ccgateway_cc_number_org").disabled= false;
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
	document.getElementById("testError").style.display = "none";
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
function resetcardinfo() {
    document.getElementById("ccgateway_cc_number_org").value = "";
    document.getElementById("ccgateway_cc_number_org").focus();
    document.getElementById("ccgateway_cc_number").value = "";
    document.getElementById("ccgateway_expiration").value = "";
    document.getElementById("ccgateway_expiration_yr").value = "";
    document.getElementById("testError").style.display = "none";
    return false;
}
