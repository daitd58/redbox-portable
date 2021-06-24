<?php
/**
 * (c) Redbox Parcel Lockers <thamer@redboxsa.com>
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 *
 * Built by Redbox Technologies, <thamer@redboxsa.com>
 */

namespace Redbox\Portable\Helper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Points
 *
 * @package Redbox\Portable\Helper
 */
class Points
{

    /**
     * @var ScopeConfigInterface
     */
    public $scopeConfig;


    /**
     * Points constructor.
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;

    }//end __construct()


    /**
     * @return bool
     */
    public function isActive()
    {
        return (bool) $this->scopeConfig->getValue(
            'carriers/redboxportable/active',
            ScopeInterface::SCOPE_STORE
        );

    }//end isActive()


    /**
     * @return mixed
     */
    public function getApiToken()
    {
        return $this->scopeConfig->getValue(
            'carriers/redbox/api_token',
            ScopeInterface::SCOPE_STORE
        );

    }//end getApiToken()

    /**
     * @return bool
     */
    public function getProductionMode()
    {
        return $this->scopeConfig->getValue(
            'carriers/redbox/production',
            ScopeInterface::SCOPE_STORE
        );

    }//end getProductionMode()

    /**
     * @return mixed
     */
    public function getApiEndpoint()
    {
        if ($this->getProductionMode()) {
            return 'https://app.redboxsa.com/api/business/v1';
        }

        return 'https://stage.redboxsa.com/api/business/v1';
    }//end getApiEndpoint()

}//end class
