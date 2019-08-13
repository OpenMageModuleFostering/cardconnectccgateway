<?php
/**
 * @brief Defines the class representing CardConnect Token Managment block shows in Customer Account Admin Block
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
class Cardconnect_Ccgateway_Block_Adminhtml_Customer_Data extends Mage_Adminhtml_Block_Abstract implements Varien_Data_Form_Element_Renderer_Interface {

    /**
     * Render block
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element) {
        $customer = Mage::registry('current_customer');
        $requestUrl = Mage::getUrl('ccgateway/payment/deletewallet',array('_secure'=>true));

        $html = '';
        $tokens = $this->getCcCustomerToken();

        if (count($tokens)!=0) {
            $html .="<tr>";
			$html .= "<th>Card Alias</th>";
            $html .="<th></th>";
			$html .= "<th>Card Number</th>";
            $html .="<th></th>";
            $html .="<th>Operation</td></th>";

            $i = 1;
            foreach ($tokens as $data) {

                $html .= "<tr>";
				$html .= "<td>".$data->getData('CC_CARD_NAME') . "</td>";
                $html .= '<td><input type="hidden" id="customer_id_' . $i . '" value="' . $customer->getId() . '"></td>';
                $html .= "<td>".substr_replace($data->getData('CC_MASK'), str_repeat("X", 12), 0, 12) . "</td>";
                $html .= '<td><input type="hidden" id="token_num_' . $i . '" value="' . $data->getData('CC_ID') . '"></td>';
                $html .= '<td><button id="card_delete" title="Delete"';
                $html .= ' onclick="deleteCardDataController(' . $i . ', \'' . $requestUrl . '\')" type="button" class="submit" >Delete</button></td></tr>';

                $i++;
            }
        } else {
            $html .="<tr><th>No Card Available</th></tr>";
        }

        return $html;
    }




    /**
     * Returns Profile Data for Card Managment
     *
     * @return string
     */
    public function getCcCustomerToken() {

        $customer = Mage::registry('current_customer');

        $collection = Mage::getModel('cardconnect_ccgateway/cardconnect_wallet')->getCollection()
                ->addFieldToFilter('CC_USER_ID', array('eq' => $customer->getId()))
                ->addFieldToSelect("*")
                ->setOrder('CC_CREATED', 'DESC');

        return $collection;
    }

}
?>
<script type="text/javascript">

    function deleteCardDataController(rowNum, requestUrl) {

        var tokenNumberId = "token_num_" + rowNum;
        var customerId = "customer_id_" + rowNum;
        var delId = document.getElementById(tokenNumberId).value;
        var customerId = document.getElementById(customerId).value;

        var data = {"cc_id": delId, "customerId": customerId};

        new Ajax.Request(requestUrl, {
            method: 'Post',
            parameters: data,
            onComplete: function (transport) {
                alert(transport.responseText); //return false;
                window.location.reload();
            }
        });

    }

</script>