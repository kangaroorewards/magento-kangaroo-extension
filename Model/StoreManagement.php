<?php
namespace Kangaroorewards\Core\Model;
use Kangaroorewards\Core\Api\StoreManagementInterface;

class StoreManagement implements StoreManagementInterface
{
    private $_store;
    private $_storeManager;
    private $_regionFactory;
    private $_countryFactory;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Store\Model\Information $storeInfo,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Directory\Model\CountryFactory $countryFactory
    )
    {
        $this->_store = $storeInfo;
        $this->_storeManager = $storeManager;
        $this->_regionFactory = $regionFactory;
        $this->_countryFactory = $countryFactory;
    }

    /**
     * Returns all store Info
     *
     * @api
     * @return json string store address info.
     */
    public function getStoreAddressInfo()
    {

        $stores = $this->_storeManager->getStores();
        $object = null;
        foreach ($stores as $store) {
            $info = $this->_store->getStoreInformationObject($store);
            $region = null;
            if ($info->getRegionId()) {
                $region = $this->_regionFactory->create()->load($info->getRegionId())->getName();
            }

            $country = null;
            if ($info->getCountryId()) {
                $countryName = $this->_countryFactory->create()->loadByCode($info->getCountryId())->getName();
                $country = array('title' => $countryName,
                    'code' => $info->getCountryId());
            }
            $object[] = array('name' => $store->getName(),
                'city' => $info['city'],
                'region' => $region,
                'country' => $country,
                'street' => $info['street_line1'],
                'zipcode' => $info['postcode'],
                'shopId' => $store->getId());
        }
        return $object;
    }
}
