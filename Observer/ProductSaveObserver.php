<?php
/**
 * Reports the SKU of a saved product (price may have changed).
 *
 * Simple products and configurable-product variants report their own SKU. The configurable/grouped
 * parent is skipped: its price and stock belong to the children, which are saved (and reported)
 * individually.
 */
namespace Kangaroorewards\Core\Observer;

use Kangaroorewards\Core\Model\ProductState\ProductTypePolicy;
use Magento\Framework\Event\Observer;

class ProductSaveObserver extends AbstractProductStateObserver
{
    /**
     * @inheritdoc
     */
    protected function collect(Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();
        if (!$product || !ProductTypePolicy::isTracked($product->getTypeId())) {
            return;
        }

        $storeId = (int)$product->getStoreId();
        $this->track(
            $storeId,
            $storeId > 0 ? [] : (array)$product->getWebsiteIds(),
            $product->getSku(),
            [
                'price' => $product->getData('price'),
                'special_price' => $product->getData('special_price'),
            ]
        );
    }
}
