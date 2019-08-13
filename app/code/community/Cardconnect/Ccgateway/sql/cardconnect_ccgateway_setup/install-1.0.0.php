<?php

/**
 * @brief Create CardConnect response table and create custom order status in database
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
$installer = $this;
$installer->startSetup();
$installer->run("
     
DROP TABLE IF EXISTS {$this->getTable('cardconnect_resp')};
CREATE TABLE {$this->getTable('cardconnect_resp')} (
  `CC_ID`               BIGINT NOT NULL  AUTO_INCREMENT PRIMARY KEY,
  `CC_ACTION`           VARCHAR(50),
  `CC_RETREF`           VARCHAR(50),
  `CC_AMT`              DECIMAL(14,2),
  `CC_AUTHCODE`         VARCHAR(6),
  `CC_ORDERID`          BIGINT,
  `CC_TOKEN`            BIGINT,
  `CC_MERCHID`          BIGINT,
  `CC_RESPSTAT`         CHAR(1),
  `CC_RESPCODE`         CHAR(3),
  `CC_RESPTEXT`         VARCHAR(50),
  `CC_RESPPROC`         CHAR(4),
  `CC_AVSRESP`          VARCHAR(2),
  `CC_CVVRESP`          CHAR(1),
  `CC_SETLSTAT`         VARCHAR(50),
  `CC_VOIDED`           VARCHAR(50),
  `CC_CREATED`          DATETIME

) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE INDEX CC_ORDERID_INDEX ON   {$this->getTable('cardconnect_resp')}  ( CC_ORDERID);
CREATE INDEX CC_RETREF_INDEX ON   {$this->getTable('cardconnect_resp')}  ( CC_RETREF);    

");

// Set custom order status in sales_order_status table

$statusTable = $installer->getTable('sales/order_status');



// Delete if order status exist
$installer->run('
delete  from ' . $statusTable . ' where status = "cardconnect_capture";
delete  from ' . $statusTable . ' where status = "cardconnect_void";
delete  from ' . $statusTable . ' where status = "cardconnect_refund";
delete  from ' . $statusTable . ' where status = "cardconnect_reject";
delete  from ' . $statusTable . ' where status = "cardconnect_txn_settled";
delete  from ' . $statusTable . ' where status = "cardconnect_processing";
');

// Insert status
$installer->getConnection()->insertArray(
        $statusTable, array(
    'status',
    'label'
        ), array(
    array('status' => 'cardconnect_capture', 'label' => 'CardConnect Capture'),
    array('status' => 'cardconnect_void', 'label' => 'CardConnect Void'),
    array('status' => 'cardconnect_refund', 'label' => 'CardConnect Refund'),
    array('status' => 'cardconnect_reject', 'label' => 'CardConnect Rejected'),
    array('status' => 'cardconnect_txn_settled', 'label' => 'CardConnect Txn Settled'),
    array('status' => 'cardconnect_processing', 'label' => 'CardConnect Processing')
        )
);

$installer->run("
DROP TABLE IF EXISTS {$this->getTable('cardconnect_wallet')};
CREATE TABLE {$this->getTable('cardconnect_wallet')} (
  `CC_ID`               	BIGINT NOT NULL  AUTO_INCREMENT PRIMARY KEY,
  `CC_USER_ID`           	BIGINT	NOT NULL COMMENT 'Customer Id',
  `CC_PROFILEID`        	VARCHAR(50)	NOT NULL COMMENT 'CC Profile Id',
  `CC_ACCTID`           	BIGINT	NOT NULL,
  `CC_MASK`          		VARCHAR(50),
  `CC_CARD_NAME`        	VARCHAR(50) COMMENT 'Alias',
  `CC_DEFAULT_CARD` 		ENUM('Y','N') DEFAULT 'N',
  `CC_CREATED`          	DATETIME

) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE INDEX CC_USER_ID_INDEX ON   	 {$this->getTable('cardconnect_wallet')}  (CC_USER_ID);
CREATE INDEX CC_PROFILEID_INDEX ON       {$this->getTable('cardconnect_wallet')}  (CC_PROFILEID);
CREATE INDEX CC_ACCTID_INDEX ON   	 {$this->getTable('cardconnect_wallet')}  (CC_ACCTID);    
    
");


$installer->endSetup();


