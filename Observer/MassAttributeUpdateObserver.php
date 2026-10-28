<?php
/**
 * Reports the SKUs affected by a mass price update (Catalog > Update attributes).
 */
namespace Kangaroorewards\Core\Observer;

use Kangaroorewards\Core\Model\ProductState\Dispatcher;
use Kangaroorewards\Core\Model\ProductState\ProductLookupInterface;
use Kangaroorewards\Core\Model\ProductState\ProductTypePolicy;
use Kangaroorewards\Core\Model\ProductState\StoreResolver;
use Magento\Framework\Event\Observer;
use Psr\Log\LoggerInterface;

class MassAttributeUpdateObserver extends AbstractProductStateObserver
{
    const PRICE_ATTRIBUTES = ['price', 'special_price', 'special_from_date', 'special_to_date'];

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
        $event = $observer->getEvent();
        $attributes = (array)$event->getData('attributes_data');
        $productIds = (array)$event->getData('product_ids');
        if (!$productIds || !array_intersect(self::PRICE_ATTRIBUTES, array_keys($attributes))) {
            return;
        }

        $storeId = (int)$event->getData('store_id');
        foreach ($this->productLookup->findByIds($productIds) as $product) {
            if (ProductTypePolicy::isTracked($product['type_id'])) {
                $this->track($storeId, [], $product['sku']);
            }
        }
    }
}
