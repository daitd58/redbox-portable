<?php
/**
 * (c) Redbox Parcel Lockers <thamer@redboxsa.com>
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 *
 * Built by Redbox Technologies, <thamer@redboxsa.com>
 */

namespace Redbox\Portable\Block;

use Magento\Framework\View\Element\Template;
use Redbox\Portable\Helper\Data;

class Maps extends Template
{

    /**
     * @var Data
     */
    private $helper;
    /** @var Template\Context  */
    private $context;

    /**
     * Constructor
     *
     * @param Template\Context $context
     * @param Data $helper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Data $helper,
        array $data = []
    ) {
        $this->context = $context;
        $this->helper = $helper;
        parent::__construct($context, $data);
    }
}
