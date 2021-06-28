<?php
/**
  * (c) Redbox Parcel Lockers <thamer@redboxsa.com>
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 *
 * Built by Redbox Technologies, <thamer@redboxsa.com>
 */

namespace Redbox\Portable\Observer;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote\Address;
use Psr\Log\LoggerInterface as PsrLoggerInterface;
use Redbox\Portable\Api\Data\AddressRepositoryInterface;
use Redbox\Portable\Model\Carrier\Redbox as Carrier;
use Redbox\Portable\Api\Data\AddressInterfaceFactory;

/**
 * Class SaveShippingAddressObserver
 * @package Redbox\Portable\Observer
 */
class SaveShippingAddressObserver implements ObserverInterface
{

    /**
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    /**
     * @var AddressInterfaceFactory
     */
    private $addressFactory;

    /** @var RequestInterface  */
    private $request;

    private $logger;

    /**
     * SaveShippingAddressObserver constructor
     * @param AddressRepositoryInterface $addressRepository
     * @param RequestInterface $request
     */
    public function __construct(
        AddressRepositoryInterface $addressRepository,
        RequestInterface $request,
        AddressInterfaceFactory $addressFactory,
        PsrLoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->addressRepository = $addressRepository;
        $this->addressFactory = $addressFactory;
        $this->request = $request;
    }

    /**
     * Save quote shipping address locker machine identifier
     * Triggered by:
     *      - sales_quote_address_save_after
     *
     * @param Observer $observer
     * @throws CouldNotSaveException
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        /** @var Address $quoteAddress */
        $quoteAddress = $observer->getData('quote_address');

        if ($quoteAddress->getAddressType() !== Address::ADDRESS_TYPE_SHIPPING
            || $quoteAddress->getShippingMethod() !== Carrier::CODE . '_' . Carrier::CODE
        ) {
            return;
        }

        try {
            $address = $this->addressRepository->getByQuoteAddressId($quoteAddress->getId());
        } catch (NoSuchEntityException $e) {
            $address = $this->addressFactory->create();
            $address->setShippingAddressId($quoteAddress->getId());
        }
        $this->addressRepository->save($address);
    }
}
