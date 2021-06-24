<?php

namespace Redbox\Portable\Plugin\Order;

use Magento\Framework\HTTP\Client\Curl;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\Order\Interceptor;
use Redbox\Portable\Api\Data\AddressRepositoryInterface;
use Redbox\Portable\Helper\Points;
use Redbox\Portable\Model\Carrier\Redbox as Carrier;
use Psr\Log\LoggerInterface as PsrLoggerInterface;

class PlaceAfterPlugin
{
    private $helper;
    private $quoteFactory;
    private $addressRepository;
    private $curl;
    private $logger;

    public function __construct(
        Points $helper,
        QuoteFactory $quoteFactory,
        AddressRepositoryInterface $addressRepository,
        PsrLoggerInterface $logger,
        Curl $curl
    ) {
        $this->helper = $helper;
        $this->quoteFactory = $quoteFactory;
        $this->addressRepository = $addressRepository;
        $this->curl = $curl;
        $this->logger = $logger;
    }

    /**
    * @param OrderManagementInterface $orderManagementInterface
    * @param Interceptor $order
    * @return $order
    */
    public function afterPlace(OrderManagementInterface $orderManagementInterface, $order)
    {
        if ($order->getShippingMethod() == Carrier::CODE . '_' . Carrier::CODE && $this->helper->isActive()) {
            $quote = $this->quoteFactory->create()->loadByIdWithoutStore($order->getQuoteId());
            $quoteAddressId = $quote->getShippingAddress()->getId();
            if ($quoteAddressId) {
                $apiToken   = $this->helper->getApiToken();
                $apiEndpoint   = $this->helper->getApiEndpoint();
                $shippingAddress = $order->getShippingAddress();
                $billingAddress = $order->getBillingAddress();
                $redboxAddress = $this->addressRepository->getByQuoteAddressId($quoteAddressId);
                $pointId = $redboxAddress->getPointId();
                $payment = $order->getPayment();
                $method = $payment->getMethodInstance();
                $methodCode = $method->getCode();
                $this->logger->info('orderId: ' . $order->getId());

                // do something with order object (Interceptor )
                if ($apiToken) {
                    $createShipmentUrl = $apiEndpoint . '/create-shipment';
                    $items = [];
                    $orderProducts = $order->getAllItems();

                    foreach ($orderProducts as $orderProduct) {
                        array_push($items, [
                            'name' => $orderProduct->getName(),
                            'quantity' => $orderProduct->getQtyOrdered(),
                            'unitPrice' => $orderProduct->getPrice()
                        ]);
                    }

                    $fields = [
                        'reference' => $order->getIncrementId(),
                        'original_tracking_number' => $order->getId(),
                        'sender_name' => $billingAddress->getFirstName() . ' ' . $billingAddress->getLastName(),
                        'sender_email' => $billingAddress->getEmail(),
                        'sender_phone' => $billingAddress->getTelephone(),
                        'sender_address' => $billingAddress->getStreet()[0] . ' ' . $billingAddress->getCity() . ' ' . $billingAddress->getCountryId(),
                        'customer_name' => $shippingAddress->getFirstName() . ' ' . $shippingAddress->getLastName(),
                        "customer_email" => $shippingAddress->getEmail(),
                        'customer_phone' => $shippingAddress->getTelephone(),
                        'customer_address' => $shippingAddress->getStreet()[0] . ' ' . $shippingAddress->getCity() . ' ' . $shippingAddress->getCountryId(),
                        'cod_currency' => $order->getOrderCurrencyCode(),
                        'cod_amount' => $methodCode == 'cashondelivery' ? $order->getTotalDue() : 0,
                        'items' => $items,
                        'from_platform' => 'magento'
                    ];

                    $fields_json = json_encode($fields);
                    $headers = [
                        "Content-Type" => "application/json",
                        "Authorization" => "Bearer " . $apiToken
                    ];
                    $this->curl->setHeaders($headers);
                    $this->curl->post($createShipmentUrl, $fields_json);
                    $response = $this->curl->getBody();
                    $this->logger->info('Response ' . $response);
                    $response_json = json_decode($response, true);
                    if ($response_json['success'] && isset($response_json['url_shipping_label'])) {
                        $redboxAddress->setUrlShippingLabel($response_json['url_shipping_label']);
                        $redboxAddress->save();
                    }
                }
            }
        }

        return $order;
    }
}
