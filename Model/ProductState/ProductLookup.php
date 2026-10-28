<?php
/**
 * Product lookup backed by the catalog product collection.
 */
namespace Kangaroorewards\Core\Model\ProductState;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

class ProductLookup implements ProductLookupInterface
{
    const CHUNK_SIZE = 500;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    public function __construct(CollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @inheritdoc
     */
    public function findByIds(array $productIds)
    {
        $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds))));
        $rows = [];
        foreach (array_chunk($productIds, self::CHUNK_SIZE) as $chunk) {
            $collection = $this->collectionFactory->create();
            $collection->addIdFilter($chunk);
            foreach ($collection as $product) {
                $rows[] = ['sku' => (string)$product->getSku(), 'type_id' => (string)$product->getTypeId()];
            }
        }

        return $rows;
    }
}
