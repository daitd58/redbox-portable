<?php
/**
  * (c) Redbox Parcel Lockers <thamer@redboxsa.com>
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 *
 * Built by Redbox Technologies, <thamer@redboxsa.com>
 */

namespace Redbox\Portable\Model;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Redbox\Portable\Api\Data\AddressInterface;
use Redbox\Portable\Api\Data\AddressRepositoryInterface;
use Redbox\Portable\Model\ResourceModel\Checkout\Address as ResourceAddress;

/**
 * Class AddressRepository
 * @package Redbox\Portable\Model
 */
class AddressRepository implements AddressRepositoryInterface
{
    /**
     * @var ResourceAddress
     */
    private $resource;

    /**
     * @var AddressFactory
     */
    private $addressFactory;

    /**
     * AddressRepository constructor
     *
     * @param ResourceAddress $resource
     * @param AddressFactory $addressFactory
     */
    public function __construct(ResourceAddress $resource, AddressFactory $addressFactory)
    {
        $this->resource = $resource;
        $this->addressFactory = $addressFactory;
    }

    /**
     * Load address by entity id
     *
     * @param string|int $addressId
     * @return AddressInterface
     * @throws NoSuchEntityException
     */
    public function getById($addressId)
    {
        /** @var AddressInterface $address */
        $address = $this->addressFactory->create();
        $this->resource->load($address, $addressId);

        if (!$address->getId()) {
            throw new NoSuchEntityException(
                __('RedBox checkout address with id "%1" does not exist.', $addressId)
            );
        }

        return $address;
    }

    /**
     * Load address by quote address id
     *
     * @param string|int $quoteAddressId
     * @return AddressInterface
     * @throws NoSuchEntityException
     */
    public function getByQuoteAddressId($quoteAddressId)
    {
        /** @var AddressInterface $address */
        $address = $this->addressFactory->create();
        $this->resource->load($address, $quoteAddressId, AddressInterface::SHIPPING_ADDRESS_ID);

        if (!$address->getId()) {
            throw new NoSuchEntityException(
                __('RedBox checkout address for quote address id "%1" does not exist.', $quoteAddressId)
            );
        }

        return $address;
    }

    /**
     * Save address
     *
     * @param AddressInterface $address
     * @return AddressInterface
     * @throws CouldNotSaveException
     */
    public function save(AddressInterface $address)
    {
        try {
            $this->resource->save($address);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }

        return $address;
    }
}
