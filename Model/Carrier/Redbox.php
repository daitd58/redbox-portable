<?php

namespace Redbox\Portable\Model\Carrier;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Psr\Log\LoggerInterface;

/**
 * Redbox shipping model
 */
class Redbox extends AbstractCarrier implements CarrierInterface
{

    const CODE = 'redboxportable';
    /**
     * @var string
     */
    protected $_code = 'redboxportable';

    /**
     * @var bool
     */
    protected $_isFixed = true;

    /**
     * @var ResultFactory
     */
    private $rateResultFactory;

    /**
     * @var MethodFactory
     */
    private $rateMethodFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ErrorFactory $rateErrorFactory
     * @param LoggerInterface $logger
     * @param ResultFactory $rateResultFactory
     * @param MethodFactory $rateMethodFactory
     * @param array $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        ResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory,
        array $data = []
    ) {
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);

        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        $this->logger = $logger;
    }

    /**
     * Check subtotal for allowed free shipping
     *
     * @param RateRequest $request
     *
     * @return bool
     */
    private function isFreeShippingRequired(RateRequest $request): bool
    {
        $minSubtotal = $request->getPackageValueWithDiscount();
        if ($request->getBaseSubtotalWithDiscountInclTax() && $this->getConfigFlag('tax_including')) {
            $minSubtotal = $request->getBaseSubtotalWithDiscountInclTax();
        }

        return $minSubtotal >= $this->getConfigData('amount');
    }

    /**
     * Custom Shipping Rates Collector
     *
     * @param RateRequest $request
     * @return \Magento\Shipping\Model\Rate\Result|bool
     */
    public function collectRates(RateRequest $request)
    {
        $cities = [
            "الرياض", "Riyadh", "الخرج", "Kharj", "الظهران", "Dhahran", "الجبيل",
            "Jubail", "الخبر", "Khubar", "راس تنورة", "Ras Tannurah", "الدرعية",
            "Diriyah", "جدة", "Jeddah", "الدمام", "Dammam", "الهفوف‎", "Al Hofuf"
        ];
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        if ($request->getDestCountryId() !== 'SA' || 
            in_array(strtolower($request->getDestCity()), array_map('strtolower', $cities)) === false
        ) {
            return false;
        }

        /** @var \Magento\Shipping\Model\Rate\Result $result */
        $result = $this->rateResultFactory->create();

        /** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $method */
        $method = $this->rateMethodFactory->create();

        $method->setCarrier($this->_code);
        $method->setCarrierTitle($this->getConfigData('title'));

        $method->setMethod($this->_code);
        $method->setMethodTitle($this->getConfigData('name'));

        $shippingCost = (float)$this->getConfigData('price');
        if ($this->getConfigData('amount') && $this->isFreeShippingRequired($request)) {
            $shippingCost = 0;
        }
        
        $method->setPrice($shippingCost);
        $method->setCost($shippingCost);

        $result->append($method);

        return $result;
    }

    /**
     * @return array
     */
    public function getAllowedMethods()
    {
        return [$this->_code => $this->getConfigData('name')];
    }

    /**
     * @return bool
     */
    public function isShippingLabelsAvailable()
    {
        return false;
    }
}
