<?php
/**
 * @brief To Inherit the phtml file having additonal info.
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

class Cardconnect_Ccgateway_Block_Adminhtml_Sales_Order_Creditmemo_Create_Form extends Mage_Adminhtml_Block_Sales_Order_Creditmemo_Create_Form {

    /**
     * This method has been overridden merely for the purpose of setting up a new view file
     * to be used in place of the default theme folder.
     *
     * @see app/code/core/Mage/Core/Block/Mage_Core_Block_Template#fetchView($fileName)
     */
    public function fetchView($fileName) {
        extract($this->_viewVars);
        $do = $this->getDirectOutput();
        if (!$do) {
            ob_start();
        }
        include getcwd() . '/app/code/community/Cardconnect/Ccgateway/blocks/creditmemo/create/form.phtml';
        if (!$do) {
            $html = ob_get_clean();
        } else {
            $html = '';
        }
        return $html;
    }

}
