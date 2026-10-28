<?php
/**
 * Reports the SKU of a stock item whose quantity or in-stock flag changed.
 */
namespace Kangaroorewards\Core\Observer;

use Kangaroorewards\Core\Model\ProductState\Dispatcher;
use Kangaroorewards\Core\Model\ProductState\ProductLookupInterface;
use Kangaroorewards\Core\Model\ProductState\ProductTypePolicy;
use Kangaroorewards\Core\Model\ProductState\StoreResolver;
use Magento\Framework\Event\Observer;
use Psr\Log\LoggerInterface;

class StockItemSaveObserver extends AbstractProductStateObserver
{
    /**
     * @var ProductLookupInterface
     */
    private $productLookup;

    public function __construct(
        Dispatcher $dispatcher,
        StoreResolver $storeResolver,
        LoggerInterface $logger,
        ProductLookupInterface $productLookup
    ) {
        parent::__construct($dispatcher, $storeResolver, $logger);
        $this->productLookup = $productLookup;
    }

    /**
     * @inheritdoc
     */
    protected function collect(Observer $observer)
    {
        $item = $observer->getEvent()->getItem();
        if (!$item || !ProductTypePolicy::isTracked($item->getTypeId()) || !$this->hasChanged($item)) {
            return;
        }

        foreach ($this->productLookup->findByIds([(int)$item->getProductId()]) as $product) {
            if (!ProductTypePolicy::isTracked($product['type_id'])) {
                continue;
            }
            $this->track(null, [], $product['sku'], [
                'qty' => $item->getData('qty'),
                'is_in_stock' => $item->getData('is_in_stock'),
            ]);
        }
    }

    /**
     * @param \Magento\Framework\DataObject $item
     * @return bool
     */
    private function hasChanged($item)
    {
        $originalQty = $item->getOrigData('qty');
        $originalStatus = $item->getOrigData('is_in_stock');
        if ($originalQty === null && $originalStatus === null) {
            return true;
        }

        return (float)$originalQty !== (float)$item->getData('qty')
            || (int)$originalStatus !== (int)$item->getData('is_in_stock');
    }
}
