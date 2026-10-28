<?php
/**
 * Resolves the Magento store (ID + base URL) a product-state event belongs to.
 */
namespace Kangaroorewards\Core\Model\ProductState;

use Magento\Store\Model\StoreManagerInterface;

class StoreResolver
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(StoreManagerInterface $storeManager)
    {
        $this->storeManager = $storeManager;
    }

    /**
     * Admin saves run in the "all store views" scope (ID 0), which the integration cannot map to a
     * store, so they fall back to the default store view of each website the product belongs to.
     *
     * @param int|string|null $storeId Null uses the current store
     * @param int[] $websiteIds Only used when the scope is the admin one
     * @return array[] List of ['store_id' => int, 'domain' => string]
     */
    public function resolve($storeId = null, array $websiteIds = [])
    {
        if ($storeId === null) {
            $storeId = $this->storeManager->getStore()->getId();
        }

        $stores = [];
        if ((int)$storeId > 0) {
            $stores[] = $this->storeManager->getStore((int)$storeId);
        } else {
            foreach ($websiteIds as $websiteId) {
                $stores[] = $this->storeManager->getWebsite($websiteId)->getDefaultStore();
            }
            if (!$stores) {
                $stores[] = $this->storeManager->getDefaultStoreView();
            }
        }

        $resolved = [];
        foreach ($stores as $store) {
            if (!$store || (int)$store->getId() <= 0) {
                continue;
            }
            $resolved[(int)$store->getId()] = [
                'store_id' => (int)$store->getId(),
                'domain' => (string)$store->getBaseUrl(),
            ];
        }

        return array_values($resolved);
    }
}
