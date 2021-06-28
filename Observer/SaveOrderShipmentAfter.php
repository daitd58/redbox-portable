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
use Psr\Log\LoggerInterface as PsrLoggerInterface;
use Redbox\Portable\Helper\Points;
use Redbox\Portable\Model\Carrier\Redbox as Carrier;

/**
 * Class SaveOrderShipmentAfter
 * @package Redbox\Portable\Observer
 */
class SaveOrderShipmentAfter implements ObserverInterface
{

    private $helper;
    private $logger;
    private $curl;

    public function __construct(
        Points $helper,
        PsrLoggerInterface $logger,
        Curl $curl
    ) {
        $this->curl = $curl;
        $this->logger = $logger;
        $this->helper = $helper;
    }

    public function execute(Observer $observer)
    {
        $shipment = $observer->getShipment();
        $order = $shipment->getOrder();
        if ($order->getShippingMethod() == Carrier::CODE . '_' . Carrier::CODE && $this->helper->isActive()) {
            $apiToken   = $this->helper->getApiToken();
            $apiEndpoint   = $this->helper->getApiEndpoint();
            $url = $apiEndpoint . '/change-status-to-ready';

            if ($apiToken) {
                $headers = [
                    "Content-Type" => "application/json",
                    "Authorization" => "Bearer " . $apiToken
                ];
                $fields = [
                    "orderId" => $order->getIncrementId(),
                    "platform" => "magento"
                ];
                $this->curl->setHeaders($headers);
                $this->curl->post($url, json_encode($fields));
                $response = $this->curl->getBody();
            }
        }
    }
}
