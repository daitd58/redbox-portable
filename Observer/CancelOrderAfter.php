<?php
/**
  * (c) Redbox Parcel Lockers <thamer@redboxsa.com>
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 *
 * Built by Redbox Technologies, <thamer@redboxsa.com>
 */

namespace Redbox\Portable\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Quote\Model\QuoteFactory;
use Psr\Log\LoggerInterface as PsrLoggerInterface;
use Redbox\Portable\Api\Data\AddressRepositoryInterface;
use Redbox\Portable\Helper\Points;
use Redbox\Portable\Model\Carrier\Redbox as Carrier;

class CancelOrderAfter implements ObserverInterface
{
    private $quoteFactory;
    private $helper;
    private $logger;
    private $addressRepository;
    private $curl;

    public function __construct(
        AddressRepositoryInterface $addressRepository,
        QuoteFactory $quoteFactory,
        Points $helper,
        PsrLoggerInterface $logger,
        Curl $curl
    ) {
        $this->curl = $curl;
        $this->logger = $logger;
        $this->helper = $helper;
        $this->addressRepository = $addressRepository;
        $this->quoteFactory = $quoteFactory;
    }

    public function execute(Observer $observer)
    {
        $order = $observer->getOrder();
        if ($order->getShippingMethod() == Carrier::CODE . '_' . Carrier::CODE && $this->helper->isActive()) {
            $orderId = $order->getIncrementId();
            $apiToken   = $this->helper->getApiToken();
            $apiEndpoint   = $this->helper->getApiEndpoint();
            if ($orderId && $apiToken) {
                $url = $apiEndpoint . '/cancel-shipment-by-order-id';
                $headers = ["Authorization: Bearer $apiToken", 'Content-Type: application/json'];
                $fields = [
                    'reference'			=> $orderId,
                ];
                $fields_json = json_encode($fields);

                $curl = curl_init($url);

                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($curl, CURLOPT_POSTFIELDS, $fields_json);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

                $response = curl_exec($curl);

                $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

                curl_close($curl);
            }
        }
    }
}
