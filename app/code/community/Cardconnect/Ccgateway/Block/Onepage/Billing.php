<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Cardconnect_Ccgateway_Block_Onepage_Billing extends Mage_Checkout_Block_Onepage_Billing {

    public function fetchView($fileName) {
        extract($this->_viewVars);
        $do = $this->getDirectOutput();
        if (!$do) {
            ob_start();
        }
        include getcwd() . '/app/code/community/Cardconnect/Ccgateway/blocks/billing.phtml';
        if (!$do) {
            $html = ob_get_clean();
        } else {
            $html = '';
        }
        return $html;
    }

}
