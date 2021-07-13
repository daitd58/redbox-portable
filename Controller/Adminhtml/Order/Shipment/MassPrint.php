<?php

namespace Redbox\Portable\Controller\Adminhtml\Order\Shipment;

use Magento\Backend\App\Action;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Backend\App\Action\Context;
use Magento\Shipping\Model\Shipping\LabelGenerator;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory as ShipmentCollectionFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Redbox\Portable\Helper\Points;
use Redbox\Portable\Model\Carrier\Redbox as Carrier;
use Psr\Log\LoggerInterface as PsrLoggerInterface;
use Magento\Framework\HTTP\Client\Curl;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MassPrint extends \Magento\Sales\Controller\Adminhtml\Order\AbstractMassAction
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Sales::shipment';

    /**
     * @var LabelGenerator
     */
    protected $labelGenerator;

    /**
     * @var FileFactory
     */
    protected $fileFactory;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var ShipmentCollectionFactory
     */
    protected $shipmentCollectionFactory;

    /**
     * @var Points
     */
    protected $helper;

    /**
     * @var PsrLoggerInterface
     */
    protected $logger;

    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @param Curl $curl
     * @param Points $helper
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param FileFactory $fileFactory
     * @param LabelGenerator $labelGenerator
     * @param ShipmentCollectionFactory $shipmentCollectionFactory
     */
    public function __construct(
        Curl $curl,
        Points $helper,
        PsrLoggerInterface $logger,
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        FileFactory $fileFactory,
        LabelGenerator $labelGenerator,
        ShipmentCollectionFactory $shipmentCollectionFactory
    ) {
        $this->curl = $curl;
        $this->logger = $logger;
        $this->helper = $helper;
        $this->fileFactory = $fileFactory;
        $this->collectionFactory = $collectionFactory;
        $this->shipmentCollectionFactory = $shipmentCollectionFactory;
        $this->labelGenerator = $labelGenerator;
        parent::__construct($context, $filter);
    }

    /**
     * Batch print shipping labels for whole shipments.
     * Push pdf document with shipping labels to user browser
     *
     * @param AbstractCollection $collection
     * @return ResponseInterface|ResultInterface
     */
    protected function massAction(AbstractCollection $collection)
    {
        $ids = [];
        if ($collection->getSize()) {
            /** @var \Magento\Sales\Model\Order\Shipment $shipment */
            foreach ($collection->getItems() as $order) {
                if ($order->getShippingMethod() == Carrier::CODE . '_' . Carrier::CODE && $this->helper->isActive()) {
                    $ids[] = $order->getIncrementId();
                }
            }
        }

        if (!empty($ids)) {
            $apiToken   = $this->helper->getApiToken();
            $apiEndpoint   = $this->helper->getApiEndpoint();
            $createShippingLabelsUrl = $apiEndpoint . '/create-url-shipping-label-bulk';
            $fields = [
                'order_numbers' => $ids,
            ];

            $fields_json = json_encode($fields);
            $headers = [
                "Content-Type" => "application/json",
                "Authorization" => "Bearer " . $apiToken
            ];
            $this->curl->setHeaders($headers);
            $this->curl->post($createShippingLabelsUrl, $fields_json);
            $response = $this->curl->getBody();
            $response_json = json_decode($response, true);
            if ($response_json['success'] && isset($response_json['url'])) {
                return $this->resultRedirectFactory->create()->setUrl($response_json['url']);
            }
        }

        $this->messageManager->addError(__('There are no shipping labels related to selected orders.'));
        return $this->resultRedirectFactory->create()->setPath('sales/order/');
    }
}
