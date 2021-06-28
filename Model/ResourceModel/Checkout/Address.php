<?php
/**
  * (c) Redbox Parcel Lockers <thamer@redboxsa.com>
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 *
 * Built by Redbox Technologies, <thamer@redboxsa.com>
 */

namespace Redbox\Portable\Model\ResourceModel\Checkout;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Redbox\Portable\Api\Data\AddressInterface;

/**
 * Class Address
 * @package Redbox\Portable\Model\ResourceModel\Checkout
 */
class Address extends AbstractDb
{
    /**
     * Define main table and initialize connection
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('redbox_portable_checkout_address', AddressInterface::ENTITY_ID);
    }
}
