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
use Magento\Quote\Model\QuoteFactory;
use Zend\Http\Client;
use Zend\Http\Request;
use Zend\Http\Headers;
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

    public function __construct(
        AddressRepositoryInterface $addressRepository,
        QuoteFactory $quoteFactory,
        Points $helper,
        PsrLoggerInterface $logger
    ) {
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
                $fields = [
                    'reference' => $orderId,
                ];
                $fields_json = json_encode($fields);
                $httpHeaders = new Headers();
                $httpHeaders->addHeaders([
                    'Authorization' => 'Bearer ' . $apiToken,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ]);

                $request = new Request();
                $request->setHeaders($httpHeaders);
                $request->setUri($url);
                $request->setMethod(Request::METHOD_PUT);
                $request->setParameterPost($fields);
                $client = new Client();
                $options = [
                    'adapter'   => 'Zend\Http\Client\Adapter\Curl',
                    'curloptions' => [CURLOPT_FOLLOWLOCATION => true],
                    'maxredirects' => 0,
                    'timeout' => 60
                ];
                $client->setOptions($options);

                $response = $client->send($request);
            }
        }
    }
}
