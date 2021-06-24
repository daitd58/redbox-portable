<?php
/**
 * (c) Redbox Parcel Lockers <thamer@redboxsa.com>
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 *
 * Built by Redbox Technologies, <thamer@redboxsa.com>
 */

namespace Redbox\Portable\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Redbox\Portable\Model\Carrier\Redbox as Carrier;

/**
 * Class Data
 */
class Data extends AbstractHelper
{

    /**
     * @return string
     */
    public function getMethodCode()
    {
        return Carrier::CODE;
    }
}
